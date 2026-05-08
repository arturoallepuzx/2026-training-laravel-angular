<?php

declare(strict_types=1);

namespace App\Tax\Application\ListTaxes;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tax\Domain\Interfaces\TaxRepositoryInterface;

class ListTaxes
{
    public function __construct(
        private TaxRepositoryInterface $taxRepository,
    ) {}

    public function __invoke(string $restaurantId): ListTaxesResponse
    {
        $taxes = $this->taxRepository->findAllByRestaurantId(Uuid::create($restaurantId));

        return ListTaxesResponse::create($taxes);
    }
}
