<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Entrypoint\Http;

use App\User\Application\LoginUserWithPin\LoginUserWithPin;
use App\User\Application\LoginUserWithPin\LoginUserWithPinResponse;
use App\User\Infrastructure\Entrypoint\Http\Requests\LoginUserWithPinRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Cookie;

class PinLoginPostController
{
    public function __construct(
        private LoginUserWithPin $loginUserWithPin,
    ) {}

    public function __invoke(LoginUserWithPinRequest $request, string $restaurantId): JsonResponse
    {
        $response = ($this->loginUserWithPin)(
            $restaurantId,
            $request->validated('user_id'),
            $request->validated('pin'),
        );

        return (new JsonResponse($response->toArray(), 200))
            ->withCookie($this->buildRefreshCookie($restaurantId, $response));
    }

    private function buildRefreshCookie(string $restaurantId, LoginUserWithPinResponse $response): Cookie
    {
        return Cookie::create('refresh_token')
            ->withValue($response->refreshToken())
            ->withExpires($response->refreshTokenExpiresAt()->value())
            ->withPath('/api/restaurants/'.$restaurantId.'/auth')
            ->withSecure(app()->environment('production'))
            ->withHttpOnly(true)
            ->withSameSite('lax');
    }
}
