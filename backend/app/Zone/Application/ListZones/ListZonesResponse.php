<?php

declare(strict_types=1);

namespace App\Zone\Application\ListZones;

use App\Zone\Domain\Entity\Zone;

final readonly class ListZonesResponse
{
    /** @param array<int, array<string, mixed>> $zones */
    public function __construct(
        public array $zones,
    ) {}

    /** @param Zone[] $zones */
    public static function create(array $zones): self
    {
        return new self(
            zones: array_map(fn (Zone $zone) => [
                'id' => $zone->id()->value(),
                'restaurant_id' => $zone->restaurantId()->value(),
                'name' => $zone->name()->value(),
                'created_at' => $zone->createdAt()->format(\DateTimeInterface::ATOM),
                'updated_at' => $zone->updatedAt()->format(\DateTimeInterface::ATOM),
            ], $zones),
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function toArray(): array
    {
        return $this->zones;
    }
}
