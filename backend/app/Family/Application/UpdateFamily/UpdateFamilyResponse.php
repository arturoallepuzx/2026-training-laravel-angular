<?php

declare(strict_types=1);

namespace App\Family\Application\UpdateFamily;

use App\Family\Domain\Entity\Family;

final readonly class UpdateFamilyResponse
{
    public function __construct(
        public string $id,
        public string $restaurantId,
        public string $name,
        public bool $active,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function create(Family $family): self
    {
        return new self(
            id: $family->id()->value(),
            restaurantId: $family->restaurantId()->value(),
            name: $family->name()->value(),
            active: $family->active(),
            createdAt: $family->createdAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $family->updatedAt()->format(\DateTimeInterface::ATOM),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'restaurant_id' => $this->restaurantId,
            'name' => $this->name,
            'active' => $this->active,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
