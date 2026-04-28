<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Entrypoint\Http;

use App\User\Application\LogoutUser\LogoutUser;
use App\User\Infrastructure\Entrypoint\Http\Requests\LogoutUserRequest;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Cookie;

class LogoutPostController
{
    public function __construct(
        private LogoutUser $logoutUser,
    ) {}

    public function __invoke(LogoutUserRequest $request, string $restaurantId): Response
    {
        $refreshCredential = (string) $request->cookies->get('refresh_token', '');

        ($this->logoutUser)($restaurantId, $refreshCredential);

        return (new Response('', 204))
            ->withCookie($this->buildClearedRefreshCookie($restaurantId));
    }

    private function buildClearedRefreshCookie(string $restaurantId): Cookie
    {
        return Cookie::create('refresh_token')
            ->withValue('')
            ->withExpires((new \DateTimeImmutable)->modify('-1 day'))
            ->withPath('/api/restaurants/'.$restaurantId.'/auth')
            ->withSecure(app()->environment('production'))
            ->withHttpOnly(true)
            ->withSameSite('lax');
    }
}
