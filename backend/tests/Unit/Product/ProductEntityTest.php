<?php

declare(strict_types=1);

namespace Tests\Unit\Product;

use App\Product\Domain\Entity\Product;
use App\Product\Domain\ValueObject\ProductImageSrc;
use App\Product\Domain\ValueObject\ProductName;
use App\Product\Domain\ValueObject\ProductPrice;
use App\Product\Domain\ValueObject\ProductStock;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

class ProductEntityTest extends TestCase
{
    public function test_ddd_create_builds_active_product_with_attributes(): void
    {
        $restaurantId = Uuid::generate();
        $familyId = Uuid::generate();
        $taxId = Uuid::generate();

        $product = Product::dddCreate(
            $restaurantId,
            $familyId,
            $taxId,
            ProductImageSrc::create('/images/cafe.png'),
            ProductName::create('Cafe solo'),
            ProductPrice::create(150),
            ProductStock::create(20),
        );

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $product->id()->value()
        );
        $this->assertSame($restaurantId->value(), $product->restaurantId()->value());
        $this->assertSame($familyId->value(), $product->familyId()->value());
        $this->assertSame($taxId->value(), $product->taxId()->value());
        $this->assertSame('/images/cafe.png', $product->imageSrc()?->value());
        $this->assertSame('Cafe solo', $product->name()->value());
        $this->assertSame(150, $product->price()->value());
        $this->assertSame(20, $product->stock()->value());
        $this->assertTrue($product->active());
    }

    public function test_updates_catalog_fields_and_active_state(): void
    {
        $product = Product::dddCreate(
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            ProductImageSrc::create('/images/cafe.png'),
            ProductName::create('Cafe solo'),
            ProductPrice::create(150),
            ProductStock::create(20),
        );
        $newFamilyId = Uuid::generate();
        $newTaxId = Uuid::generate();
        $previousUpdatedAt = $product->updatedAt()->value();

        $product->updateFamilyId($newFamilyId);
        $product->updateTaxId($newTaxId);
        $product->updateImageSrc(null);
        $product->updateName(ProductName::create('Cafe con leche'));
        $product->updatePrice(ProductPrice::create(190));
        $product->updateStock(ProductStock::create(8));
        $product->updateActive(false);

        $this->assertSame($newFamilyId->value(), $product->familyId()->value());
        $this->assertSame($newTaxId->value(), $product->taxId()->value());
        $this->assertNull($product->imageSrc());
        $this->assertSame('Cafe con leche', $product->name()->value());
        $this->assertSame(190, $product->price()->value());
        $this->assertSame(8, $product->stock()->value());
        $this->assertFalse($product->active());
        $this->assertGreaterThanOrEqual($previousUpdatedAt, $product->updatedAt()->value());
        $this->assertTrue($product->wasModified());
    }

    public function test_is_not_modified_after_creation(): void
    {
        $product = $this->buildProduct();

        $this->assertFalse($product->wasModified());
    }

    public function test_same_values_do_not_mark_as_modified(): void
    {
        $product = $this->buildProduct();

        $product->updateName(ProductName::create('Cafe solo'));
        $product->updatePrice(ProductPrice::create(150));
        $product->updateStock(ProductStock::create(20));
        $product->updateActive(true);
        $product->updateImageSrc(ProductImageSrc::create('/images/cafe.png'));

        $this->assertFalse($product->wasModified());
    }

    private function buildProduct(): Product
    {
        return Product::dddCreate(
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            ProductImageSrc::create('/images/cafe.png'),
            ProductName::create('Cafe solo'),
            ProductPrice::create(150),
            ProductStock::create(20),
        );
    }
}
