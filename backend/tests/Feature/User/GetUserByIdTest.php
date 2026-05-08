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

class GetUserByIdTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        JWT::$timestamp = null;
        parent::tearDown();
    }

    public function test_returns_user_data_when_admin_requests_existing_user(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $admin = EloquentUser::factory()->create([
            'restaurant_id' => $restaurant->id,
            'role' => 'admin',
        ]);
        $target = EloquentUser::factory()->create([
            'restaurant_id' => $restaurant->id,
            'role' => 'operator',
            'name' => 'Target User',
            'email' => 'target@example.com',
        ]);

        $token = $this->issueToken(
            userId: Uuid::create($admin->uuid),
            restaurantId: Uuid::create($restaurant->uuid),
            role: UserRole::admin(),
        );

        $response = $this->getJson(
            "/api/restaurants/{$restaurant->uuid}/users/{$target->uuid}",
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $target->uuid,
            'restaurant_id' => $restaurant->uuid,
            'role' => 'operator',
            'name' => 'Target User',
            'email' => 'target@example.com',
            'image_src' => null,
        ]);
    }

    public function test_returns_404_when_user_does_not_exist(): void
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

        $missingId = Uuid::generate()->value();

        $response = $this->getJson(
            "/api/restaurants/{$restaurant->uuid}/users/{$missingId}",
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

        $response = $this->getJson(
            "/api/restaurants/{$restaurantA->uuid}/users/{$userInB->uuid}",
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(404);
    }

    public function test_returns_403_when_non_admin_requests_user(): void
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

        $response = $this->getJson(
            "/api/restaurants/{$restaurant->uuid}/users/{$target->uuid}",
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(403);
    }

    public function test_returns_401_without_authorization(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $target = EloquentUser::factory()->create([
            'restaurant_id' => $restaurant->id,
        ]);

        $response = $this->getJson(
            "/api/restaurants/{$restaurant->uuid}/users/{$target->uuid}",
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
