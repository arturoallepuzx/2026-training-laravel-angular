<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Shared\Domain\Exception\ConflictException;
use App\Shared\Domain\Exception\ForbiddenException;
use App\Shared\Domain\Exception\NotFoundException;
use App\Shared\Domain\Exception\UnauthorizedException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class Handler
{
    public static function register(Exceptions $exceptions): void
    {
        $exceptions->render(function (ValidationException $e) {
            return new JsonResponse(['error' => 'The given data was invalid.', 'details' => $e->errors()], 422);
        });

        $exceptions->render(function (NotFoundException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 404);
        });

        $exceptions->render(function (ConflictException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 409);
        });

        $exceptions->render(function (UnauthorizedException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 401);
        });

        $exceptions->render(function (ForbiddenException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 403);
        });

        $exceptions->render(function (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 422);
        });
    }
}
