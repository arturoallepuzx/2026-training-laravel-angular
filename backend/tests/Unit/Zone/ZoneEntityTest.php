<?php

declare(strict_types=1);

namespace Tests\Unit\Zone;

use App\Shared\Domain\ValueObject\Uuid;
use App\Zone\Domain\Entity\Zone;
use App\Zone\Domain\ValueObject\ZoneName;
use PHPUnit\Framework\TestCase;

class ZoneEntityTest extends TestCase
{
    public function test_ddd_create_builds_zone_with_attributes(): void
    {
        $restaurantId = Uuid::generate();

        $zone = Zone::dddCreate(
            $restaurantId,
            ZoneName::create('Terraza'),
        );

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $zone->id()->value()
        );
        $this->assertSame($restaurantId->value(), $zone->restaurantId()->value());
        $this->assertSame('Terraza', $zone->name()->value());
    }

    public function test_updates_name(): void
    {
        $zone = Zone::dddCreate(
            Uuid::generate(),
            ZoneName::create('Terraza'),
        );
        $previousUpdatedAt = $zone->updatedAt()->value();

        $zone->updateName(ZoneName::create('Salon Principal'));

        $this->assertSame('Salon Principal', $zone->name()->value());
        $this->assertGreaterThanOrEqual($previousUpdatedAt, $zone->updatedAt()->value());
        $this->assertTrue($zone->wasModified());
    }

    public function test_is_not_modified_after_creation(): void
    {
        $zone = Zone::dddCreate(Uuid::generate(), ZoneName::create('Terraza'));

        $this->assertFalse($zone->wasModified());
    }

    public function test_same_name_does_not_mark_as_modified(): void
    {
        $zone = Zone::dddCreate(Uuid::generate(), ZoneName::create('Terraza'));

        $zone->updateName(ZoneName::create('Terraza'));

        $this->assertFalse($zone->wasModified());
    }
}
