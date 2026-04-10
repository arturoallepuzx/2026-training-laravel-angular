<?php

namespace App\Tax\Application\GetTaxById;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tax\Domain\Interfaces\TaxRepositoryInterface;

class GetTaxById
{
    public function __construct(
        private TaxRepositoryInterface $taxRepository,
    ) {}

    public function __invoke(string $id, string $restaurantId): GetTaxByIdResponse
    {
        $tax = $this->taxRepository->findById(
            Uuid::create($id),
            Uuid::create($restaurantId),
        );

        if ($tax === null) {
            throw new \DomainException('Tax not found.');
        }

        return GetTaxByIdResponse::create($tax);
    }
}
