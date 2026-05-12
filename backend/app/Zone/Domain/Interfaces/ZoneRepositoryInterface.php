<?php

declare(strict_types=1);

namespace App\Zone\Domain\Interfaces;

use App\Shared\Domain\ValueObject\Uuid;
use App\Zone\Domain\Entity\Zone;
use App\Zone\Domain\ValueObject\ZoneName;

interface ZoneRepositoryInterface
{
    public function create(Zone $zone): void;

    public function update(Zone $zone): void;

    public function findById(Uuid $id, Uuid $restaurantId): ?Zone;

    public function findByNameAndRestaurantId(ZoneName $name, Uuid $restaurantId): ?Zone;

    /** @return Zone[] */
    public function findAllByRestaurantId(Uuid $restaurantId): array;

    public function delete(Uuid $id, Uuid $restaurantId): void;
}
