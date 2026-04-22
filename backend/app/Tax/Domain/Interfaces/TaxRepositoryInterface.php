<?php

declare(strict_types=1);

namespace App\Tax\Domain\Interfaces;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tax\Domain\Entity\Tax;
use App\Tax\Domain\ValueObject\TaxName;

interface TaxRepositoryInterface
{
    public function create(Tax $tax): void;

    public function update(Tax $tax): void;

    public function findById(Uuid $id, Uuid $restaurantId): ?Tax;

    public function findByNameAndRestaurantId(TaxName $name, Uuid $restaurantId): ?Tax;

    /** @return Tax[] */
    public function findAllByRestaurantId(Uuid $restaurantId): array;

    public function delete(Uuid $id, Uuid $restaurantId): void;
}
