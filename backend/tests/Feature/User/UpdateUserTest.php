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

class UpdateUserTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        JWT::$timestamp = null;
        parent::tearDown();
    }

    public function test_admin_updates_user_name_and_role(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $admin = EloquentUser::factory()->create([
            'restaurant_id' => $restaurant->id,
            'role' => 'admin',
        ]);
        $target = EloquentUser::factory()->create([
            'restaurant_id' => $restaurant->id,
            'role' => 'operator',
            'name' => 'Old Name',
        ]);

        $token = $this->issueToken(
            userId: Uuid::create($admin->uuid),
            restaurantId: Uuid::create($restaurant->uuid),
            role: UserRole::admin(),
        );

        $response = $this->putJson(
            "/api/restaurants/{$restaurant->uuid}/users/{$target->uuid}",
            [
                'name' => 'New Name',
                'role' => 'supervisor',
            ],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $target->uuid,
            'name' => 'New Name',
            'role' => 'supervisor',
        ]);

        $this->assertDatabaseHas('users', [
            'uuid' => $target->uuid,
            'name' => 'New Name',
            'role' => 'supervisor',
        ]);
    }

    public function test_admin_updates_image_src_to_null(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $admin = EloquentUser::factory()->create([
            'restaurant_id' => $restaurant->id,
            'role' => 'admin',
        ]);
        $target = EloquentUser::factory()->create([
            'restaurant_id' => $restaurant->id,
            'image_src' => 'https://cdn.example.com/photo.jpg',
        ]);

        $token = $this->issueToken(
            userId: Uuid::create($admin->uuid),
            restaurantId: Uuid::create($restaurant->uuid),
            role: UserRole::admin(),
        );

        $response = $this->putJson(
            "/api/restaurants/{$restaurant->uuid}/users/{$target->uuid}",
            ['image_src' => null],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', [
            'uuid' => $target->uuid,
            'image_src' => null,
        ]);
    }

    public function test_returns_409_when_email_already_taken(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $admin = EloquentUser::factory()->create([
            'restaurant_id' => $restaurant->id,
            'role' => 'admin',
        ]);
        EloquentUser::factory()->create([
            'restaurant_id' => $restaurant->id,
            'email' => 'taken@example.com',
        ]);
        $target = EloquentUser::factory()->create([
            'restaurant_id' => $restaurant->id,
        ]);

        $token = $this->issueToken(
            userId: Uuid::create($admin->uuid),
            restaurantId: Uuid::create($restaurant->uuid),
            role: UserRole::admin(),
        );

        $response = $this->putJson(
            "/api/restaurants/{$restaurant->uuid}/users/{$target->uuid}",
            ['email' => 'taken@example.com'],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(409);
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

        $response = $this->putJson(
            "/api/restaurants/{$restaurant->uuid}/users/{$missing}",
            ['name' => 'Whatever'],
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

        $response = $this->putJson(
            "/api/restaurants/{$restaurant->uuid}/users/{$target->uuid}",
            ['name' => 'Hacked'],
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
