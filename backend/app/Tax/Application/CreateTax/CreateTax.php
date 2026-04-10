<?php

namespace App\Tax\Application\CreateTax;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tax\Domain\Entity\Tax;
use App\Tax\Domain\Interfaces\TaxRepositoryInterface;
use App\Tax\Domain\ValueObject\TaxName;
use App\Tax\Domain\ValueObject\TaxPercentage;

class CreateTax
{
    public function __construct(
        private TaxRepositoryInterface $taxRepository,
    ) {}

    public function __invoke(string $restaurantId, string $name, int $percentage): CreateTaxResponse
    {
        $tax = Tax::dddCreate(
            Uuid::create($restaurantId),
            TaxName::create($name),
            TaxPercentage::create($percentage),
        );

        $this->taxRepository->save($tax);

        return CreateTaxResponse::create($tax);
    }
}
