<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Entrypoint\Http;

use App\User\Application\RefreshAuthentication\RefreshAuthentication;
use App\User\Application\RefreshAuthentication\RefreshAuthenticationResponse;
use App\User\Infrastructure\Entrypoint\Http\Requests\TenantRouteRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Cookie;

class RefreshPostController
{
    public function __construct(
        private RefreshAuthentication $refreshAuthentication,
    ) {}

    public function __invoke(TenantRouteRequest $request, string $restaurantId): JsonResponse
    {
        $refreshCredential = (string) $request->cookies->get('refresh_token', '');

        $response = ($this->refreshAuthentication)($restaurantId, $refreshCredential);

        return (new JsonResponse($response->toArray(), 200))
            ->withCookie($this->buildRefreshCookie($restaurantId, $response));
    }

    private function buildRefreshCookie(string $restaurantId, RefreshAuthenticationResponse $response): Cookie
    {
        return Cookie::create('refresh_token')
            ->withValue($response->refreshCredential())
            ->withExpires($response->refreshCredentialExpiresAt()->value())
            ->withPath('/api/restaurants/'.$restaurantId.'/auth')
            ->withSecure(app()->environment('production'))
            ->withHttpOnly(true)
            ->withSameSite('lax');
    }
}
