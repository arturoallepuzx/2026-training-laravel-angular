<?php

namespace App\Tax\Application\GetAllTaxes;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tax\Domain\Interfaces\TaxRepositoryInterface;

class GetAllTaxes
{
    public function __construct(
        private TaxRepositoryInterface $taxRepository,
    ) {}

    public function __invoke(string $restaurantId): GetAllTaxesResponse
    {
        $taxes = $this->taxRepository->findAllByRestaurantId(Uuid::create($restaurantId));

        return GetAllTaxesResponse::create($taxes);
    }
}
