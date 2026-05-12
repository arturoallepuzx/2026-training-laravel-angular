<?php

declare(strict_types=1);

namespace Tests\Feature\Zone;

use App\Auth\Domain\Interfaces\AccessTokenIssuerInterface;
use App\Auth\Domain\ValueObject\AccessTokenPayload;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\UserRole;
use App\Shared\Domain\ValueObject\Uuid;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ZoneRoutesProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        JWT::$timestamp = null;
        parent::tearDown();
    }

    public function test_get_all_returns_401_without_token(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();

        $response = $this->getJson("/api/restaurants/{$restaurant->uuid}/zones");

        $response->assertStatus(401);
    }

    public function test_get_all_returns_200_with_operator_token(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueToken(
            restaurantId: Uuid::create($restaurant->uuid),
            role: UserRole::operator(),
        );

        $response = $this->getJson(
            "/api/restaurants/{$restaurant->uuid}/zones",
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(200);
    }

    public function test_get_all_returns_401_when_token_is_for_different_restaurant(): void
    {
        $restaurantA = EloquentRestaurant::factory()->create();
        $restaurantB = EloquentRestaurant::factory()->create();
        $token = $this->issueToken(
            restaurantId: Uuid::create($restaurantA->uuid),
            role: UserRole::admin(),
        );

        $response = $this->getJson(
            "/api/restaurants/{$restaurantB->uuid}/zones",
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(401);
    }

    public function test_post_returns_403_with_operator_token(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueToken(
            restaurantId: Uuid::create($restaurant->uuid),
            role: UserRole::operator(),
        );

        $response = $this->postJson(
            "/api/restaurants/{$restaurant->uuid}/zones",
            ['name' => 'Terraza'],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(403);
    }

    public function test_post_returns_403_with_supervisor_token(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueToken(
            restaurantId: Uuid::create($restaurant->uuid),
            role: UserRole::supervisor(),
        );

        $response = $this->postJson(
            "/api/restaurants/{$restaurant->uuid}/zones",
            ['name' => 'Terraza'],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(403);
    }

    public function test_put_returns_403_with_operator_token(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueToken(
            restaurantId: Uuid::create($restaurant->uuid),
            role: UserRole::operator(),
        );

        $response = $this->putJson(
            "/api/restaurants/{$restaurant->uuid}/zones/".Uuid::generate()->value(),
            ['name' => 'Terraza'],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(403);
    }

    public function test_delete_returns_403_with_operator_token(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueToken(
            restaurantId: Uuid::create($restaurant->uuid),
            role: UserRole::operator(),
        );

        $response = $this->deleteJson(
            "/api/restaurants/{$restaurant->uuid}/zones/".Uuid::generate()->value(),
            [],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(403);
    }

    public function test_post_passes_middlewares_with_admin_token(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueToken(
            restaurantId: Uuid::create($restaurant->uuid),
            role: UserRole::admin(),
        );

        $response = $this->postJson(
            "/api/restaurants/{$restaurant->uuid}/zones",
            ['name' => 'Terraza'],
            ['Authorization' => 'Bearer '.$token],
        );

        $this->assertNotSame(401, $response->status(), 'admin token must pass auth.access_token');
        $this->assertNotSame(403, $response->status(), 'admin token must pass auth.role:admin');
    }

    private function issueToken(Uuid $restaurantId, UserRole $role): string
    {
        $issuedAt = DomainDateTime::create(new \DateTimeImmutable('2099-01-01 10:00:00'));
        $expiresAt = DomainDateTime::create(new \DateTimeImmutable('2099-01-01 10:15:00'));

        JWT::$timestamp = $issuedAt->value()->getTimestamp() + 60;

        $payload = AccessTokenPayload::create(
            Uuid::generate(),
            $restaurantId,
            $role,
            Uuid::generate(),
            $issuedAt,
            $expiresAt,
        );

        return $this->app->make(AccessTokenIssuerInterface::class)->issue($payload)->value();
    }
}
