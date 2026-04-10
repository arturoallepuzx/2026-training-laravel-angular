<?php

namespace App\Tax\Application\GetAllTaxes;

use App\Tax\Domain\Entity\Tax;

final readonly class GetAllTaxesResponse
{
    /** @param array<int, array<string, mixed>> $taxes */
    public function __construct(
        public array $taxes,
    ) {}

    /** @param Tax[] $taxes */
    public static function create(array $taxes): self
    {
        return new self(
            taxes: array_map(fn (Tax $tax) => [
                'id' => $tax->id()->value(),
                'restaurant_id' => $tax->restaurantId()->value(),
                'name' => $tax->name()->value(),
                'percentage' => $tax->percentage()->value(),
                'created_at' => $tax->createdAt()->format(\DateTimeInterface::ATOM),
                'updated_at' => $tax->updatedAt()->format(\DateTimeInterface::ATOM),
            ], $taxes),
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function toArray(): array
    {
        return $this->taxes;
    }
}
