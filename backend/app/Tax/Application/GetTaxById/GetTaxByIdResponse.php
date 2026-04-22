<?php

declare(strict_types=1);

namespace App\Tax\Application\GetTaxById;

use App\Tax\Domain\Entity\Tax;

final readonly class GetTaxByIdResponse
{
    public function __construct(
        public string $id,
        public string $restaurantId,
        public string $name,
        public int $percentage,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function create(Tax $tax): self
    {
        return new self(
            id: $tax->id()->value(),
            restaurantId: $tax->restaurantId()->value(),
            name: $tax->name()->value(),
            percentage: $tax->percentage()->value(),
            createdAt: $tax->createdAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $tax->updatedAt()->format(\DateTimeInterface::ATOM),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'restaurant_id' => $this->restaurantId,
            'name' => $this->name,
            'percentage' => $this->percentage,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
