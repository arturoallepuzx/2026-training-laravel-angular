<?php

declare(strict_types=1);

namespace Tests\Unit\Shared;

use App\Shared\Domain\ValueObject\AuthContext;
use App\Shared\Domain\ValueObject\UserRole;
use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Auth\AuthContextHolder;
use PHPUnit\Framework\TestCase;

class AuthContextHolderTest extends TestCase
{
    public function test_get_returns_null_when_no_context_bound(): void
    {
        $holder = new AuthContextHolder;

        $this->assertNull($holder->get());
    }

    public function test_get_returns_bound_context_after_bind(): void
    {
        $holder = new AuthContextHolder;
        $context = $this->buildContext();

        $holder->bind($context);

        $this->assertSame($context, $holder->get());
    }

    public function test_bind_replaces_previous_context(): void
    {
        $holder = new AuthContextHolder;
        $first = $this->buildContext();
        $second = $this->buildContext();

        $holder->bind($first);
        $holder->bind($second);

        $this->assertSame($second, $holder->get());
    }

    private function buildContext(): AuthContext
    {
        return AuthContext::create(
            Uuid::generate(),
            Uuid::generate(),
            UserRole::admin(),
            Uuid::generate(),
        );
    }
}
