<?php

namespace App\Exceptions;

use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\JsonResponse;

class Handler
{
    public static function register(Exceptions $exceptions): void
    {
        $exceptions->render(function (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 404);
        });

        $exceptions->render(function (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 422);
        });
    }
}
