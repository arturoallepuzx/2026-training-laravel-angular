<?php

declare(strict_types=1);

namespace App\Restaurant\Infrastructure\Entrypoint\Http;

use App\Restaurant\Application\DeleteRestaurant\DeleteRestaurant;
use Illuminate\Http\Response;

class DeleteController
{
    public function __construct(
        private DeleteRestaurant $deleteRestaurant,
    ) {}

    public function __invoke(string $restaurantId): Response
    {
        ($this->deleteRestaurant)($restaurantId);

        return new Response('', 204);
    }
}
