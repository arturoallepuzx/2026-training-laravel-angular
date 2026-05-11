<?php

declare(strict_types=1);

namespace App\Family\Application\ListFamilies;

use App\Family\Domain\Interfaces\FamilyRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

class ListFamilies
{
    public function __construct(
        private FamilyRepositoryInterface $familyRepository,
    ) {}

    public function __invoke(string $restaurantId): ListFamiliesResponse
    {
        $families = $this->familyRepository->findAllByRestaurantId(Uuid::create($restaurantId));

        return ListFamiliesResponse::create($families);
    }
}
