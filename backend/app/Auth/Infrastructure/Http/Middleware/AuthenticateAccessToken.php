<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Http\Middleware;

use App\Auth\Domain\Exception\InvalidAccessTokenException;
use App\Auth\Domain\Interfaces\AccessTokenVerifierInterface;
use App\Shared\Domain\ValueObject\AuthContext;
use App\Shared\Infrastructure\Auth\AuthContextHolder;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAccessToken
{
    public function __construct(
        private AccessTokenVerifierInterface $accessTokenVerifier,
        private AuthContextHolder $authContextHolder,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if ($token === null || $token === '') {
            throw InvalidAccessTokenException::malformed();
        }

        $payload = $this->accessTokenVerifier->verify($token);

        $this->authContextHolder->bind(
            AuthContext::create(
                $payload->userId(),
                $payload->restaurantId(),
                $payload->role(),
                $payload->sessionId(),
            )
        );

        return $next($request);
    }
}
