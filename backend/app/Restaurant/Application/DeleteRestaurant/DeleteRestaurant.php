<?php

declare(strict_types=1);

namespace App\Restaurant\Application\DeleteRestaurant;

use App\Restaurant\Domain\Exception\RestaurantNotFoundException;
use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

class DeleteRestaurant
{
    public function __construct(
        private RestaurantRepositoryInterface $restaurantRepository,
    ) {}

    public function __invoke(string $id): void
    {
        $uuid = Uuid::create($id);

        $restaurant = $this->restaurantRepository->findById($uuid);

        if ($restaurant === null) {
            throw RestaurantNotFoundException::forId($uuid);
        }

        $this->restaurantRepository->delete($uuid);
    }
}
