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

class GetMeTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        JWT::$timestamp = null;
        parent::tearDown();
    }

    public function test_get_me_returns_user_profile_when_token_is_valid(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $user = EloquentUser::factory()->create([
            'restaurant_id' => $restaurant->id,
            'role' => 'supervisor',
            'name' => 'Authenticated User',
            'email' => 'me@example.com',
        ]);

        $token = $this->issueToken(
            userId: Uuid::create($user->uuid),
            restaurantId: Uuid::create($restaurant->uuid),
            role: UserRole::supervisor(),
        );

        $response = $this->getJson(
            "/api/restaurants/{$restaurant->uuid}/auth/me",
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(200);
        $response->assertJson([
            'user' => [
                'id' => $user->uuid,
                'restaurant_id' => $restaurant->uuid,
                'role' => 'supervisor',
                'name' => 'Authenticated User',
                'email' => 'me@example.com',
                'image_src' => null,
            ],
        ]);
    }

    public function test_get_me_returns_401_without_authorization_header(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();

        $response = $this->getJson("/api/restaurants/{$restaurant->uuid}/auth/me");

        $response->assertStatus(401);
    }

    public function test_get_me_returns_401_when_token_belongs_to_different_restaurant(): void
    {
        $restaurantA = EloquentRestaurant::factory()->create();
        $restaurantB = EloquentRestaurant::factory()->create();
        $userInA = EloquentUser::factory()->create([
            'restaurant_id' => $restaurantA->id,
        ]);

        $tokenForA = $this->issueToken(
            userId: Uuid::create($userInA->uuid),
            restaurantId: Uuid::create($restaurantA->uuid),
            role: UserRole::admin(),
        );

        $response = $this->getJson(
            "/api/restaurants/{$restaurantB->uuid}/auth/me",
            ['Authorization' => 'Bearer '.$tokenForA],
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
