<?php

declare(strict_types=1);

namespace Tests\Unit\Table;

use App\Shared\Domain\ValueObject\Uuid;
use App\Table\Domain\Entity\Table;
use App\Table\Domain\ValueObject\TableName;
use PHPUnit\Framework\TestCase;

class TableEntityTest extends TestCase
{
    public function test_ddd_create_builds_table_with_attributes(): void
    {
        $restaurantId = Uuid::generate();
        $zoneId = Uuid::generate();

        $table = Table::dddCreate(
            $restaurantId,
            $zoneId,
            TableName::create('Mesa 1'),
        );

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $table->id()->value()
        );
        $this->assertSame($restaurantId->value(), $table->restaurantId()->value());
        $this->assertSame($zoneId->value(), $table->zoneId()->value());
        $this->assertSame('Mesa 1', $table->name()->value());
    }

    public function test_updates_name(): void
    {
        $table = Table::dddCreate(
            Uuid::generate(),
            Uuid::generate(),
            TableName::create('Mesa 1'),
        );
        $previousUpdatedAt = $table->updatedAt()->value();

        $table->updateName(TableName::create('Mesa VIP'));

        $this->assertSame('Mesa VIP', $table->name()->value());
        $this->assertGreaterThanOrEqual($previousUpdatedAt, $table->updatedAt()->value());
    }

    public function test_updates_zone_id(): void
    {
        $originalZoneId = Uuid::generate();
        $newZoneId = Uuid::generate();
        $table = Table::dddCreate(
            Uuid::generate(),
            $originalZoneId,
            TableName::create('Mesa 1'),
        );
        $previousUpdatedAt = $table->updatedAt()->value();

        $table->updateZoneId($newZoneId);

        $this->assertSame($newZoneId->value(), $table->zoneId()->value());
        $this->assertGreaterThanOrEqual($previousUpdatedAt, $table->updatedAt()->value());
    }

    public function test_name_rejects_empty_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        TableName::create('   ');
    }

    public function test_name_rejects_too_long_value(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        TableName::create(str_repeat('a', 256));
    }
}
