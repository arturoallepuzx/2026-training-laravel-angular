<?php

declare(strict_types=1);

namespace App\Family\Domain\Interfaces;

use App\Family\Domain\Entity\Family;
use App\Family\Domain\ValueObject\FamilyName;
use App\Shared\Domain\ValueObject\Uuid;

interface FamilyRepositoryInterface
{
    public function create(Family $family): void;

    public function update(Family $family): void;

    public function findById(Uuid $id, Uuid $restaurantId): ?Family;

    public function findByNameAndRestaurantId(FamilyName $name, Uuid $restaurantId): ?Family;

    /** @return Family[] */
    public function findAllByRestaurantId(Uuid $restaurantId): array;

    public function delete(Uuid $id, Uuid $restaurantId): void;
}
