<?php

declare(strict_types=1);

namespace App\Table\Application\UpdateTable;

use App\Shared\Domain\ValueObject\Uuid;
use App\Table\Domain\Exception\TableNameAlreadyExistsException;
use App\Table\Domain\Exception\TableNotFoundException;
use App\Table\Domain\Exception\TableZoneNotFoundException;
use App\Table\Domain\Interfaces\TableRepositoryInterface;
use App\Table\Domain\Interfaces\TableZoneExistsCheckerInterface;
use App\Table\Domain\ValueObject\TableName;

class UpdateTable
{
    public function __construct(
        private TableRepositoryInterface $tableRepository,
        private TableZoneExistsCheckerInterface $tableZoneExistsChecker,
    ) {}

    public function __invoke(
        string $id,
        string $restaurantId,
        ?string $zoneId,
        ?string $name,
    ): UpdateTableResponse {
        $tableId = Uuid::create($id);
        $restaurantUuid = Uuid::create($restaurantId);

        $table = $this->tableRepository->findById($tableId, $restaurantUuid);

        if ($table === null) {
            throw TableNotFoundException::forId($tableId);
        }

        $targetZoneId = $table->zoneId();
        $targetName = $table->name();

        if ($zoneId !== null) {
            $newZoneId = Uuid::create($zoneId);

            if (! $this->tableZoneExistsChecker->check($newZoneId, $restaurantUuid)) {
                throw TableZoneNotFoundException::forId($newZoneId);
            }

            $targetZoneId = $newZoneId;
        }

        if ($name !== null) {
            $targetName = TableName::create($name);
        }

        $zoneChanged = $targetZoneId->value() !== $table->zoneId()->value();
        $nameChanged = ! $targetName->equals($table->name());

        if ($zoneChanged || $nameChanged) {
            $existing = $this->tableRepository->findByNameAndZoneIdAndRestaurantId(
                $targetName,
                $targetZoneId,
                $restaurantUuid,
            );

            if ($existing !== null && $existing->id()->value() !== $table->id()->value()) {
                throw TableNameAlreadyExistsException::forName($targetName->value());
            }

            if ($zoneChanged) {
                $table->updateZoneId($targetZoneId);
            }

            if ($nameChanged) {
                $table->updateName($targetName);
            }
        }

        $this->tableRepository->update($table);

        return UpdateTableResponse::create($table);
    }
}
