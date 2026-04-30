<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\Infrastructure\Http;

use App\Auth\Domain\Exception\RoleNotAllowedException;
use App\Auth\Infrastructure\Http\Middleware\RequireRole;
use App\Shared\Domain\Exception\AuthenticationRequiredException;
use App\Shared\Domain\ValueObject\AuthContext;
use App\Shared\Domain\ValueObject\UserRole;
use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Auth\AuthContextHolder;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class RequireRoleTest extends TestCase
{
    public function test_throws_when_no_roles_configured(): void
    {
        $middleware = new RequireRole(new AuthContextHolder);

        $this->expectException(\InvalidArgumentException::class);

        $middleware->handle(Request::create('/'), fn () => new Response);
    }

    public function test_throws_when_configured_role_is_not_allowed_value(): void
    {
        $middleware = new RequireRole(new AuthContextHolder);

        $this->expectException(\InvalidArgumentException::class);

        $middleware->handle(Request::create('/'), fn () => new Response, 'admn');
    }

    public function test_throws_missing_when_no_context_bound(): void
    {
        $middleware = new RequireRole(new AuthContextHolder);

        $this->expectException(AuthenticationRequiredException::class);

        $middleware->handle(Request::create('/'), fn () => new Response, 'admin');
    }

    public function test_throws_role_not_allowed_when_context_role_is_not_in_list(): void
    {
        $holder = new AuthContextHolder;
        $holder->bind($this->buildContext(UserRole::operator()));

        $middleware = new RequireRole($holder);

        $this->expectException(RoleNotAllowedException::class);

        $middleware->handle(Request::create('/'), fn () => new Response, 'admin', 'supervisor');
    }

    public function test_calls_next_when_context_role_is_allowed(): void
    {
        $holder = new AuthContextHolder;
        $holder->bind($this->buildContext(UserRole::supervisor()));

        $middleware = new RequireRole($holder);

        $nextCalled = false;
        $response = $middleware->handle(Request::create('/'), function () use (&$nextCalled) {
            $nextCalled = true;

            return new Response('ok', 200);
        }, 'admin', 'supervisor');

        $this->assertTrue($nextCalled);
        $this->assertSame('ok', $response->getContent());
    }

    private function buildContext(UserRole $role): AuthContext
    {
        return AuthContext::create(
            Uuid::generate(),
            Uuid::generate(),
            $role,
            Uuid::generate(),
        );
    }
}
