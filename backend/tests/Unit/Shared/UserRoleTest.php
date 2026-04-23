<?php

declare(strict_types=1);

namespace Tests\Unit\Shared;

use App\Shared\Domain\ValueObject\UserRole;
use PHPUnit\Framework\TestCase;

class UserRoleTest extends TestCase
{
    public function test_create_accepts_valid_roles(): void
    {
        $this->assertTrue(UserRole::create('admin')->isAdmin());
        $this->assertTrue(UserRole::create('supervisor')->isSupervisor());
        $this->assertTrue(UserRole::create('operator')->isOperator());
    }

    public function test_create_throws_for_invalid_role(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        UserRole::create('owner');
    }

    public function test_allowed_returns_all_supported_roles(): void
    {
        $this->assertSame(['admin', 'supervisor', 'operator'], UserRole::allowed());
    }

    public function test_equals_compares_by_value(): void
    {
        $this->assertTrue(UserRole::admin()->equals(UserRole::admin()));
        $this->assertFalse(UserRole::admin()->equals(UserRole::operator()));
    }
}
