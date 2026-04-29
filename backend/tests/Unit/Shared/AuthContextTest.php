<?php

declare(strict_types=1);

namespace Tests\Unit\Shared;

use App\Shared\Domain\ValueObject\AuthContext;
use App\Shared\Domain\ValueObject\UserRole;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

class AuthContextTest extends TestCase
{
    public function test_create_returns_instance_with_all_fields_accessible(): void
    {
        $userId = Uuid::generate();
        $restaurantId = Uuid::generate();
        $sessionId = Uuid::generate();
        $role = UserRole::supervisor();

        $context = AuthContext::create($userId, $restaurantId, $role, $sessionId);

        $this->assertSame($userId->value(), $context->userId()->value());
        $this->assertSame($restaurantId->value(), $context->restaurantId()->value());
        $this->assertTrue($context->role()->isSupervisor());
        $this->assertSame($sessionId->value(), $context->sessionId()->value());
    }
}
