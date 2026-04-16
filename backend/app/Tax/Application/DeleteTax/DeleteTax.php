<?php

namespace App\Tax\Application\DeleteTax;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tax\Domain\Exception\TaxNotFoundException;
use App\Tax\Domain\Interfaces\TaxRepositoryInterface;

class DeleteTax
{
    public function __construct(
        private TaxRepositoryInterface $taxRepository,
    ) {}

    public function __invoke(string $id, string $restaurantId): void
    {
        $idVO = Uuid::create($id);
        $restaurantIdVO = Uuid::create($restaurantId);

        $tax = $this->taxRepository->findById($idVO, $restaurantIdVO);

        if ($tax === null) {
            throw TaxNotFoundException::forId($idVO);
        }

        $this->taxRepository->delete($idVO, $restaurantIdVO);
    }
}
