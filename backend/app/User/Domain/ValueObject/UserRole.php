<?php

namespace App\User\Domain\ValueObject;

class UserRole
{
    private const ADMIN = 'admin';

    private const SUPERVISOR = 'supervisor';

    private const OPERATOR = 'operator';

    private const ALLOWED = [
        self::ADMIN,
        self::SUPERVISOR,
        self::OPERATOR,
    ];

    private string $value;

    private function __construct(string $value)
    {
        if (! in_array($value, self::ALLOWED, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid user role "%s". Allowed: %s.',
                    $value,
                    implode(', ', self::ALLOWED),
                )
            );
        }

        $this->value = $value;
    }

    public static function create(string $value): self
    {
        return new self($value);
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
        return self::ALLOWED;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
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
