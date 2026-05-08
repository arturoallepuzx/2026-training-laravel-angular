<?php

declare(strict_types=1);

namespace App\Restaurant\Domain\ValueObject;

class TaxId
{
    private const MAX_LENGTH = 32;

    private string $value;

    private function __construct(string $value)
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new \InvalidArgumentException('Tax ID cannot be empty.');
        }
        if (mb_strlen($trimmed) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('Tax ID cannot exceed %d characters.', self::MAX_LENGTH)
            );
        }
        $this->value = mb_strtoupper($trimmed);
    }

    public static function create(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
