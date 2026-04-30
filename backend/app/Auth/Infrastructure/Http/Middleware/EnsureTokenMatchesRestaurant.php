<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Http\Middleware;

use App\Shared\Domain\Exception\AuthenticationRequiredException;
use App\Shared\Infrastructure\Auth\AuthContextHolder;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTokenMatchesRestaurant
{
    public function __construct(
        private AuthContextHolder $authContextHolder,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $context = $this->authContextHolder->get();

        if ($context === null) {
            throw AuthenticationRequiredException::missing();
        }

        $routeRestaurantId = $request->route('restaurantId');

        if (! is_string($routeRestaurantId) || $routeRestaurantId === '') {
            throw new \InvalidArgumentException('Route must declare a {restaurantId} parameter to use auth.restaurant middleware.');
        }

        if ($context->restaurantId()->value() !== $routeRestaurantId) {
            throw AuthenticationRequiredException::invalid();
        }

        return $next($request);
    }
}
