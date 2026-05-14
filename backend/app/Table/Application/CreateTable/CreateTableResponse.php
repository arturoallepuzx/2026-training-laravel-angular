<?php

declare(strict_types=1);

namespace App\Table\Application\CreateTable;

use App\Table\Domain\Entity\Table;

final readonly class CreateTableResponse
{
    public function __construct(
        public string $id,
        public string $restaurantId,
        public string $zoneId,
        public string $name,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function create(Table $table): self
    {
        return new self(
            id: $table->id()->value(),
            restaurantId: $table->restaurantId()->value(),
            zoneId: $table->zoneId()->value(),
            name: $table->name()->value(),
            createdAt: $table->createdAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $table->updatedAt()->format(\DateTimeInterface::ATOM),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'restaurant_id' => $this->restaurantId,
            'zone_id' => $this->zoneId,
            'name' => $this->name,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
