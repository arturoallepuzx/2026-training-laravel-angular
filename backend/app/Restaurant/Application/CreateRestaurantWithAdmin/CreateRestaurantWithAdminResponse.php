<?php

declare(strict_types=1);

namespace App\Restaurant\Application\CreateRestaurantWithAdmin;

use App\Restaurant\Application\CreateRestaurant\CreateRestaurantResponse;
use App\User\Domain\Entity\User;

final readonly class CreateRestaurantWithAdminResponse
{
    /**
     * @param  array<string, mixed>  $restaurant
     * @param  array<string, mixed>  $admin
     */
    public function __construct(
        public array $restaurant,
        public array $admin,
    ) {}

    public static function create(CreateRestaurantResponse $restaurant, User $admin): self
    {
        return new self(
            restaurant: $restaurant->toArray(),
            admin: [
                'id' => $admin->id()->value(),
                'restaurant_id' => $admin->restaurantId()->value(),
                'name' => $admin->name()->value(),
                'email' => $admin->email()->value(),
                'role' => $admin->role()->value(),
            ],
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'restaurant' => $this->restaurant,
            'admin' => $this->admin,
        ];
    }
}
