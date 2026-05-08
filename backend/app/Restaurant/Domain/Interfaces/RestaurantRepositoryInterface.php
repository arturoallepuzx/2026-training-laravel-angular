<?php

declare(strict_types=1);

namespace App\Restaurant\Domain\Interfaces;

use App\Restaurant\Domain\Entity\Restaurant;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\Uuid;

interface RestaurantRepositoryInterface
{
    public function create(Restaurant $restaurant): void;

    public function update(Restaurant $restaurant): void;

    public function delete(Uuid $id): void;

    public function findById(Uuid $id): ?Restaurant;

    public function findByEmail(Email $email): ?Restaurant;

    public function findAll(): array;
}
