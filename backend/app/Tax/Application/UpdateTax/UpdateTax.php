<?php

namespace App\Tax\Application\UpdateTax;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tax\Domain\Interfaces\TaxRepositoryInterface;
use App\Tax\Domain\ValueObject\TaxName;
use App\Tax\Domain\ValueObject\TaxPercentage;

class UpdateTax
{
    public function __construct(
        private TaxRepositoryInterface $taxRepository,
    ) {}

    public function __invoke(string $id, string $restaurantId, ?string $name, ?int $percentage): UpdateTaxResponse
    {
        $tax = $this->taxRepository->findById(
            Uuid::create($id),
            Uuid::create($restaurantId),
        );

        if ($tax === null) {
            throw new \DomainException('Tax not found.');
        }

        if ($name !== null) {
            $tax->updateName(TaxName::create($name));
        }

        if ($percentage !== null) {
            $tax->updatePercentage(TaxPercentage::create($percentage));
        }

        $this->taxRepository->save($tax);

        return UpdateTaxResponse::create($tax);
    }
}
