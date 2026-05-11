<?php

declare(strict_types=1);

namespace App\Family\Application\DeleteFamily;

use App\Family\Domain\Exception\FamilyNotFoundException;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

class DeleteFamily
{
    public function __construct(
        private FamilyRepositoryInterface $familyRepository,
    ) {}

    public function __invoke(string $id, string $restaurantId): void
    {
        $idVO = Uuid::create($id);
        $restaurantIdVO = Uuid::create($restaurantId);

        $family = $this->familyRepository->findById($idVO, $restaurantIdVO);

        if ($family === null) {
            throw FamilyNotFoundException::forId($idVO);
        }

        $this->familyRepository->delete($idVO, $restaurantIdVO);
    }
}
