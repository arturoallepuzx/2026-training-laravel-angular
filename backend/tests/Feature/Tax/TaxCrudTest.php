<?php

declare(strict_types=1);

namespace Tests\Feature\Tax;

use App\Auth\Domain\Interfaces\AccessTokenIssuerInterface;
use App\Auth\Domain\ValueObject\AccessTokenPayload;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\UserRole;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tax\Infrastructure\Persistence\Models\EloquentTax;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        JWT::$timestamp = null;
        parent::tearDown();
    }

    public function test_get_all_returns_only_taxes_for_authenticated_restaurant(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $otherRestaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));

        EloquentTax::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'IVA General',
            'percentage' => 21,
        ]);
        EloquentTax::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'IVA Reducido',
            'percentage' => 10,
        ]);
        EloquentTax::factory()->create([
            'restaurant_id' => $otherRestaurant->id,
            'name' => 'IGIC',
            'percentage' => 7,
        ]);

        $response = $this->getJson(
            "/api/restaurants/{$restaurant->uuid}/taxes",
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(200);
        $response->assertJsonCount(2);
        $response->assertJsonFragment(['name' => 'IVA General', 'percentage' => 21]);
        $response->assertJsonFragment(['name' => 'IVA Reducido', 'percentage' => 10]);
        $response->assertJsonMissing(['name' => 'IGIC']);
    }

    public function test_get_by_id_returns_tax_when_it_belongs_to_restaurant(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));
        $tax = EloquentTax::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'IVA General',
            'percentage' => 21,
        ]);

        $response = $this->getJson(
            "/api/restaurants/{$restaurant->uuid}/taxes/{$tax->uuid}",
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $tax->uuid,
            'restaurant_id' => $restaurant->uuid,
            'name' => 'IVA General',
            'percentage' => 21,
        ]);
    }

    public function test_get_by_id_returns_404_when_tax_belongs_to_other_restaurant(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $otherRestaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));
        $tax = EloquentTax::factory()->create([
            'restaurant_id' => $otherRestaurant->id,
            'name' => 'IGIC',
            'percentage' => 7,
        ]);

        $response = $this->getJson(
            "/api/restaurants/{$restaurant->uuid}/taxes/{$tax->uuid}",
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(404);
    }

    public function test_post_creates_tax_when_authenticated_as_admin(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));

        $response = $this->postJson(
            "/api/restaurants/{$restaurant->uuid}/taxes",
            ['name' => 'IVA General', 'percentage' => 21],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(201);
        $response->assertJson([
            'restaurant_id' => $restaurant->uuid,
            'name' => 'IVA General',
            'percentage' => 21,
        ]);
        $this->assertDatabaseHas('taxes', [
            'restaurant_id' => $restaurant->id,
            'name' => 'IVA General',
            'percentage' => 21,
            'deleted_at' => null,
        ]);
    }

    public function test_post_returns_422_for_invalid_payload(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));

        $response = $this->postJson(
            "/api/restaurants/{$restaurant->uuid}/taxes",
            ['name' => '', 'percentage' => 101],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'error',
            'details' => [
                'name',
                'percentage',
            ],
        ]);
    }

    public function test_post_returns_409_when_name_already_exists_in_restaurant(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));
        EloquentTax::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'IVA General',
            'percentage' => 21,
        ]);

        $response = $this->postJson(
            "/api/restaurants/{$restaurant->uuid}/taxes",
            ['name' => 'IVA General', 'percentage' => 10],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(409);
    }

    public function test_put_updates_tax_when_authenticated_as_admin(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));
        $tax = EloquentTax::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'IVA General',
            'percentage' => 21,
        ]);

        $response = $this->putJson(
            "/api/restaurants/{$restaurant->uuid}/taxes/{$tax->uuid}",
            ['name' => 'IVA Reducido', 'percentage' => 10],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $tax->uuid,
            'restaurant_id' => $restaurant->uuid,
            'name' => 'IVA Reducido',
            'percentage' => 10,
        ]);
        $this->assertDatabaseHas('taxes', [
            'uuid' => $tax->uuid,
            'name' => 'IVA Reducido',
            'percentage' => 10,
        ]);
    }

    public function test_put_returns_409_when_name_already_exists_in_restaurant(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));
        EloquentTax::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'IVA General',
            'percentage' => 21,
        ]);
        $target = EloquentTax::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'IVA Reducido',
            'percentage' => 10,
        ]);

        $response = $this->putJson(
            "/api/restaurants/{$restaurant->uuid}/taxes/{$target->uuid}",
            ['name' => 'IVA General', 'percentage' => 4],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(409);
    }

    public function test_delete_soft_deletes_tax_and_removes_it_from_list(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));
        $deletedTax = EloquentTax::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'IVA General',
            'percentage' => 21,
        ]);
        $remainingTax = EloquentTax::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'IVA Reducido',
            'percentage' => 10,
        ]);

        $deleteResponse = $this->deleteJson(
            "/api/restaurants/{$restaurant->uuid}/taxes/{$deletedTax->uuid}",
            [],
            ['Authorization' => 'Bearer '.$token],
        );

        $deleteResponse->assertStatus(204);
        $this->assertSoftDeleted('taxes', ['uuid' => $deletedTax->uuid]);

        $listResponse = $this->getJson(
            "/api/restaurants/{$restaurant->uuid}/taxes",
            ['Authorization' => 'Bearer '.$token],
        );

        $listResponse->assertStatus(200);
        $listResponse->assertJsonCount(1);
        $listResponse->assertJsonFragment(['id' => $remainingTax->uuid]);
        $listResponse->assertJsonMissing(['id' => $deletedTax->uuid]);
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
