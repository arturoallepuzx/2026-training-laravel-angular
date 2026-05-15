<?php

declare(strict_types=1);

namespace Tests\Unit\Restaurant;

use App\Restaurant\Domain\Entity\Restaurant;
use App\Restaurant\Domain\ValueObject\LegalName;
use App\Restaurant\Domain\ValueObject\RestaurantName;
use App\Restaurant\Domain\ValueObject\TaxId;
use App\Shared\Domain\ValueObject\Email;
use PHPUnit\Framework\TestCase;

class RestaurantEntityTest extends TestCase
{
    public function test_ddd_create_builds_restaurant_with_attributes(): void
    {
        $restaurant = $this->buildRestaurant();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $restaurant->id()->value()
        );
        $this->assertSame('Bistro Central', $restaurant->name()->value());
        $this->assertSame('Bistro Central SL', $restaurant->legalName()->value());
        $this->assertSame('B12345678', $restaurant->taxId()->value());
        $this->assertSame('owner@bistro.test', $restaurant->email()->value());
    }

    public function test_updates_fields(): void
    {
        $restaurant = $this->buildRestaurant();
        $previousUpdatedAt = $restaurant->updatedAt()->value();

        $restaurant->updateName(RestaurantName::create('Bistro Norte'));
        $restaurant->updateLegalName(LegalName::create('Bistro Norte SL'));
        $restaurant->updateTaxId(TaxId::create('B87654321'));
        $restaurant->updateEmail(Email::create('norte@bistro.test'));

        $this->assertSame('Bistro Norte', $restaurant->name()->value());
        $this->assertSame('Bistro Norte SL', $restaurant->legalName()->value());
        $this->assertSame('B87654321', $restaurant->taxId()->value());
        $this->assertSame('norte@bistro.test', $restaurant->email()->value());
        $this->assertGreaterThanOrEqual($previousUpdatedAt, $restaurant->updatedAt()->value());
        $this->assertTrue($restaurant->wasModified());
    }

    public function test_is_not_modified_after_creation(): void
    {
        $this->assertFalse($this->buildRestaurant()->wasModified());
    }

    public function test_same_values_do_not_mark_as_modified(): void
    {
        $restaurant = $this->buildRestaurant();

        $restaurant->updateName(RestaurantName::create('Bistro Central'));
        $restaurant->updateLegalName(LegalName::create('Bistro Central SL'));
        $restaurant->updateTaxId(TaxId::create('B12345678'));
        $restaurant->updateEmail(Email::create('owner@bistro.test'));

        $this->assertFalse($restaurant->wasModified());
    }

    private function buildRestaurant(): Restaurant
    {
        return Restaurant::dddCreate(
            RestaurantName::create('Bistro Central'),
            LegalName::create('Bistro Central SL'),
            TaxId::create('B12345678'),
            Email::create('owner@bistro.test'),
        );
    }
}
