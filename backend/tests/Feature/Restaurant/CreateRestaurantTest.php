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

class CreateRestaurantTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        JWT::$timestamp = null;
        parent::tearDown();
    }

    public function test_superadmin_creates_restaurant_with_admin_atomically(): void
    {
        $superadminRestaurant = $this->seedSuperadminRestaurant();
        $superadmin = EloquentUser::factory()->create([
            'restaurant_id' => $superadminRestaurant->id,
            'role' => 'superadmin',
        ]);

        $token = $this->issueToken(
            userId: Uuid::create($superadmin->uuid),
            restaurantId: Uuid::create($superadminRestaurant->uuid),
            role: UserRole::superadmin(),
        );

        $response = $this->postJson(
            '/api/superadmin/restaurants',
            [
                'name' => 'Bistro Stellar',
                'legal_name' => 'Bistro Stellar SL',
                'tax_id' => 'B12345678',
                'email' => 'owner@bistro-stellar.com',
                'admin_name' => 'Bistro Admin',
                'admin_email' => 'admin@bistro-stellar.com',
                'admin_password' => 'super-secret-1',
                'admin_password_confirmation' => 'super-secret-1',
            ],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'restaurant' => ['id', 'name', 'legal_name', 'tax_id', 'email', 'created_at', 'updated_at'],
            'admin' => ['id', 'restaurant_id', 'name', 'email', 'role'],
        ]);
        $response->assertJson([
            'restaurant' => [
                'name' => 'Bistro Stellar',
                'email' => 'owner@bistro-stellar.com',
            ],
            'admin' => [
                'name' => 'Bistro Admin',
                'email' => 'admin@bistro-stellar.com',
                'role' => 'admin',
            ],
        ]);

        $this->assertDatabaseHas('restaurants', ['email' => 'owner@bistro-stellar.com']);
        $this->assertDatabaseHas('users', [
            'email' => 'admin@bistro-stellar.com',
            'role' => 'admin',
        ]);
    }

    public function test_returns_409_when_restaurant_email_already_exists(): void
    {
        $superadminRestaurant = $this->seedSuperadminRestaurant();
        $superadmin = EloquentUser::factory()->create([
            'restaurant_id' => $superadminRestaurant->id,
            'role' => 'superadmin',
        ]);
        EloquentRestaurant::factory()->create(['email' => 'taken@bistro.com']);

        $token = $this->issueToken(
            userId: Uuid::create($superadmin->uuid),
            restaurantId: Uuid::create($superadminRestaurant->uuid),
            role: UserRole::superadmin(),
        );

        $response = $this->postJson(
            '/api/superadmin/restaurants',
            [
                'name' => 'New Bistro',
                'legal_name' => 'New Bistro SL',
                'tax_id' => 'B99999999',
                'email' => 'taken@bistro.com',
                'admin_name' => 'Whatever',
                'admin_email' => 'admin@new.com',
                'admin_password' => 'super-secret-1',
                'admin_password_confirmation' => 'super-secret-1',
            ],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(409);
    }

    public function test_returns_409_when_admin_email_already_exists(): void
    {
        $superadminRestaurant = $this->seedSuperadminRestaurant();
        $superadmin = EloquentUser::factory()->create([
            'restaurant_id' => $superadminRestaurant->id,
            'role' => 'superadmin',
        ]);
        EloquentUser::factory()->create([
            'restaurant_id' => $superadminRestaurant->id,
            'email' => 'admin@taken.com',
        ]);

        $token = $this->issueToken(
            userId: Uuid::create($superadmin->uuid),
            restaurantId: Uuid::create($superadminRestaurant->uuid),
            role: UserRole::superadmin(),
        );

        $response = $this->postJson(
            '/api/superadmin/restaurants',
            [
                'name' => 'New Bistro',
                'legal_name' => 'New Bistro SL',
                'tax_id' => 'B99999999',
                'email' => 'new@bistro.com',
                'admin_name' => 'Whatever',
                'admin_email' => 'admin@taken.com',
                'admin_password' => 'super-secret-1',
                'admin_password_confirmation' => 'super-secret-1',
            ],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(409);
        $this->assertDatabaseMissing('restaurants', ['email' => 'new@bistro.com']);
    }

    public function test_returns_403_when_admin_tenant_tries_to_create_restaurant(): void
    {
        $this->seedSuperadminRestaurant();
        $tenantRestaurant = EloquentRestaurant::factory()->create();
        $admin = EloquentUser::factory()->create([
            'restaurant_id' => $tenantRestaurant->id,
            'role' => 'admin',
        ]);

        $token = $this->issueToken(
            userId: Uuid::create($admin->uuid),
            restaurantId: Uuid::create($tenantRestaurant->uuid),
            role: UserRole::admin(),
        );

        $response = $this->postJson(
            '/api/superadmin/restaurants',
            [
                'name' => 'Hacker',
                'legal_name' => 'Hacker SL',
                'tax_id' => 'B00000000',
                'email' => 'hacker@x.com',
                'admin_name' => 'Hacker',
                'admin_email' => 'hacker-admin@x.com',
                'admin_password' => 'super-secret-1',
                'admin_password_confirmation' => 'super-secret-1',
            ],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(403);
    }

    public function test_returns_401_without_token(): void
    {
        $response = $this->postJson('/api/superadmin/restaurants', [
            'name' => 'X',
            'legal_name' => 'X',
            'tax_id' => 'B00000000',
            'email' => 'x@x.com',
            'admin_name' => 'X',
            'admin_email' => 'admin@x.com',
            'admin_password' => 'super-secret-1',
            'admin_password_confirmation' => 'super-secret-1',
        ]);

        $response->assertStatus(401);
    }

    public function test_returns_422_when_validation_fails(): void
    {
        $superadminRestaurant = $this->seedSuperadminRestaurant();
        $superadmin = EloquentUser::factory()->create([
            'restaurant_id' => $superadminRestaurant->id,
            'role' => 'superadmin',
        ]);

        $token = $this->issueToken(
            userId: Uuid::create($superadmin->uuid),
            restaurantId: Uuid::create($superadminRestaurant->uuid),
            role: UserRole::superadmin(),
        );

        $response = $this->postJson(
            '/api/superadmin/restaurants',
            [
                'name' => '',
                'email' => 'not-an-email',
                'admin_email' => 'also-not-an-email',
                'admin_password' => 'short',
                'admin_password_confirmation' => 'different',
            ],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(422);
    }

    private function seedSuperadminRestaurant(): EloquentRestaurant
    {
        return EloquentRestaurant::query()->create([
            'uuid' => (string) config('superadmin.restaurant_uuid'),
            'name' => 'Superadmin',
            'legal_name' => 'Superadmin',
            'tax_id' => 'SUPERADMIN',
            'email' => 'system@yurest.local',
            'password' => null,
        ]);
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
