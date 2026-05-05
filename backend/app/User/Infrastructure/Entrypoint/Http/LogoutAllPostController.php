<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Entrypoint\Http;

use App\Shared\Domain\Exception\AuthenticationRequiredException;
use App\Shared\Infrastructure\Auth\AuthContextHolder;
use App\User\Application\LogoutAllUserSessions\LogoutAllUserSessions;
use App\User\Infrastructure\Entrypoint\Http\Requests\TenantRouteRequest;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Cookie;

class LogoutAllPostController
{
    public function __construct(
        private LogoutAllUserSessions $logoutAllUserSessions,
        private AuthContextHolder $authContextHolder,
    ) {}

    public function __invoke(TenantRouteRequest $request, string $restaurantId): Response
    {
        $context = $this->authContextHolder->get();

        if ($context === null) {
            throw AuthenticationRequiredException::missing();
        }

        ($this->logoutAllUserSessions)($context->userId()->value());

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
