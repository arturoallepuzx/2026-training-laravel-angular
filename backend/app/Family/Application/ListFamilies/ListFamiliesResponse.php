<?php

declare(strict_types=1);

namespace App\Family\Application\ListFamilies;

use App\Family\Domain\Entity\Family;

final readonly class ListFamiliesResponse
{
    /** @param array<int, array<string, mixed>> $families */
    public function __construct(
        public array $families,
    ) {}

    /** @param Family[] $families */
    public static function create(array $families): self
    {
        return new self(
            families: array_map(fn (Family $family) => [
                'id' => $family->id()->value(),
                'restaurant_id' => $family->restaurantId()->value(),
                'name' => $family->name()->value(),
                'active' => $family->active(),
                'created_at' => $family->createdAt()->format(\DateTimeInterface::ATOM),
                'updated_at' => $family->updatedAt()->format(\DateTimeInterface::ATOM),
            ], $families),
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function toArray(): array
    {
        return $this->families;
    }
}
