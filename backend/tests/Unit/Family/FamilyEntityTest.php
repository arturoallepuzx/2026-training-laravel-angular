<?php

declare(strict_types=1);

namespace Tests\Unit\Family;

use App\Family\Domain\Entity\Family;
use App\Family\Domain\ValueObject\FamilyName;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

class FamilyEntityTest extends TestCase
{
    public function test_ddd_create_builds_active_family_with_attributes(): void
    {
        $restaurantId = Uuid::generate();

        $family = Family::dddCreate(
            $restaurantId,
            FamilyName::create('Bebidas'),
        );

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $family->id()->value()
        );
        $this->assertSame($restaurantId->value(), $family->restaurantId()->value());
        $this->assertSame('Bebidas', $family->name()->value());
        $this->assertTrue($family->active());
    }

    public function test_updates_name_and_active_state(): void
    {
        $family = Family::dddCreate(
            Uuid::generate(),
            FamilyName::create('Bebidas'),
        );
        $previousUpdatedAt = $family->updatedAt()->value();

        $family->updateName(FamilyName::create('Postres'));
        $family->updateActive(false);

        $this->assertSame('Postres', $family->name()->value());
        $this->assertFalse($family->active());
        $this->assertGreaterThanOrEqual($previousUpdatedAt, $family->updatedAt()->value());
        $this->assertTrue($family->wasModified());
    }

    public function test_is_not_modified_after_creation(): void
    {
        $family = Family::dddCreate(Uuid::generate(), FamilyName::create('Bebidas'));

        $this->assertFalse($family->wasModified());
    }

    public function test_same_values_do_not_mark_as_modified(): void
    {
        $family = Family::dddCreate(Uuid::generate(), FamilyName::create('Bebidas'), true);

        $family->updateName(FamilyName::create('Bebidas'));
        $family->updateActive(true);

        $this->assertFalse($family->wasModified());
    }
}
