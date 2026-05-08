<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

class UserRole
{
    private const SUPERADMIN = 'superadmin';

    private const ADMIN = 'admin';

    private const SUPERVISOR = 'supervisor';

    private const OPERATOR = 'operator';

    private const TENANT_ROLES = [
        self::ADMIN,
        self::SUPERVISOR,
        self::OPERATOR,
    ];

    private const SYSTEM_ROLES = [
        self::SUPERADMIN,
    ];

    private const VALID_ROLES = [
        ...self::TENANT_ROLES,
        ...self::SYSTEM_ROLES,
    ];

    private string $value;

    private function __construct(string $value)
    {
        if (! in_array($value, self::VALID_ROLES, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid user role "%s". Allowed: %s.',
                    $value,
                    implode(', ', self::VALID_ROLES),
                )
            );
        }

        $this->value = $value;
    }

    public static function create(string $value): self
    {
        return new self($value);
    }

    public static function createTenantRole(string $value): self
    {
        if (! in_array($value, self::TENANT_ROLES, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Role "%s" is not a tenant role. Allowed: %s.',
                    $value,
                    implode(', ', self::TENANT_ROLES),
                )
            );
        }

        return new self($value);
    }

    public static function superadmin(): self
    {
        return new self(self::SUPERADMIN);
    }

    public static function admin(): self
    {
        return new self(self::ADMIN);
    }

    public static function supervisor(): self
    {
        return new self(self::SUPERVISOR);
    }

    public static function operator(): self
    {
        return new self(self::OPERATOR);
    }

    /**
     * @return list<string>
     */
    public static function allowed(): array
    {
        return self::TENANT_ROLES;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function isSuperadmin(): bool
    {
        return $this->value === self::SUPERADMIN;
    }

    public function isAdmin(): bool
    {
        return $this->value === self::ADMIN;
    }

    public function isSupervisor(): bool
    {
        return $this->value === self::SUPERVISOR;
    }

    public function isOperator(): bool
    {
        return $this->value === self::OPERATOR;
    }
}
