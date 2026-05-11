<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use App\Auth\Domain\Interfaces\AccessTokenIssuerInterface;
use App\Auth\Domain\ValueObject\AccessTokenPayload;
use App\Family\Infrastructure\Persistence\Models\EloquentFamily;
use App\Product\Infrastructure\Persistence\Models\EloquentProduct;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\UserRole;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tax\Infrastructure\Persistence\Models\EloquentTax;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        JWT::$timestamp = null;
        parent::tearDown();
    }

    public function test_get_all_returns_only_products_for_authenticated_restaurant(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $otherRestaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));
        [$family, $tax] = $this->createFamilyAndTaxFor($restaurant);
        [$otherFamily, $otherTax] = $this->createFamilyAndTaxFor($otherRestaurant);

        EloquentProduct::factory()->create([
            'restaurant_id' => $restaurant->id,
            'family_id' => $family->id,
            'tax_id' => $tax->id,
            'name' => 'Cafe solo',
            'price' => 150,
            'stock' => 20,
            'active' => true,
        ]);
        EloquentProduct::factory()->create([
            'restaurant_id' => $restaurant->id,
            'family_id' => $family->id,
            'tax_id' => $tax->id,
            'name' => 'Cafe con leche',
            'price' => 190,
            'stock' => 10,
            'active' => false,
        ]);
        EloquentProduct::factory()->create([
            'restaurant_id' => $otherRestaurant->id,
            'family_id' => $otherFamily->id,
            'tax_id' => $otherTax->id,
            'name' => 'Other Product',
        ]);

        $response = $this->getJson(
            "/api/restaurants/{$restaurant->uuid}/products",
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(200);
        $response->assertJsonCount(2);
        $response->assertJsonFragment(['name' => 'Cafe solo', 'price' => 150, 'stock' => 20, 'active' => true]);
        $response->assertJsonFragment(['name' => 'Cafe con leche', 'price' => 190, 'stock' => 10, 'active' => false]);
        $response->assertJsonMissing(['name' => 'Other Product']);
    }

    public function test_get_by_id_returns_product_when_it_belongs_to_restaurant(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));
        [$family, $tax] = $this->createFamilyAndTaxFor($restaurant);
        $product = EloquentProduct::factory()->create([
            'restaurant_id' => $restaurant->id,
            'family_id' => $family->id,
            'tax_id' => $tax->id,
            'image_src' => '/images/cafe.png',
            'name' => 'Cafe solo',
            'price' => 150,
            'stock' => 20,
            'active' => true,
        ]);

        $response = $this->getJson(
            "/api/restaurants/{$restaurant->uuid}/products/{$product->uuid}",
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $product->uuid,
            'restaurant_id' => $restaurant->uuid,
            'family_id' => $family->uuid,
            'tax_id' => $tax->uuid,
            'image_src' => '/images/cafe.png',
            'name' => 'Cafe solo',
            'price' => 150,
            'stock' => 20,
            'active' => true,
        ]);
    }

    public function test_get_by_id_returns_404_when_product_belongs_to_other_restaurant(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $otherRestaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));
        [$family, $tax] = $this->createFamilyAndTaxFor($otherRestaurant);
        $product = EloquentProduct::factory()->create([
            'restaurant_id' => $otherRestaurant->id,
            'family_id' => $family->id,
            'tax_id' => $tax->id,
            'name' => 'Other Product',
        ]);

        $response = $this->getJson(
            "/api/restaurants/{$restaurant->uuid}/products/{$product->uuid}",
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(404);
    }

    public function test_post_creates_product_when_authenticated_as_admin(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));
        [$family, $tax] = $this->createFamilyAndTaxFor($restaurant);

        $response = $this->postJson(
            "/api/restaurants/{$restaurant->uuid}/products",
            [
                'family_id' => $family->uuid,
                'tax_id' => $tax->uuid,
                'image_src' => '/images/cafe.png',
                'name' => 'Cafe solo',
                'price' => 150,
                'stock' => 20,
                'active' => true,
            ],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(201);
        $response->assertJson([
            'restaurant_id' => $restaurant->uuid,
            'family_id' => $family->uuid,
            'tax_id' => $tax->uuid,
            'image_src' => '/images/cafe.png',
            'name' => 'Cafe solo',
            'price' => 150,
            'stock' => 20,
            'active' => true,
        ]);
        $this->assertDatabaseHas('products', [
            'restaurant_id' => $restaurant->id,
            'family_id' => $family->id,
            'tax_id' => $tax->id,
            'image_src' => '/images/cafe.png',
            'name' => 'Cafe solo',
            'price' => 150,
            'stock' => 20,
            'active' => true,
            'deleted_at' => null,
        ]);
    }

    public function test_post_returns_422_for_invalid_payload(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));

        $response = $this->postJson(
            "/api/restaurants/{$restaurant->uuid}/products",
            [
                'family_id' => 'not-a-uuid',
                'tax_id' => 'not-a-uuid',
                'name' => '',
                'price' => -1,
                'stock' => -1,
                'active' => 'not-boolean',
            ],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'error',
            'details' => [
                'family_id',
                'tax_id',
                'name',
                'price',
                'stock',
                'active',
            ],
        ]);
    }

    public function test_post_returns_404_when_references_belong_to_other_restaurant(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $otherRestaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));
        [$family, $tax] = $this->createFamilyAndTaxFor($otherRestaurant);

        $response = $this->postJson(
            "/api/restaurants/{$restaurant->uuid}/products",
            [
                'family_id' => $family->uuid,
                'tax_id' => $tax->uuid,
                'name' => 'Cafe solo',
                'price' => 150,
                'stock' => 20,
            ],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(404);
    }

    public function test_post_returns_409_when_name_already_exists_in_restaurant(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));
        [$family, $tax] = $this->createFamilyAndTaxFor($restaurant);
        EloquentProduct::factory()->create([
            'restaurant_id' => $restaurant->id,
            'family_id' => $family->id,
            'tax_id' => $tax->id,
            'name' => 'Cafe solo',
        ]);

        $response = $this->postJson(
            "/api/restaurants/{$restaurant->uuid}/products",
            [
                'family_id' => $family->uuid,
                'tax_id' => $tax->uuid,
                'name' => 'Cafe solo',
                'price' => 150,
                'stock' => 20,
            ],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(409);
    }

    public function test_put_updates_product_when_authenticated_as_admin(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));
        [$family, $tax] = $this->createFamilyAndTaxFor($restaurant);
        [$newFamily, $newTax] = $this->createFamilyAndTaxFor($restaurant);
        $product = EloquentProduct::factory()->create([
            'restaurant_id' => $restaurant->id,
            'family_id' => $family->id,
            'tax_id' => $tax->id,
            'image_src' => '/images/cafe.png',
            'name' => 'Cafe solo',
            'price' => 150,
            'stock' => 20,
            'active' => true,
        ]);

        $response = $this->putJson(
            "/api/restaurants/{$restaurant->uuid}/products/{$product->uuid}",
            [
                'family_id' => $newFamily->uuid,
                'tax_id' => $newTax->uuid,
                'image_src' => null,
                'name' => 'Cafe con leche',
                'price' => 190,
                'stock' => 8,
                'active' => false,
            ],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $product->uuid,
            'restaurant_id' => $restaurant->uuid,
            'family_id' => $newFamily->uuid,
            'tax_id' => $newTax->uuid,
            'image_src' => null,
            'name' => 'Cafe con leche',
            'price' => 190,
            'stock' => 8,
            'active' => false,
        ]);
        $this->assertDatabaseHas('products', [
            'uuid' => $product->uuid,
            'family_id' => $newFamily->id,
            'tax_id' => $newTax->id,
            'image_src' => null,
            'name' => 'Cafe con leche',
            'price' => 190,
            'stock' => 8,
            'active' => false,
        ]);
    }

    public function test_put_returns_409_when_name_already_exists_in_restaurant(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));
        [$family, $tax] = $this->createFamilyAndTaxFor($restaurant);
        EloquentProduct::factory()->create([
            'restaurant_id' => $restaurant->id,
            'family_id' => $family->id,
            'tax_id' => $tax->id,
            'name' => 'Cafe solo',
        ]);
        $target = EloquentProduct::factory()->create([
            'restaurant_id' => $restaurant->id,
            'family_id' => $family->id,
            'tax_id' => $tax->id,
            'name' => 'Cafe con leche',
        ]);

        $response = $this->putJson(
            "/api/restaurants/{$restaurant->uuid}/products/{$target->uuid}",
            ['name' => 'Cafe solo'],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(409);
    }

    public function test_delete_soft_deletes_product_and_removes_it_from_list(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $token = $this->issueAdminToken(Uuid::create($restaurant->uuid));
        [$family, $tax] = $this->createFamilyAndTaxFor($restaurant);
        $deletedProduct = EloquentProduct::factory()->create([
            'restaurant_id' => $restaurant->id,
            'family_id' => $family->id,
            'tax_id' => $tax->id,
            'name' => 'Cafe solo',
        ]);
        $remainingProduct = EloquentProduct::factory()->create([
            'restaurant_id' => $restaurant->id,
            'family_id' => $family->id,
            'tax_id' => $tax->id,
            'name' => 'Cafe con leche',
        ]);

        $deleteResponse = $this->deleteJson(
            "/api/restaurants/{$restaurant->uuid}/products/{$deletedProduct->uuid}",
            [],
            ['Authorization' => 'Bearer '.$token],
        );

        $deleteResponse->assertStatus(204);
        $this->assertSoftDeleted('products', ['uuid' => $deletedProduct->uuid]);

        $listResponse = $this->getJson(
            "/api/restaurants/{$restaurant->uuid}/products",
            ['Authorization' => 'Bearer '.$token],
        );

        $listResponse->assertStatus(200);
        $listResponse->assertJsonCount(1);
        $listResponse->assertJsonFragment(['id' => $remainingProduct->uuid]);
        $listResponse->assertJsonMissing(['id' => $deletedProduct->uuid]);
    }

    /** @return array{0: EloquentFamily, 1: EloquentTax} */
    private function createFamilyAndTaxFor(EloquentRestaurant $restaurant): array
    {
        $family = EloquentFamily::factory()->create([
            'restaurant_id' => $restaurant->id,
        ]);
        $tax = EloquentTax::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'IVA '.$restaurant->id.' '.Uuid::generate()->value(),
        ]);

        return [$family, $tax];
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
