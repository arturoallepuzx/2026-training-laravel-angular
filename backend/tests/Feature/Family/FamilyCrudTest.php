<?php

declare(strict_types=1);

namespace Tests\Feature\Family;

use App\Auth\Domain\Interfaces\AccessTokenIssuerInterface;
use App\Auth\Domain\ValueObject\AccessTokenPayload;
use App\Family\Infrastructure\Persistence\Models\EloquentFamily;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\UserRole;
use App\Shared\Domain\ValueObject\Uuid;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        JWT::$timestamp = null;
        parent::tearDown();
    }

    public function test_get_all_returns_only_families_for_authenticated_restaurant(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $otherRestaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));

        EloquentFamily::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Bebidas',
            'active' => true,
        ]);
        EloquentFamily::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Postres',
            'active' => false,
        ]);
        EloquentFamily::factory()->create([
            'restaurant_id' => $otherRestaurant->id,
            'name' => 'Other Drinks',
            'active' => true,
        ]);

        $response = $this->getJson(
            "/api/restaurants/{$restaurant->uuid}/families",
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(200);
        $response->assertJsonCount(2);
        $response->assertJsonFragment(['name' => 'Bebidas', 'active' => true]);
        $response->assertJsonFragment(['name' => 'Postres', 'active' => false]);
        $response->assertJsonMissing(['name' => 'Other Drinks']);
    }

    public function test_get_by_id_returns_family_when_it_belongs_to_restaurant(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));
        $family = EloquentFamily::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Bebidas',
            'active' => true,
        ]);

        $response = $this->getJson(
            "/api/restaurants/{$restaurant->uuid}/families/{$family->uuid}",
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $family->uuid,
            'restaurant_id' => $restaurant->uuid,
            'name' => 'Bebidas',
            'active' => true,
        ]);
    }

    public function test_get_by_id_returns_404_when_family_belongs_to_other_restaurant(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $otherRestaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));
        $family = EloquentFamily::factory()->create([
            'restaurant_id' => $otherRestaurant->id,
            'name' => 'Other Drinks',
        ]);

        $response = $this->getJson(
            "/api/restaurants/{$restaurant->uuid}/families/{$family->uuid}",
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(404);
    }

    public function test_post_creates_family_when_authenticated_as_admin(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));

        $response = $this->postJson(
            "/api/restaurants/{$restaurant->uuid}/families",
            ['name' => 'Bebidas', 'active' => true],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(201);
        $response->assertJson([
            'restaurant_id' => $restaurant->uuid,
            'name' => 'Bebidas',
            'active' => true,
        ]);
        $this->assertDatabaseHas('families', [
            'restaurant_id' => $restaurant->id,
            'name' => 'Bebidas',
            'active' => true,
            'deleted_at' => null,
        ]);
    }

    public function test_post_returns_422_for_invalid_payload(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));

        $response = $this->postJson(
            "/api/restaurants/{$restaurant->uuid}/families",
            ['name' => '', 'active' => 'not-boolean'],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'error',
            'details' => [
                'name',
                'active',
            ],
        ]);
    }

    public function test_post_returns_409_when_name_already_exists_in_restaurant(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));
        EloquentFamily::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Bebidas',
        ]);

        $response = $this->postJson(
            "/api/restaurants/{$restaurant->uuid}/families",
            ['name' => 'Bebidas'],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(409);
    }

    public function test_put_updates_family_when_authenticated_as_admin(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));
        $family = EloquentFamily::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Bebidas',
            'active' => true,
        ]);

        $response = $this->putJson(
            "/api/restaurants/{$restaurant->uuid}/families/{$family->uuid}",
            ['name' => 'Postres', 'active' => false],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $family->uuid,
            'restaurant_id' => $restaurant->uuid,
            'name' => 'Postres',
            'active' => false,
        ]);
        $this->assertDatabaseHas('families', [
            'uuid' => $family->uuid,
            'name' => 'Postres',
            'active' => false,
        ]);
    }

    public function test_put_returns_409_when_name_already_exists_in_restaurant(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));
        EloquentFamily::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Bebidas',
        ]);
        $target = EloquentFamily::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Postres',
        ]);

        $response = $this->putJson(
            "/api/restaurants/{$restaurant->uuid}/families/{$target->uuid}",
            ['name' => 'Bebidas'],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(409);
    }

    public function test_delete_soft_deletes_family_and_removes_it_from_list(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));
        $deletedFamily = EloquentFamily::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Bebidas',
        ]);
        $remainingFamily = EloquentFamily::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Postres',
        ]);

        $deleteResponse = $this->deleteJson(
            "/api/restaurants/{$restaurant->uuid}/families/{$deletedFamily->uuid}",
            [],
            ['Authorization' => 'Bearer '.$token],
        );

        $deleteResponse->assertStatus(204);
        $this->assertSoftDeleted('families', ['uuid' => $deletedFamily->uuid]);

        $listResponse = $this->getJson(
            "/api/restaurants/{$restaurant->uuid}/families",
            ['Authorization' => 'Bearer '.$token],
        );

        $listResponse->assertStatus(200);
        $listResponse->assertJsonCount(1);
        $listResponse->assertJsonFragment(['id' => $remainingFamily->uuid]);
        $listResponse->assertJsonMissing(['id' => $deletedFamily->uuid]);
    }

    private function issueAdminToken(Uuid $restaurantId): string
    {
        $issuedAt = DomainDateTime::create(new \DateTimeImmutable('2099-01-01 10:00:00'));
        $expiresAt = DomainDateTime::create(new \DateTimeImmutable('2099-01-01 10:15:00'));

        JWT::$timestamp = $issuedAt->value()->getTimestamp() + 60;

        $payload = AccessTokenPayload::create(
            Uuid::generate(),
            $restaurantId,
            UserRole::admin(),
            Uuid::generate(),
            $issuedAt,
            $expiresAt,
        );

        return $this->app->make(AccessTokenIssuerInterface::class)->issue($payload)->value();
    }
}
