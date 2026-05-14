<?php

declare(strict_types=1);

namespace App\Table\Application\ListTables;

use App\Shared\Domain\ValueObject\Uuid;
use App\Table\Domain\Interfaces\TableRepositoryInterface;

class ListTables
{
    public function __construct(
        private TableRepositoryInterface $tableRepository,
    ) {}

    public function __invoke(string $restaurantId): ListTablesResponse
    {
        $tables = $this->tableRepository->findAllByRestaurantId(Uuid::create($restaurantId));

        return ListTablesResponse::create($tables);
    }
}
