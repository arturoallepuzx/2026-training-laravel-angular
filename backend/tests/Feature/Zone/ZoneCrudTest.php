<?php

declare(strict_types=1);

namespace Tests\Feature\Zone;

use App\Auth\Domain\Interfaces\AccessTokenIssuerInterface;
use App\Auth\Domain\ValueObject\AccessTokenPayload;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\UserRole;
use App\Shared\Domain\ValueObject\Uuid;
use App\Zone\Infrastructure\Persistence\Models\EloquentZone;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ZoneCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        JWT::$timestamp = null;
        parent::tearDown();
    }

    public function test_get_all_returns_only_zones_for_authenticated_restaurant(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $otherRestaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));

        EloquentZone::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Terraza',
        ]);
        EloquentZone::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Salon Principal',
        ]);
        EloquentZone::factory()->create([
            'restaurant_id' => $otherRestaurant->id,
            'name' => 'Barra',
        ]);

        $response = $this->getJson(
            "/api/restaurants/{$restaurant->uuid}/zones",
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(200);
        $response->assertJsonCount(2);
        $response->assertJsonFragment(['name' => 'Terraza']);
        $response->assertJsonFragment(['name' => 'Salon Principal']);
        $response->assertJsonMissing(['name' => 'Barra']);
    }

    public function test_get_by_id_returns_zone_when_it_belongs_to_restaurant(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));
        $zone = EloquentZone::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Terraza',
        ]);

        $response = $this->getJson(
            "/api/restaurants/{$restaurant->uuid}/zones/{$zone->uuid}",
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $zone->uuid,
            'restaurant_id' => $restaurant->uuid,
            'name' => 'Terraza',
        ]);
    }

    public function test_get_by_id_returns_404_when_zone_belongs_to_other_restaurant(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $otherRestaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));
        $zone = EloquentZone::factory()->create([
            'restaurant_id' => $otherRestaurant->id,
            'name' => 'Barra',
        ]);

        $response = $this->getJson(
            "/api/restaurants/{$restaurant->uuid}/zones/{$zone->uuid}",
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(404);
    }

    public function test_post_creates_zone_when_authenticated_as_admin(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));

        $response = $this->postJson(
            "/api/restaurants/{$restaurant->uuid}/zones",
            ['name' => 'Terraza'],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(201);
        $response->assertJson([
            'restaurant_id' => $restaurant->uuid,
            'name' => 'Terraza',
        ]);
        $this->assertDatabaseHas('zones', [
            'restaurant_id' => $restaurant->id,
            'name' => 'Terraza',
            'deleted_at' => null,
        ]);
    }

    public function test_post_returns_422_for_invalid_payload(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));

        $response = $this->postJson(
            "/api/restaurants/{$restaurant->uuid}/zones",
            ['name' => ''],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'error',
            'details' => [
                'name',
            ],
        ]);
    }

    public function test_post_returns_409_when_name_already_exists_in_restaurant(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));
        EloquentZone::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Terraza',
        ]);

        $response = $this->postJson(
            "/api/restaurants/{$restaurant->uuid}/zones",
            ['name' => 'Terraza'],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(409);
    }

    public function test_put_updates_zone_when_authenticated_as_admin(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));
        $zone = EloquentZone::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Terraza',
        ]);

        $response = $this->putJson(
            "/api/restaurants/{$restaurant->uuid}/zones/{$zone->uuid}",
            ['name' => 'Salon Principal'],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $zone->uuid,
            'restaurant_id' => $restaurant->uuid,
            'name' => 'Salon Principal',
        ]);
        $this->assertDatabaseHas('zones', [
            'uuid' => $zone->uuid,
            'name' => 'Salon Principal',
        ]);
    }

    public function test_put_returns_422_for_invalid_payload(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));
        $zone = EloquentZone::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Terraza',
        ]);

        $response = $this->putJson(
            "/api/restaurants/{$restaurant->uuid}/zones/{$zone->uuid}",
            [],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'error',
            'details' => [
                'name',
            ],
        ]);
    }

    public function test_put_returns_409_when_name_already_exists_in_restaurant(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));
        EloquentZone::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Terraza',
        ]);
        $target = EloquentZone::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Salon Principal',
        ]);

        $response = $this->putJson(
            "/api/restaurants/{$restaurant->uuid}/zones/{$target->uuid}",
            ['name' => 'Terraza'],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(409);
    }

    public function test_delete_soft_deletes_zone_and_removes_it_from_list(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));
        $deletedZone = EloquentZone::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Terraza',
        ]);
        $remainingZone = EloquentZone::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Salon Principal',
        ]);

        $deleteResponse = $this->deleteJson(
            "/api/restaurants/{$restaurant->uuid}/zones/{$deletedZone->uuid}",
            [],
            ['Authorization' => 'Bearer '.$token],
        );

        $deleteResponse->assertStatus(204);
        $this->assertSoftDeleted('zones', ['uuid' => $deletedZone->uuid]);

        $listResponse = $this->getJson(
            "/api/restaurants/{$restaurant->uuid}/zones",
            ['Authorization' => 'Bearer '.$token],
        );

        $listResponse->assertStatus(200);
        $listResponse->assertJsonCount(1);
        $listResponse->assertJsonFragment(['id' => $remainingZone->uuid]);
        $listResponse->assertJsonMissing(['id' => $deletedZone->uuid]);
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
