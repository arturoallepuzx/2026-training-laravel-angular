<?php

declare(strict_types=1);

namespace App\Restaurant\Application\CreateRestaurant;

use App\Restaurant\Domain\Entity\Restaurant;
use App\Restaurant\Domain\Exception\RestaurantEmailAlreadyExistsException;
use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
use App\Restaurant\Domain\ValueObject\LegalName;
use App\Restaurant\Domain\ValueObject\RestaurantName;
use App\Restaurant\Domain\ValueObject\TaxId;
use App\Shared\Domain\ValueObject\Email;

class CreateRestaurant
{
    public function __construct(
        private RestaurantRepositoryInterface $restaurantRepository,
    ) {}

    public function __invoke(
        string $name,
        string $legalName,
        string $taxId,
        string $email,
    ): CreateRestaurantResponse {
        $emailVo = Email::create($email);

        if ($this->restaurantRepository->findByEmail($emailVo) !== null) {
            throw RestaurantEmailAlreadyExistsException::forEmail($emailVo->value());
        }

        $restaurant = Restaurant::dddCreate(
            RestaurantName::create($name),
            LegalName::create($legalName),
            TaxId::create($taxId),
            $emailVo,
        );

        $this->restaurantRepository->create($restaurant);

        return CreateRestaurantResponse::create($restaurant);
    }
}
