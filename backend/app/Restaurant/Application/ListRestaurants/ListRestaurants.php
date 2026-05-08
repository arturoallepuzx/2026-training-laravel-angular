<?php

declare(strict_types=1);

namespace App\Restaurant\Application\ListRestaurants;

use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;

class ListRestaurants
{
    public function __construct(
        private RestaurantRepositoryInterface $restaurantRepository,
    ) {}

    public function __invoke(): ListRestaurantsResponse
    {
        return ListRestaurantsResponse::create($this->restaurantRepository->findAll());
    }
}
