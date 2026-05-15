<?php

declare(strict_types=1);

namespace App\Restaurant\Application\UpdateRestaurant;

use App\Restaurant\Domain\Exception\RestaurantEmailAlreadyExistsException;
use App\Restaurant\Domain\Exception\RestaurantNotFoundException;
use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
use App\Restaurant\Domain\ValueObject\LegalName;
use App\Restaurant\Domain\ValueObject\RestaurantName;
use App\Restaurant\Domain\ValueObject\TaxId;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\Uuid;

class UpdateRestaurant
{
    public function __construct(
        private RestaurantRepositoryInterface $restaurantRepository,
    ) {}

    public function __invoke(
        string $id,
        ?string $name,
        ?string $legalName,
        ?string $taxId,
        ?string $email,
    ): UpdateRestaurantResponse {
        $uuid = Uuid::create($id);

        $restaurant = $this->restaurantRepository->findById($uuid);

        if ($restaurant === null) {
            throw RestaurantNotFoundException::forId($uuid);
        }

        if ($name !== null) {
            $restaurant->updateName(RestaurantName::create($name));
        }

        if ($legalName !== null) {
            $restaurant->updateLegalName(LegalName::create($legalName));
        }

        if ($taxId !== null) {
            $restaurant->updateTaxId(TaxId::create($taxId));
        }

        if ($email !== null) {
            $newEmail = Email::create($email);

            if ($newEmail->value() !== $restaurant->email()->value()) {
                $existing = $this->restaurantRepository->findByEmail($newEmail);

                if ($existing !== null && $existing->id()->value() !== $restaurant->id()->value()) {
                    throw RestaurantEmailAlreadyExistsException::forEmail($newEmail->value());
                }

                $restaurant->updateEmail($newEmail);
            }
        }

        if ($restaurant->wasModified()) {
            $this->restaurantRepository->update($restaurant);
        }

        return UpdateRestaurantResponse::create($restaurant);
    }
}
