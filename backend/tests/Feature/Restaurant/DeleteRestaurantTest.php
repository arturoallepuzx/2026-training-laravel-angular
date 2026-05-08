<?php

declare(strict_types=1);

namespace Tests\Feature\Restaurant;

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

class DeleteRestaurantTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        JWT::$timestamp = null;
        parent::tearDown();
    }

    public function test_admin_soft_deletes_their_restaurant(): void
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

        $response = $this->deleteJson(
            "/api/restaurants/{$restaurant->uuid}",
            [],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(204);
        $this->assertSoftDeleted('restaurants', ['uuid' => $restaurant->uuid]);
    }

    public function test_returns_403_when_non_admin(): void
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

        $response = $this->deleteJson(
            "/api/restaurants/{$restaurant->uuid}",
            [],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(403);
    }

    public function test_returns_401_when_token_belongs_to_different_restaurant(): void
    {
        $restaurantA = EloquentRestaurant::factory()->create();
        $restaurantB = EloquentRestaurant::factory()->create();
        $admin = EloquentUser::factory()->create([
            'restaurant_id' => $restaurantA->id,
            'role' => 'admin',
        ]);

        $token = $this->issueToken(
            userId: Uuid::create($admin->uuid),
            restaurantId: Uuid::create($restaurantA->uuid),
            role: UserRole::admin(),
        );

        $response = $this->deleteJson(
            "/api/restaurants/{$restaurantB->uuid}",
            [],
            ['Authorization' => 'Bearer '.$token],
        );

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
