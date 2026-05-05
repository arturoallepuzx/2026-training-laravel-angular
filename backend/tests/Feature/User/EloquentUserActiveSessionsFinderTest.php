<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Persistence\EloquentRestaurantIdResolver;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use App\User\Infrastructure\Persistence\Repositories\EloquentUserActiveSessionsFinder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EloquentUserActiveSessionsFinderTest extends TestCase
{
    use RefreshDatabase;

    private function finder(): EloquentUserActiveSessionsFinder
    {
        return new EloquentUserActiveSessionsFinder(new EloquentRestaurantIdResolver);
    }

    public function test_find_users_with_active_sessions_filters_counts_and_orders_results(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $otherRestaurant = EloquentRestaurant::factory()->create();
        $juan = EloquentUser::factory()->create([
            'restaurant_id' => $restaurant->id,
            'role' => 'operator',
            'name' => 'Juan',
            'email' => 'juan@tpv.test',
            'image_src' => null,
        ]);
        $laura = EloquentUser::factory()->create([
            'restaurant_id' => $restaurant->id,
            'role' => 'supervisor',
            'name' => 'Laura',
            'email' => 'laura@tpv.test',
            'image_src' => '/profiles/laura.png',
        ]);
        $withoutSessions = EloquentUser::factory()->create(['restaurant_id' => $restaurant->id]);
        $expiredOnly = EloquentUser::factory()->create(['restaurant_id' => $restaurant->id]);
        $revokedOnly = EloquentUser::factory()->create(['restaurant_id' => $restaurant->id]);
        $deletedWithActiveSession = EloquentUser::factory()->create(['restaurant_id' => $restaurant->id]);
        $deletedWithActiveSession->delete();
        $otherRestaurantUser = EloquentUser::factory()->create(['restaurant_id' => $otherRestaurant->id]);

        $juanSessionA = Uuid::generate();
        $this->insertRefreshToken($juan, $juanSessionA, updatedAt: '2099-01-01 10:00:00');
        $this->insertRefreshToken($juan, $juanSessionA, updatedAt: '2099-01-01 11:00:00');
        $this->insertRefreshToken($juan, Uuid::generate(), updatedAt: '2099-01-01 12:00:00');

        $this->insertRefreshToken($laura, Uuid::generate(), updatedAt: '2099-01-01 13:00:00');
        $this->insertRefreshToken($expiredOnly, Uuid::generate(), expiresAt: '2000-01-01 10:00:00');
        $this->insertRefreshToken($revokedOnly, Uuid::generate(), revokedAt: '2099-01-01 09:00:00');
        $this->insertRefreshToken($deletedWithActiveSession, Uuid::generate());
        $this->insertRefreshToken($otherRestaurantUser, Uuid::generate());

        $users = $this->finder()->findUsersWithActiveSessionsByRestaurantId(Uuid::create($restaurant->uuid));

        $this->assertCount(2, $users);
        $this->assertSame($laura->uuid, $users[0]->userId()->value(), 'users must be ordered by last_seen_at descending');
        $this->assertSame('Laura', $users[0]->name()->value());
        $this->assertSame('laura@tpv.test', $users[0]->email()->value());
        $this->assertSame('supervisor', $users[0]->role()->value());
        $this->assertSame('/profiles/laura.png', $users[0]->imageSrc());
        $this->assertSame(1, $users[0]->activeSessions());
        $this->assertSame('2099-01-01T13:00:00+00:00', $users[0]->lastSeenAt()->format(\DateTimeInterface::ATOM));

        $this->assertSame($juan->uuid, $users[1]->userId()->value());
        $this->assertSame('Juan', $users[1]->name()->value());
        $this->assertSame('juan@tpv.test', $users[1]->email()->value());
        $this->assertSame('operator', $users[1]->role()->value());
        $this->assertNull($users[1]->imageSrc());
        $this->assertSame(2, $users[1]->activeSessions(), 'duplicate active rows in one session count once');
        $this->assertSame('2099-01-01T12:00:00+00:00', $users[1]->lastSeenAt()->format(\DateTimeInterface::ATOM));

        $returnedIds = array_map(fn ($u) => $u->userId()->value(), $users);
        $this->assertNotContains($withoutSessions->uuid, $returnedIds);
        $this->assertNotContains($expiredOnly->uuid, $returnedIds);
        $this->assertNotContains($revokedOnly->uuid, $returnedIds);
        $this->assertNotContains($deletedWithActiveSession->uuid, $returnedIds);
        $this->assertNotContains($otherRestaurantUser->uuid, $returnedIds);
    }

    private function insertRefreshToken(
        EloquentUser $user,
        Uuid $sessionId,
        string $expiresAt = '2099-01-08 10:00:00',
        ?string $revokedAt = null,
        string $updatedAt = '2099-01-01 10:00:00',
    ): void {
        DB::table('refresh_tokens')->insert([
            'uuid' => Uuid::generate()->value(),
            'session_uuid' => $sessionId->value(),
            'user_id' => $user->id,
            'token_hash' => hash('sha256', Uuid::generate()->value()),
            'expires_at' => $expiresAt,
            'revoked_at' => $revokedAt,
            'replaced_by_id' => null,
            'created_at' => '2099-01-01 09:00:00',
            'updated_at' => $updatedAt,
        ]);
    }
}
