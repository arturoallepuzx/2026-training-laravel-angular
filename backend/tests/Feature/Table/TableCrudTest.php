<?php

declare(strict_types=1);

namespace Tests\Feature\Table;

use App\Auth\Domain\Interfaces\AccessTokenIssuerInterface;
use App\Auth\Domain\ValueObject\AccessTokenPayload;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\UserRole;
use App\Shared\Domain\ValueObject\Uuid;
use App\Table\Infrastructure\Persistence\Models\EloquentTable;
use App\Zone\Infrastructure\Persistence\Models\EloquentZone;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TableCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        JWT::$timestamp = null;
        parent::tearDown();
    }

    public function test_get_all_returns_only_tables_for_authenticated_restaurant(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $otherRestaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));

        $zone = EloquentZone::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Terraza',
        ]);
        $otherZone = EloquentZone::factory()->create([
            'restaurant_id' => $otherRestaurant->id,
            'name' => 'Barra',
        ]);

        EloquentTable::factory()->create([
            'restaurant_id' => $restaurant->id,
            'zone_id' => $zone->id,
            'name' => 'Mesa 1',
        ]);
        EloquentTable::factory()->create([
            'restaurant_id' => $restaurant->id,
            'zone_id' => $zone->id,
            'name' => 'Mesa 2',
        ]);
        EloquentTable::factory()->create([
            'restaurant_id' => $otherRestaurant->id,
            'zone_id' => $otherZone->id,
            'name' => 'Mesa Foreign',
        ]);

        $response = $this->getJson(
            "/api/restaurants/{$restaurant->uuid}/tables",
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(200);
        $response->assertJsonCount(2);
        $response->assertJsonFragment(['name' => 'Mesa 1']);
        $response->assertJsonFragment(['name' => 'Mesa 2']);
        $response->assertJsonMissing(['name' => 'Mesa Foreign']);
    }

    public function test_get_all_excludes_tables_whose_zone_is_soft_deleted(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));

        $activeZone = EloquentZone::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Terraza',
        ]);
        $deletedZone = EloquentZone::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Salon',
        ]);

        EloquentTable::factory()->create([
            'restaurant_id' => $restaurant->id,
            'zone_id' => $activeZone->id,
            'name' => 'Mesa Visible',
        ]);
        EloquentTable::factory()->create([
            'restaurant_id' => $restaurant->id,
            'zone_id' => $deletedZone->id,
            'name' => 'Mesa Hidden',
        ]);

        $deletedZone->delete();

        $response = $this->getJson(
            "/api/restaurants/{$restaurant->uuid}/tables",
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['name' => 'Mesa Visible']);
        $response->assertJsonMissing(['name' => 'Mesa Hidden']);
    }

    public function test_get_by_id_returns_table_when_it_belongs_to_restaurant(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));
        $zone = EloquentZone::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Terraza',
        ]);
        $table = EloquentTable::factory()->create([
            'restaurant_id' => $restaurant->id,
            'zone_id' => $zone->id,
            'name' => 'Mesa 1',
        ]);

        $response = $this->getJson(
            "/api/restaurants/{$restaurant->uuid}/tables/{$table->uuid}",
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $table->uuid,
            'restaurant_id' => $restaurant->uuid,
            'zone_id' => $zone->uuid,
            'name' => 'Mesa 1',
        ]);
    }

    public function test_get_by_id_returns_404_when_table_belongs_to_other_restaurant(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $otherRestaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));
        $otherZone = EloquentZone::factory()->create([
            'restaurant_id' => $otherRestaurant->id,
            'name' => 'Barra',
        ]);
        $table = EloquentTable::factory()->create([
            'restaurant_id' => $otherRestaurant->id,
            'zone_id' => $otherZone->id,
            'name' => 'Mesa Foreign',
        ]);

        $response = $this->getJson(
            "/api/restaurants/{$restaurant->uuid}/tables/{$table->uuid}",
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(404);
    }

    public function test_post_creates_table_when_authenticated_as_admin(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));
        $zone = EloquentZone::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Terraza',
        ]);

        $response = $this->postJson(
            "/api/restaurants/{$restaurant->uuid}/tables",
            ['zone_id' => $zone->uuid, 'name' => 'Mesa 1'],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(201);
        $response->assertJson([
            'restaurant_id' => $restaurant->uuid,
            'zone_id' => $zone->uuid,
            'name' => 'Mesa 1',
        ]);
        $this->assertDatabaseHas('tables', [
            'restaurant_id' => $restaurant->id,
            'zone_id' => $zone->id,
            'name' => 'Mesa 1',
            'deleted_at' => null,
        ]);
    }

    public function test_post_allows_same_name_in_different_zones(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));
        $zoneA = EloquentZone::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Terraza',
        ]);
        $zoneB = EloquentZone::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Salon',
        ]);
        EloquentTable::factory()->create([
            'restaurant_id' => $restaurant->id,
            'zone_id' => $zoneA->id,
            'name' => 'Mesa 1',
        ]);

        $response = $this->postJson(
            "/api/restaurants/{$restaurant->uuid}/tables",
            ['zone_id' => $zoneB->uuid, 'name' => 'Mesa 1'],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(201);
    }

    public function test_post_returns_422_for_invalid_payload(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));

        $response = $this->postJson(
            "/api/restaurants/{$restaurant->uuid}/tables",
            ['zone_id' => 'not-uuid', 'name' => ''],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'error',
            'details' => ['zone_id', 'name'],
        ]);
    }

    public function test_post_returns_404_when_zone_belongs_to_other_restaurant(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $otherRestaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));
        $foreignZone = EloquentZone::factory()->create([
            'restaurant_id' => $otherRestaurant->id,
            'name' => 'Barra',
        ]);

        $response = $this->postJson(
            "/api/restaurants/{$restaurant->uuid}/tables",
            ['zone_id' => $foreignZone->uuid, 'name' => 'Mesa 1'],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(404);
    }

    public function test_post_returns_409_when_name_already_exists_in_zone(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));
        $zone = EloquentZone::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Terraza',
        ]);
        EloquentTable::factory()->create([
            'restaurant_id' => $restaurant->id,
            'zone_id' => $zone->id,
            'name' => 'Mesa 1',
        ]);

        $response = $this->postJson(
            "/api/restaurants/{$restaurant->uuid}/tables",
            ['zone_id' => $zone->uuid, 'name' => 'Mesa 1'],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(409);
    }

    public function test_put_updates_table_name(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));
        $zone = EloquentZone::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Terraza',
        ]);
        $table = EloquentTable::factory()->create([
            'restaurant_id' => $restaurant->id,
            'zone_id' => $zone->id,
            'name' => 'Mesa 1',
        ]);

        $response = $this->putJson(
            "/api/restaurants/{$restaurant->uuid}/tables/{$table->uuid}",
            ['name' => 'Mesa VIP'],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $table->uuid,
            'name' => 'Mesa VIP',
        ]);
    }

    public function test_put_moves_table_to_another_zone(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));
        $zoneA = EloquentZone::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Terraza',
        ]);
        $zoneB = EloquentZone::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Salon',
        ]);
        $table = EloquentTable::factory()->create([
            'restaurant_id' => $restaurant->id,
            'zone_id' => $zoneA->id,
            'name' => 'Mesa 1',
        ]);

        $response = $this->putJson(
            "/api/restaurants/{$restaurant->uuid}/tables/{$table->uuid}",
            ['zone_id' => $zoneB->uuid],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(200);
        $response->assertJson(['zone_id' => $zoneB->uuid]);
    }

    public function test_put_returns_409_when_moving_to_zone_with_existing_name(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));
        $zoneA = EloquentZone::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Terraza',
        ]);
        $zoneB = EloquentZone::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Salon',
        ]);
        $table = EloquentTable::factory()->create([
            'restaurant_id' => $restaurant->id,
            'zone_id' => $zoneA->id,
            'name' => 'Mesa 1',
        ]);
        EloquentTable::factory()->create([
            'restaurant_id' => $restaurant->id,
            'zone_id' => $zoneB->id,
            'name' => 'Mesa 1',
        ]);

        $response = $this->putJson(
            "/api/restaurants/{$restaurant->uuid}/tables/{$table->uuid}",
            ['zone_id' => $zoneB->uuid],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(409);
    }

    public function test_put_returns_404_when_target_zone_belongs_to_other_restaurant(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $otherRestaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));
        $zone = EloquentZone::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Terraza',
        ]);
        $foreignZone = EloquentZone::factory()->create([
            'restaurant_id' => $otherRestaurant->id,
            'name' => 'Barra',
        ]);
        $table = EloquentTable::factory()->create([
            'restaurant_id' => $restaurant->id,
            'zone_id' => $zone->id,
            'name' => 'Mesa 1',
        ]);

        $response = $this->putJson(
            "/api/restaurants/{$restaurant->uuid}/tables/{$table->uuid}",
            ['zone_id' => $foreignZone->uuid],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(404);
    }

    public function test_delete_soft_deletes_table(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));
        $zone = EloquentZone::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Terraza',
        ]);
        $table = EloquentTable::factory()->create([
            'restaurant_id' => $restaurant->id,
            'zone_id' => $zone->id,
            'name' => 'Mesa 1',
        ]);

        $response = $this->deleteJson(
            "/api/restaurants/{$restaurant->uuid}/tables/{$table->uuid}",
            [],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(204);
        $this->assertSoftDeleted('tables', ['uuid' => $table->uuid]);
    }

    public function test_post_allows_recreating_table_with_same_name_after_soft_delete(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));
        $zone = EloquentZone::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Terraza',
        ]);
        $original = EloquentTable::factory()->create([
            'restaurant_id' => $restaurant->id,
            'zone_id' => $zone->id,
            'name' => 'Mesa 1',
        ]);
        $original->delete();

        $response = $this->postJson(
            "/api/restaurants/{$restaurant->uuid}/tables",
            ['zone_id' => $zone->uuid, 'name' => 'Mesa 1'],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(201);
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
