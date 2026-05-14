<?php

declare(strict_types=1);

namespace App\Table\Application\ListTables;

use App\Table\Domain\Entity\Table;

final readonly class ListTablesResponse
{
    /** @param array<int, array<string, mixed>> $tables */
    public function __construct(
        public array $tables,
    ) {}

    /** @param Table[] $tables */
    public static function create(array $tables): self
    {
        return new self(
            tables: array_map(fn (Table $table) => [
                'id' => $table->id()->value(),
                'restaurant_id' => $table->restaurantId()->value(),
                'zone_id' => $table->zoneId()->value(),
                'name' => $table->name()->value(),
                'created_at' => $table->createdAt()->format(\DateTimeInterface::ATOM),
                'updated_at' => $table->updatedAt()->format(\DateTimeInterface::ATOM),
            ], $tables),
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function toArray(): array
    {
        return $this->tables;
    }
}
