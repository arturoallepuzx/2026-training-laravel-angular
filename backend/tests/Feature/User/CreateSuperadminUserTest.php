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
use Illuminate\Support\Str;
use Tests\TestCase;

class CreateSuperadminUserTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        JWT::$timestamp = null;
        parent::tearDown();
    }

    public function test_superadmin_creates_another_superadmin(): void
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
            '/api/superadmin/users',
            [
                'name' => 'Second Superadmin',
                'email' => 'second-superadmin@yurest.local',
                'password' => 'super-secret-1',
                'password_confirmation' => 'super-secret-1',
            ],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(201);
        $response->assertJsonStructure(['id', 'name', 'email', 'created_at', 'updated_at']);
        $response->assertJson([
            'name' => 'Second Superadmin',
            'email' => 'second-superadmin@yurest.local',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'second-superadmin@yurest.local',
            'role' => 'superadmin',
            'restaurant_id' => $superadminRestaurant->id,
        ]);
    }

    public function test_returns_409_when_email_already_exists(): void
    {
        $superadminRestaurant = $this->seedSuperadminRestaurant();
        $superadmin = EloquentUser::factory()->create([
            'restaurant_id' => $superadminRestaurant->id,
            'role' => 'superadmin',
            'email' => 'taken@yurest.local',
        ]);

        $token = $this->issueToken(
            userId: Uuid::create($superadmin->uuid),
            restaurantId: Uuid::create($superadminRestaurant->uuid),
            role: UserRole::superadmin(),
        );

        $response = $this->postJson(
            '/api/superadmin/users',
            [
                'name' => 'Another One',
                'email' => 'taken@yurest.local',
                'password' => 'super-secret-1',
                'password_confirmation' => 'super-secret-1',
            ],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(409);
    }

    public function test_returns_403_when_admin_tenant_tries_to_create_superadmin(): void
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
            '/api/superadmin/users',
            [
                'name' => 'Hacker',
                'email' => 'hacker@example.com',
                'password' => 'super-secret-1',
                'password_confirmation' => 'super-secret-1',
            ],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(403);
    }

    public function test_returns_401_without_token(): void
    {
        $this->seedSuperadminRestaurant();

        $response = $this->postJson('/api/superadmin/users', [
            'name' => 'Whoever',
            'email' => 'whoever@example.com',
            'password' => 'super-secret-1',
            'password_confirmation' => 'super-secret-1',
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
            '/api/superadmin/users',
            [
                'name' => '',
                'email' => 'not-an-email',
                'password' => 'short',
                'password_confirmation' => 'different',
            ],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(422);
    }

    private function seedSuperadminRestaurant(): EloquentRestaurant
    {
        return EloquentRestaurant::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Superadmin',
            'legal_name' => 'Superadmin',
            'tax_id' => 'SUPERADMIN',
            'email' => (string) config('superadmin.restaurant_email'),
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
