<?php

declare(strict_types=1);

namespace Tests\Unit\Shared;

use App\Family\Domain\Entity\Family;
use App\Family\Domain\ValueObject\FamilyName;
use App\Product\Domain\Entity\Product;
use App\Product\Domain\ValueObject\ProductName;
use App\Product\Domain\ValueObject\ProductPrice;
use App\Product\Domain\ValueObject\ProductStock;
use App\Restaurant\Domain\Entity\Restaurant;
use App\Restaurant\Domain\ValueObject\LegalName;
use App\Restaurant\Domain\ValueObject\RestaurantName;
use App\Restaurant\Domain\ValueObject\TaxId;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\Uuid;
use App\Table\Domain\Entity\Table;
use App\Table\Domain\ValueObject\TableName;
use App\Tax\Domain\Entity\Tax;
use App\Tax\Domain\ValueObject\TaxName;
use App\Tax\Domain\ValueObject\TaxPercentage;
use App\Zone\Domain\Entity\Zone;
use App\Zone\Domain\ValueObject\ZoneName;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class NameSemanticsTest extends TestCase
{
    /**
     * @param  class-string  $nameClass
     */
    #[DataProvider('nameValueObjects')]
    public function test_equals_uses_canonical_business_equality(string $nameClass, string $currentValue, string $newValue): void
    {
        $currentName = $nameClass::create($currentValue);
        $newName = $nameClass::create($newValue);

        $this->assertTrue($currentName->equals($newName));
    }

    public function test_case_only_name_changes_mark_entities_as_modified(): void
    {
        $family = Family::dddCreate(Uuid::generate(), FamilyName::create('bebidas'));
        $family->updateName(FamilyName::create('Bebidas'));
        $this->assertTrue($family->wasModified());

        $product = Product::dddCreate(
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            null,
            ProductName::create('cafe solo'),
            ProductPrice::create(150),
            ProductStock::create(20),
        );
        $product->updateName(ProductName::create('Cafe Solo'));
        $this->assertTrue($product->wasModified());

        $restaurant = Restaurant::dddCreate(
            RestaurantName::create('bistro central'),
            LegalName::create('Bistro Central SL'),
            TaxId::create('B12345678'),
            Email::create('owner@bistro.test'),
        );
        $restaurant->updateName(RestaurantName::create('Bistro Central'));
        $this->assertTrue($restaurant->wasModified());

        $table = Table::dddCreate(Uuid::generate(), Uuid::generate(), TableName::create('mesa vip'));
        $table->updateName(TableName::create('Mesa VIP'));
        $this->assertTrue($table->wasModified());

        $tax = Tax::dddCreate(Uuid::generate(), TaxName::create('iva general'), TaxPercentage::create(21));
        $tax->updateName(TaxName::create('IVA General'));
        $this->assertTrue($tax->wasModified());

        $zone = Zone::dddCreate(Uuid::generate(), ZoneName::create('salon principal'));
        $zone->updateName(ZoneName::create('Salon Principal'));
        $this->assertTrue($zone->wasModified());
    }

    /**
     * @return array<string, array{0: class-string, 1: string, 2: string}>
     */
    public static function nameValueObjects(): array
    {
        return [
            'family name' => [FamilyName::class, 'bebidas', 'Bebidas'],
            'product name' => [ProductName::class, 'cafe solo', 'Cafe Solo'],
            'restaurant name' => [RestaurantName::class, 'bistro central', 'Bistro Central'],
            'table name' => [TableName::class, 'mesa vip', 'Mesa VIP'],
            'tax name' => [TaxName::class, 'iva general', 'IVA General'],
            'zone name' => [ZoneName::class, 'salon principal', 'Salon Principal'],
        ];
    }
}
