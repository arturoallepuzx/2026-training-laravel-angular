<?php

declare(strict_types=1);

namespace Tests\Unit\Tax;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tax\Domain\Entity\Tax;
use App\Tax\Domain\ValueObject\TaxName;
use App\Tax\Domain\ValueObject\TaxPercentage;
use PHPUnit\Framework\TestCase;

class TaxEntityTest extends TestCase
{
    public function test_ddd_create_builds_tax_with_attributes(): void
    {
        $restaurantId = Uuid::generate();

        $tax = Tax::dddCreate(
            $restaurantId,
            TaxName::create('IVA General'),
            TaxPercentage::create(21),
        );

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $tax->id()->value()
        );
        $this->assertSame($restaurantId->value(), $tax->restaurantId()->value());
        $this->assertSame('IVA General', $tax->name()->value());
        $this->assertSame(21, $tax->percentage()->value());
    }

    public function test_updates_name_and_percentage(): void
    {
        $tax = Tax::dddCreate(
            Uuid::generate(),
            TaxName::create('IVA General'),
            TaxPercentage::create(21),
        );
        $previousUpdatedAt = $tax->updatedAt()->value();

        $tax->updateName(TaxName::create('IVA Reducido'));
        $tax->updatePercentage(TaxPercentage::create(10));

        $this->assertSame('IVA Reducido', $tax->name()->value());
        $this->assertSame(10, $tax->percentage()->value());
        $this->assertGreaterThanOrEqual($previousUpdatedAt, $tax->updatedAt()->value());
        $this->assertTrue($tax->wasModified());
    }

    public function test_is_not_modified_after_creation(): void
    {
        $tax = Tax::dddCreate(Uuid::generate(), TaxName::create('IVA General'), TaxPercentage::create(21));

        $this->assertFalse($tax->wasModified());
    }

    public function test_same_values_do_not_mark_as_modified(): void
    {
        $tax = Tax::dddCreate(Uuid::generate(), TaxName::create('IVA General'), TaxPercentage::create(21));

        $tax->updateName(TaxName::create('IVA General'));
        $tax->updatePercentage(TaxPercentage::create(21));

        $this->assertFalse($tax->wasModified());
    }
}
