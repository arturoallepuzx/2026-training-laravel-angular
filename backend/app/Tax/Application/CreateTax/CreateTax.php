<?php

declare(strict_types=1);

namespace App\Tax\Application\CreateTax;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tax\Domain\Entity\Tax;
use App\Tax\Domain\Exception\TaxNameAlreadyExistsException;
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
        $restaurantUuid = Uuid::create($restaurantId);
        $taxName = TaxName::create($name);
        $taxPercentage = TaxPercentage::create($percentage);

        if ($this->taxRepository->findByNameAndRestaurantId($taxName, $restaurantUuid) !== null) {
            throw TaxNameAlreadyExistsException::forName($taxName->value());
        }

        $tax = Tax::dddCreate(
            $restaurantUuid,
            $taxName,
            $taxPercentage,
        );

        $this->taxRepository->create($tax);

        return CreateTaxResponse::create($tax);
    }
}
