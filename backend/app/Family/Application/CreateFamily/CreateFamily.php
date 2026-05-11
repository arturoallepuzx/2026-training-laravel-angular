<?php

declare(strict_types=1);

namespace App\Family\Application\CreateFamily;

use App\Family\Domain\Entity\Family;
use App\Family\Domain\Exception\FamilyNameAlreadyExistsException;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;
use App\Family\Domain\ValueObject\FamilyName;
use App\Shared\Domain\ValueObject\Uuid;

class CreateFamily
{
    public function __construct(
        private FamilyRepositoryInterface $familyRepository,
    ) {}

    public function __invoke(string $restaurantId, string $name, bool $active = true): CreateFamilyResponse
    {
        $restaurantUuid = Uuid::create($restaurantId);
        $familyName = FamilyName::create($name);

        if ($this->familyRepository->findByNameAndRestaurantId($familyName, $restaurantUuid) !== null) {
            throw FamilyNameAlreadyExistsException::forName($familyName->value());
        }

        $family = Family::dddCreate(
            $restaurantUuid,
            $familyName,
            $active,
        );

        $this->familyRepository->create($family);

        return CreateFamilyResponse::create($family);
    }
}
