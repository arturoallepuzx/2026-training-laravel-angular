<?php

declare(strict_types=1);

namespace App\Family\Application\UpdateFamily;

use App\Family\Domain\Exception\FamilyNameAlreadyExistsException;
use App\Family\Domain\Exception\FamilyNotFoundException;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;
use App\Family\Domain\ValueObject\FamilyName;
use App\Shared\Domain\ValueObject\Uuid;

class UpdateFamily
{
    public function __construct(
        private FamilyRepositoryInterface $familyRepository,
    ) {}

    public function __invoke(string $id, string $restaurantId, ?string $name, ?bool $active): UpdateFamilyResponse
    {
        $familyId = Uuid::create($id);
        $restaurantUuid = Uuid::create($restaurantId);

        $family = $this->familyRepository->findById($familyId, $restaurantUuid);

        if ($family === null) {
            throw FamilyNotFoundException::forId($familyId);
        }

        $familyName = $name !== null ? FamilyName::create($name) : null;

        if ($familyName !== null && $familyName->value() !== $family->name()->value()) {
            if (! $familyName->equals($family->name())) {
                $existing = $this->familyRepository->findByNameAndRestaurantId($familyName, $restaurantUuid);

                if ($existing !== null && $existing->id()->value() !== $family->id()->value()) {
                    throw FamilyNameAlreadyExistsException::forName($familyName->value());
                }
            }

            $family->updateName($familyName);
        }

        if ($active !== null) {
            $family->updateActive($active);
        }

        if ($family->wasModified()) {
            $this->familyRepository->update($family);
        }

        return UpdateFamilyResponse::create($family);
    }
}
