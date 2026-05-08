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
use Illuminate\Support\Str;
use Tests\TestCase;

class SuperadminRestaurantsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        JWT::$timestamp = null;
        parent::tearDown();
    }

    public function test_superadmin_lists_all_restaurants(): void
    {
        $superadminRestaurant = $this->seedSuperadminRestaurant();
        $superadmin = $this->seedSuperadmin($superadminRestaurant);
        EloquentRestaurant::factory()->count(3)->create();

        $token = $this->issueToken(
            userId: Uuid::create($superadmin->uuid),
            restaurantId: Uuid::create($superadminRestaurant->uuid),
            role: UserRole::superadmin(),
        );

        $response = $this->getJson(
            '/api/superadmin/restaurants',
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(200);
        $response->assertJsonCount(4);
    }

    public function test_superadmin_gets_any_restaurant_by_id(): void
    {
        $superadminRestaurant = $this->seedSuperadminRestaurant();
        $superadmin = $this->seedSuperadmin($superadminRestaurant);
        $target = EloquentRestaurant::factory()->create([
            'name' => 'Other Bistro',
            'email' => 'other@bistro.com',
        ]);

        $token = $this->issueToken(
            userId: Uuid::create($superadmin->uuid),
            restaurantId: Uuid::create($superadminRestaurant->uuid),
            role: UserRole::superadmin(),
        );

        $response = $this->getJson(
            "/api/superadmin/restaurants/{$target->uuid}",
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $target->uuid,
            'name' => 'Other Bistro',
            'email' => 'other@bistro.com',
        ]);
    }

    public function test_superadmin_updates_any_restaurant(): void
    {
        $superadminRestaurant = $this->seedSuperadminRestaurant();
        $superadmin = $this->seedSuperadmin($superadminRestaurant);
        $target = EloquentRestaurant::factory()->create(['name' => 'Old Name']);

        $token = $this->issueToken(
            userId: Uuid::create($superadmin->uuid),
            restaurantId: Uuid::create($superadminRestaurant->uuid),
            role: UserRole::superadmin(),
        );

        $response = $this->putJson(
            "/api/superadmin/restaurants/{$target->uuid}",
            ['name' => 'New Name'],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(200);
        $this->assertDatabaseHas('restaurants', [
            'uuid' => $target->uuid,
            'name' => 'New Name',
        ]);
    }

    public function test_returns_403_when_admin_tenant_tries_to_list(): void
    {
        $this->seedSuperadminRestaurant();
        $tenant = EloquentRestaurant::factory()->create();
        $admin = EloquentUser::factory()->create([
            'restaurant_id' => $tenant->id,
            'role' => 'admin',
        ]);

        $token = $this->issueToken(
            userId: Uuid::create($admin->uuid),
            restaurantId: Uuid::create($tenant->uuid),
            role: UserRole::admin(),
        );

        $response = $this->getJson(
            '/api/superadmin/restaurants',
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(403);
    }

    public function test_returns_401_without_token(): void
    {
        $response = $this->getJson('/api/superadmin/restaurants');
        $response->assertStatus(401);
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

    private function seedSuperadmin(EloquentRestaurant $restaurant): EloquentUser
    {
        return EloquentUser::factory()->create([
            'restaurant_id' => $restaurant->id,
            'role' => 'superadmin',
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
