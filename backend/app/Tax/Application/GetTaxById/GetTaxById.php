<?php

declare(strict_types=1);

namespace App\Tax\Application\GetTaxById;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tax\Domain\Exception\TaxNotFoundException;
use App\Tax\Domain\Interfaces\TaxRepositoryInterface;

class GetTaxById
{
    public function __construct(
        private TaxRepositoryInterface $taxRepository,
    ) {}

    public function __invoke(string $id, string $restaurantId): GetTaxByIdResponse
    {
        $taxId = Uuid::create($id);

        $tax = $this->taxRepository->findById(
            $taxId,
            Uuid::create($restaurantId),
        );

        if ($tax === null) {
            throw TaxNotFoundException::forId($taxId);
        }

        return GetTaxByIdResponse::create($tax);
    }
}
