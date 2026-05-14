<?php

declare(strict_types=1);

namespace App\Table\Application\GetTableById;

use App\Shared\Domain\ValueObject\Uuid;
use App\Table\Domain\Exception\TableNotFoundException;
use App\Table\Domain\Interfaces\TableRepositoryInterface;

class GetTableById
{
    public function __construct(
        private TableRepositoryInterface $tableRepository,
    ) {}

    public function __invoke(string $id, string $restaurantId): GetTableByIdResponse
    {
        $tableId = Uuid::create($id);

        $table = $this->tableRepository->findById(
            $tableId,
            Uuid::create($restaurantId),
        );

        if ($table === null) {
            throw TableNotFoundException::forId($tableId);
        }

        return GetTableByIdResponse::create($table);
    }
}
