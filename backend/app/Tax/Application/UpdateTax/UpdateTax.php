<?php

namespace App\Tax\Application\UpdateTax;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tax\Domain\Exception\TaxNameAlreadyExistsException;
use App\Tax\Domain\Exception\TaxNotFoundException;
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
        $taxId = Uuid::create($id);
        $restaurantUuid = Uuid::create($restaurantId);

        $tax = $this->taxRepository->findById($taxId, $restaurantUuid);

        if ($tax === null) {
            throw TaxNotFoundException::forId($taxId);
        }

        $taxName = $name !== null ? TaxName::create($name) : null;
        $taxPercentage = $percentage !== null ? TaxPercentage::create($percentage) : null;

        if ($taxName !== null && ! $taxName->equals($tax->name())) {
            $existing = $this->taxRepository->findByNameAndRestaurantId($taxName, $restaurantUuid);

            if ($existing !== null && $existing->id()->value() !== $tax->id()->value()) {
                throw TaxNameAlreadyExistsException::forName($taxName->value());
            }

            $tax->updateName($taxName);
        }

        if ($taxPercentage !== null) {
            $tax->updatePercentage($taxPercentage);
        }

        $this->taxRepository->update($tax);

        return UpdateTaxResponse::create($tax);
    }
}
