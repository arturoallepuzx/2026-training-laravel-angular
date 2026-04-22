<?php

declare(strict_types=1);

namespace App\User\Domain\ValueObject;

class UserPin
{
    private const LENGTH = 4;

    private string $value;

    private function __construct(string $value)
    {
        if (strlen($value) !== self::LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('User PIN must be exactly %d digits.', self::LENGTH)
            );
        }

        if (! ctype_digit($value)) {
            throw new \InvalidArgumentException('User PIN must contain only digits.');
        }

        $this->value = $value;
    }

    public static function create(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return hash_equals($this->value, $other->value);
    }
}
