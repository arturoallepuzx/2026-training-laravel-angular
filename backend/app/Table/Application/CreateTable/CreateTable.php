<?php

declare(strict_types=1);

namespace App\Table\Application\CreateTable;

use App\Shared\Domain\ValueObject\Uuid;
use App\Table\Domain\Entity\Table;
use App\Table\Domain\Exception\TableNameAlreadyExistsException;
use App\Table\Domain\Exception\TableZoneNotFoundException;
use App\Table\Domain\Interfaces\TableRepositoryInterface;
use App\Table\Domain\Interfaces\TableZoneExistsCheckerInterface;
use App\Table\Domain\ValueObject\TableName;

class CreateTable
{
    public function __construct(
        private TableRepositoryInterface $tableRepository,
        private TableZoneExistsCheckerInterface $tableZoneExistsChecker,
    ) {}

    public function __invoke(string $restaurantId, string $zoneId, string $name): CreateTableResponse
    {
        $restaurantUuid = Uuid::create($restaurantId);
        $zoneUuid = Uuid::create($zoneId);
        $tableName = TableName::create($name);

        if (! $this->tableZoneExistsChecker->check($zoneUuid, $restaurantUuid)) {
            throw TableZoneNotFoundException::forId($zoneUuid);
        }

        if ($this->tableRepository->findByNameAndZoneIdAndRestaurantId($tableName, $zoneUuid, $restaurantUuid) !== null) {
            throw TableNameAlreadyExistsException::forName($tableName->value());
        }

        $table = Table::dddCreate(
            $restaurantUuid,
            $zoneUuid,
            $tableName,
        );

        $this->tableRepository->create($table);

        return CreateTableResponse::create($table);
    }
}
