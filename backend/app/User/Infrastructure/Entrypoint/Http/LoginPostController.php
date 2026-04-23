<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Entrypoint\Http;

use App\User\Application\LoginUser\LoginUser;
use App\User\Application\LoginUser\LoginUserResponse;
use App\User\Infrastructure\Entrypoint\Http\Requests\LoginUserRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Cookie;

class LoginPostController
{
    public function __construct(
        private LoginUser $loginUser,
    ) {}

    public function __invoke(LoginUserRequest $request, string $restaurantId): JsonResponse
    {
        $response = ($this->loginUser)(
            $restaurantId,
            $request->validated('email'),
            $request->validated('password'),
        );

        return (new JsonResponse($response->toArray(), 200))
            ->withCookie($this->buildRefreshCookie($restaurantId, $response));
    }

    private function buildRefreshCookie(string $restaurantId, LoginUserResponse $response): Cookie
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
