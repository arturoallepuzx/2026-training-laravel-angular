<?php

declare(strict_types=1);

namespace App\Family\Infrastructure\Entrypoint\Http;

use App\Family\Application\ListFamilies\ListFamilies;
use Illuminate\Http\JsonResponse;

class GetAllController
{
    public function __construct(
        private ListFamilies $listFamilies,
    ) {}

    public function __invoke(string $restaurantId): JsonResponse
    {
        $response = ($this->listFamilies)($restaurantId);

        return new JsonResponse($response->toArray());
    }
}
