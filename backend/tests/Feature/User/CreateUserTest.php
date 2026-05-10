<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use App\Auth\Domain\Interfaces\AccessTokenIssuerInterface;
use App\Auth\Domain\ValueObject\AccessTokenPayload;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\UserRole;
use App\Shared\Domain\ValueObject\Uuid;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateUserTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        JWT::$timestamp = null;
        parent::tearDown();
    }

    public function test_post_users_returns_201_and_user_json_when_authenticated_as_admin(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueToken(
            restaurantId: Uuid::create($restaurant->uuid),
            role: UserRole::admin(),
        );

        $response = $this->postJson(
            "/api/restaurants/{$restaurant->uuid}/users",
            [
                'role' => 'admin',
                'name' => 'Integration User',
                'email' => 'integration@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'pin' => '1234',
            ],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'id',
            'restaurant_id',
            'role',
            'name',
            'email',
            'image_src',
            'created_at',
            'updated_at',
        ]);
        $response->assertJson([
            'restaurant_id' => $restaurant->uuid,
            'role' => 'admin',
            'name' => 'Integration User',
            'email' => 'integration@example.com',
        ]);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $response->json('id')
        );

        $this->assertDatabaseHas('users', [
            'email' => 'integration@example.com',
            'role' => 'admin',
            'name' => 'Integration User',
        ]);
        $storedPin = (string) DB::table('users')
            ->where('email', 'integration@example.com')
            ->value('pin');

        $this->assertNotSame('1234', $storedPin);
        $this->assertTrue(Hash::check('1234', $storedPin));
    }

    public function test_post_users_returns_401_without_token(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();

        $response = $this->postJson("/api/restaurants/{$restaurant->uuid}/users", [
            'role' => 'admin',
            'name' => 'X',
            'email' => 'x@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(401);
    }

    public function test_post_users_returns_403_with_operator_token(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueToken(
            restaurantId: Uuid::create($restaurant->uuid),
            role: UserRole::operator(),
        );

        $response = $this->postJson(
            "/api/restaurants/{$restaurant->uuid}/users",
            [
                'role' => 'operator',
                'name' => 'X',
                'email' => 'x@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(403);
    }

    public function test_post_users_returns_403_with_supervisor_token(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueToken(
            restaurantId: Uuid::create($restaurant->uuid),
            role: UserRole::supervisor(),
        );

        $response = $this->postJson(
            "/api/restaurants/{$restaurant->uuid}/users",
            [
                'role' => 'operator',
                'name' => 'X',
                'email' => 'x@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(403);
    }

    public function test_post_users_returns_401_when_token_is_for_different_restaurant(): void
    {
        $restaurantA = EloquentRestaurant::factory()->create();
        $restaurantB = EloquentRestaurant::factory()->create();
        $token = $this->issueToken(
            restaurantId: Uuid::create($restaurantA->uuid),
            role: UserRole::admin(),
        );

        $response = $this->postJson(
            "/api/restaurants/{$restaurantB->uuid}/users",
            [
                'role' => 'operator',
                'name' => 'X',
                'email' => 'x@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(401);
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
