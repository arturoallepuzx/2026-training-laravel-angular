<?php

declare(strict_types=1);

namespace App\Family\Application\GetFamilyById;

use App\Family\Domain\Exception\FamilyNotFoundException;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

class GetFamilyById
{
    public function __construct(
        private FamilyRepositoryInterface $familyRepository,
    ) {}

    public function __invoke(string $id, string $restaurantId): GetFamilyByIdResponse
    {
        $familyId = Uuid::create($id);

        $family = $this->familyRepository->findById(
            $familyId,
            Uuid::create($restaurantId),
        );

        if ($family === null) {
            throw FamilyNotFoundException::forId($familyId);
        }

        return GetFamilyByIdResponse::create($family);
    }
}
