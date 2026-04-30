<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\Infrastructure\Http;

use App\Auth\Infrastructure\Http\Middleware\EnsureTokenMatchesRestaurant;
use App\Shared\Domain\Exception\AuthenticationRequiredException;
use App\Shared\Domain\ValueObject\AuthContext;
use App\Shared\Domain\ValueObject\UserRole;
use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Auth\AuthContextHolder;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class EnsureTokenMatchesRestaurantTest extends TestCase
{
    public function test_throws_missing_when_no_context_bound(): void
    {
        $middleware = new EnsureTokenMatchesRestaurant(new AuthContextHolder);

        $this->expectException(AuthenticationRequiredException::class);

        $middleware->handle($this->buildRequest(Uuid::generate()->value()), fn () => new Response);
    }

    public function test_throws_when_route_does_not_declare_restaurant_id(): void
    {
        $holder = new AuthContextHolder;
        $holder->bind($this->buildContext(Uuid::generate()));

        $middleware = new EnsureTokenMatchesRestaurant($holder);

        $request = Request::create('/some-path');

        $this->expectException(\InvalidArgumentException::class);

        $middleware->handle($request, fn () => new Response);
    }

    public function test_throws_invalid_when_token_restaurant_does_not_match_route(): void
    {
        $holder = new AuthContextHolder;
        $holder->bind($this->buildContext(Uuid::generate()));

        $middleware = new EnsureTokenMatchesRestaurant($holder);

        $this->expectException(AuthenticationRequiredException::class);

        $middleware->handle($this->buildRequest(Uuid::generate()->value()), fn () => new Response);
    }

    public function test_calls_next_when_token_restaurant_matches_route(): void
    {
        $restaurantId = Uuid::generate();

        $holder = new AuthContextHolder;
        $holder->bind($this->buildContext($restaurantId));

        $middleware = new EnsureTokenMatchesRestaurant($holder);

        $nextCalled = false;
        $response = $middleware->handle(
            $this->buildRequest($restaurantId->value()),
            function () use (&$nextCalled) {
                $nextCalled = true;

                return new Response('ok', 200);
            },
        );

        $this->assertTrue($nextCalled);
        $this->assertSame('ok', $response->getContent());
    }

    private function buildContext(Uuid $restaurantId): AuthContext
    {
        return AuthContext::create(
            Uuid::generate(),
            $restaurantId,
            UserRole::admin(),
            Uuid::generate(),
        );
    }

    private function buildRequest(string $restaurantId): Request
    {
        $request = Request::create('/api/restaurants/'.$restaurantId.'/dummy');

        $route = new Route(['GET'], '/api/restaurants/{restaurantId}/dummy', fn () => null);
        $route->bind($request);
        $route->setParameter('restaurantId', $restaurantId);

        $request->setRouteResolver(fn () => $route);

        return $request;
    }
}
