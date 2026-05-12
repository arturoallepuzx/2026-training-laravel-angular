<?php

declare(strict_types=1);

namespace App\Zone\Application\UpdateZone;

use App\Zone\Domain\Entity\Zone;

final readonly class UpdateZoneResponse
{
    public function __construct(
        public string $id,
        public string $restaurantId,
        public string $name,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function create(Zone $zone): self
    {
        return new self(
            id: $zone->id()->value(),
            restaurantId: $zone->restaurantId()->value(),
            name: $zone->name()->value(),
            createdAt: $zone->createdAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $zone->updatedAt()->format(\DateTimeInterface::ATOM),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'restaurant_id' => $this->restaurantId,
            'name' => $this->name,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
