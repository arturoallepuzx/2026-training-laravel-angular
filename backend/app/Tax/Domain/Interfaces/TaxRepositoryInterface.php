<?php

namespace App\Tax\Domain\Interfaces;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tax\Domain\Entity\Tax;

interface TaxRepositoryInterface
{
    public function save(Tax $tax): void;

    public function findById(Uuid $id, Uuid $restaurantId): ?Tax;

    /** @return Tax[] */
    public function findAllByRestaurantId(Uuid $restaurantId): array;

    public function delete(Uuid $id, Uuid $restaurantId): void;
}
