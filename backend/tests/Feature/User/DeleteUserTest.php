<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use App\Auth\Domain\Interfaces\AccessTokenIssuerInterface;
use App\Auth\Domain\ValueObject\AccessTokenPayload;
use App\Auth\Infrastructure\Persistence\Models\EloquentRefreshToken;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\UserRole;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteUserTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        JWT::$timestamp = null;
        parent::tearDown();
    }

    public function test_admin_soft_deletes_user_and_revokes_sessions(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $admin = EloquentUser::factory()->create([
            'restaurant_id' => $restaurant->id,
            'role' => 'admin',
        ]);
        $target = EloquentUser::factory()->create([
            'restaurant_id' => $restaurant->id,
        ]);

        EloquentRefreshToken::create([
            'uuid' => Uuid::generate()->value(),
            'session_uuid' => Uuid::generate()->value(),
            'user_id' => $target->id,
            'token_hash' => 'hash-1',
            'expires_at' => (new \DateTimeImmutable)->modify('+7 days'),
            'revoked_at' => null,
        ]);

        $token = $this->issueToken(
            userId: Uuid::create($admin->uuid),
            restaurantId: Uuid::create($restaurant->uuid),
            role: UserRole::admin(),
        );

        $response = $this->deleteJson(
            "/api/restaurants/{$restaurant->uuid}/users/{$target->uuid}",
            [],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(204);

        $this->assertSoftDeleted('users', ['uuid' => $target->uuid]);
        $this->assertDatabaseMissing('refresh_tokens', [
            'user_id' => $target->id,
            'revoked_at' => null,
        ]);
    }

    public function test_returns_404_when_user_not_found(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $admin = EloquentUser::factory()->create([
            'restaurant_id' => $restaurant->id,
            'role' => 'admin',
        ]);

        $token = $this->issueToken(
            userId: Uuid::create($admin->uuid),
            restaurantId: Uuid::create($restaurant->uuid),
            role: UserRole::admin(),
        );

        $missing = Uuid::generate()->value();

        $response = $this->deleteJson(
            "/api/restaurants/{$restaurant->uuid}/users/{$missing}",
            [],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(404);
    }

    public function test_returns_404_when_user_belongs_to_different_restaurant(): void
    {
        $restaurantA = EloquentRestaurant::factory()->create();
        $restaurantB = EloquentRestaurant::factory()->create();
        $admin = EloquentUser::factory()->create([
            'restaurant_id' => $restaurantA->id,
            'role' => 'admin',
        ]);
        $userInB = EloquentUser::factory()->create([
            'restaurant_id' => $restaurantB->id,
        ]);

        $token = $this->issueToken(
            userId: Uuid::create($admin->uuid),
            restaurantId: Uuid::create($restaurantA->uuid),
            role: UserRole::admin(),
        );

        $response = $this->deleteJson(
            "/api/restaurants/{$restaurantA->uuid}/users/{$userInB->uuid}",
            [],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(404);
    }

    public function test_returns_403_when_non_admin(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $operator = EloquentUser::factory()->create([
            'restaurant_id' => $restaurant->id,
            'role' => 'operator',
        ]);
        $target = EloquentUser::factory()->create([
            'restaurant_id' => $restaurant->id,
        ]);

        $token = $this->issueToken(
            userId: Uuid::create($operator->uuid),
            restaurantId: Uuid::create($restaurant->uuid),
            role: UserRole::operator(),
        );

        $response = $this->deleteJson(
            "/api/restaurants/{$restaurant->uuid}/users/{$target->uuid}",
            [],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(403);
    }

    private function issueToken(Uuid $userId, Uuid $restaurantId, UserRole $role): string
    {
        $issuedAt = DomainDateTime::create(new \DateTimeImmutable('2099-01-01 10:00:00'));
        $expiresAt = DomainDateTime::create(new \DateTimeImmutable('2099-01-01 10:15:00'));

        JWT::$timestamp = $issuedAt->value()->getTimestamp() + 60;

        $payload = AccessTokenPayload::create(
            $userId,
            $restaurantId,
            $role,
            Uuid::generate(),
            $issuedAt,
            $expiresAt,
        );

        return $this->app->make(AccessTokenIssuerInterface::class)->issue($payload)->value();
    }
}
