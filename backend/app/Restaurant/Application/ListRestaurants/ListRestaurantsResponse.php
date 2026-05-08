<?php

declare(strict_types=1);

namespace App\Restaurant\Application\ListRestaurants;

use App\Restaurant\Domain\Entity\Restaurant;

final readonly class ListRestaurantsResponse
{
    /** @param array<int, array<string, mixed>> $restaurants */
    public function __construct(
        public array $restaurants,
    ) {}

    /** @param Restaurant[] $restaurants */
    public static function create(array $restaurants): self
    {
        return new self(
            restaurants: array_map(fn (Restaurant $restaurant): array => [
                'id' => $restaurant->id()->value(),
                'name' => $restaurant->name()->value(),
                'legal_name' => $restaurant->legalName()->value(),
                'tax_id' => $restaurant->taxId()->value(),
                'email' => $restaurant->email()->value(),
                'created_at' => $restaurant->createdAt()->format(\DateTimeInterface::ATOM),
                'updated_at' => $restaurant->updatedAt()->format(\DateTimeInterface::ATOM),
            ], $restaurants),
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function toArray(): array
    {
        return $this->restaurants;
    }
}
