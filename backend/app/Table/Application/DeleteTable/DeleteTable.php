<?php

declare(strict_types=1);

namespace App\Table\Application\DeleteTable;

use App\Shared\Domain\ValueObject\Uuid;
use App\Table\Domain\Exception\TableNotFoundException;
use App\Table\Domain\Interfaces\TableRepositoryInterface;

class DeleteTable
{
    public function __construct(
        private TableRepositoryInterface $tableRepository,
    ) {}

    public function __invoke(string $id, string $restaurantId): void
    {
        $idVO = Uuid::create($id);
        $restaurantIdVO = Uuid::create($restaurantId);

        $table = $this->tableRepository->findById($idVO, $restaurantIdVO);

        if ($table === null) {
            throw TableNotFoundException::forId($idVO);
        }

        $this->tableRepository->delete($idVO, $restaurantIdVO);
    }
}
