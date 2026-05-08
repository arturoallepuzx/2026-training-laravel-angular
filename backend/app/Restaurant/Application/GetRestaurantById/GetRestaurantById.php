<?php

declare(strict_types=1);

namespace App\Restaurant\Application\GetRestaurantById;

use App\Restaurant\Domain\Exception\RestaurantNotFoundException;
use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

class GetRestaurantById
{
    public function __construct(
        private RestaurantRepositoryInterface $restaurantRepository,
    ) {}

    public function __invoke(string $id): GetRestaurantByIdResponse
    {
        $uuid = Uuid::create($id);

        $restaurant = $this->restaurantRepository->findById($uuid);

        if ($restaurant === null) {
            throw RestaurantNotFoundException::forId($uuid);
        }

        return GetRestaurantByIdResponse::create($restaurant);
    }
}
