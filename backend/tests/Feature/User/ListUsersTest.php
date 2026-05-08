<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use App\Auth\Domain\Interfaces\AccessTokenIssuerInterface;
use App\Auth\Domain\ValueObject\AccessTokenPayload;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\UserRole;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListUsersTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        JWT::$timestamp = null;
        parent::tearDown();
    }

    public function test_admin_lists_users_only_from_their_restaurant(): void
    {
        $restaurantA = EloquentRestaurant::factory()->create();
        $restaurantB = EloquentRestaurant::factory()->create();

        $admin = EloquentUser::factory()->create([
            'restaurant_id' => $restaurantA->id,
            'role' => 'admin',
        ]);
        EloquentUser::factory()->count(2)->create([
            'restaurant_id' => $restaurantA->id,
            'role' => 'operator',
        ]);
        EloquentUser::factory()->count(3)->create([
            'restaurant_id' => $restaurantB->id,
        ]);

        $token = $this->issueToken(
            userId: Uuid::create($admin->uuid),
            restaurantId: Uuid::create($restaurantA->uuid),
            role: UserRole::admin(),
        );

        $response = $this->getJson(
            "/api/restaurants/{$restaurantA->uuid}/users",
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(200);
        $response->assertJsonCount(3);
        $response->assertJsonFragment(['restaurant_id' => $restaurantA->uuid]);
        $response->assertJsonMissing(['restaurant_id' => $restaurantB->uuid]);
    }

    public function test_excludes_soft_deleted_users(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $admin = EloquentUser::factory()->create([
            'restaurant_id' => $restaurant->id,
            'role' => 'admin',
        ]);
        $deleted = EloquentUser::factory()->create([
            'restaurant_id' => $restaurant->id,
        ]);
        $deleted->delete();

        $token = $this->issueToken(
            userId: Uuid::create($admin->uuid),
            restaurantId: Uuid::create($restaurant->uuid),
            role: UserRole::admin(),
        );

        $response = $this->getJson(
            "/api/restaurants/{$restaurant->uuid}/users",
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonMissing(['id' => $deleted->uuid]);
    }

    public function test_returns_403_when_non_admin_lists_users(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $operator = EloquentUser::factory()->create([
            'restaurant_id' => $restaurant->id,
            'role' => 'operator',
        ]);

        $token = $this->issueToken(
            userId: Uuid::create($operator->uuid),
            restaurantId: Uuid::create($restaurant->uuid),
            role: UserRole::operator(),
        );

        $response = $this->getJson(
            "/api/restaurants/{$restaurant->uuid}/users",
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(403);
    }

    public function test_returns_401_without_authorization(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();

        $response = $this->getJson("/api/restaurants/{$restaurant->uuid}/users");

        $response->assertStatus(401);
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
