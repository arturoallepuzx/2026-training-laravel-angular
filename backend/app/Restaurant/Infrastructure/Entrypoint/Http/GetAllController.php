<?php

declare(strict_types=1);

namespace App\Restaurant\Infrastructure\Entrypoint\Http;

use App\Restaurant\Application\ListRestaurants\ListRestaurants;
use Illuminate\Http\JsonResponse;

class GetAllController
{
    public function __construct(
        private ListRestaurants $listRestaurants,
    ) {}

    public function __invoke(): JsonResponse
    {
        $response = ($this->listRestaurants)();

        return new JsonResponse($response->toArray());
    }
}
