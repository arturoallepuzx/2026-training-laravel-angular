<?php

declare(strict_types=1);

namespace App\Table\Domain\Interfaces;

use App\Shared\Domain\ValueObject\Uuid;
use App\Table\Domain\Entity\Table;
use App\Table\Domain\ValueObject\TableName;

interface TableRepositoryInterface
{
    public function create(Table $table): void;

    public function update(Table $table): void;

    public function findById(Uuid $id, Uuid $restaurantId): ?Table;

    public function findByNameAndZoneIdAndRestaurantId(
        TableName $name,
        Uuid $zoneId,
        Uuid $restaurantId,
    ): ?Table;

    /** @return Table[] */
    public function findAllByRestaurantId(Uuid $restaurantId): array;

    public function delete(Uuid $id, Uuid $restaurantId): void;
}
