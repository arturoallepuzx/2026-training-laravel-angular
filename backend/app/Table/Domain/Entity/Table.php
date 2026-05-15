<?php

declare(strict_types=1);

namespace App\Table\Domain\Entity;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use App\Table\Domain\ValueObject\TableName;

class Table
{
    private bool $modified = false;

    private function __construct(
        private Uuid $id,
        private Uuid $restaurantId,
        private Uuid $zoneId,
        private TableName $name,
        private DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
    ) {}

    public static function dddCreate(
        Uuid $restaurantId,
        Uuid $zoneId,
        TableName $name,
    ): self {
        $now = DomainDateTime::now();

        return new self(
            Uuid::generate(),
            $restaurantId,
            $zoneId,
            $name,
            $now,
            $now,
        );
    }

    public static function fromPersistence(
        string $id,
        string $restaurantId,
        string $zoneId,
        string $name,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            Uuid::create($id),
            Uuid::create($restaurantId),
            Uuid::create($zoneId),
            TableName::create($name),
            DomainDateTime::create($createdAt),
            DomainDateTime::create($updatedAt),
        );
    }

    public function updateName(TableName $name): void
    {
        if ($this->name->value() === $name->value()) {
            return;
        }

        $this->name = $name;
        $this->touch();
    }

    public function updateZoneId(Uuid $zoneId): void
    {
        if ($this->zoneId->value() === $zoneId->value()) {
            return;
        }

        $this->zoneId = $zoneId;
        $this->touch();
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function restaurantId(): Uuid
    {
        return $this->restaurantId;
    }

    public function zoneId(): Uuid
    {
        return $this->zoneId;
    }

    public function name(): TableName
    {
        return $this->name;
    }

    public function createdAt(): DomainDateTime
    {
        return $this->createdAt;
    }

    public function updatedAt(): DomainDateTime
    {
        return $this->updatedAt;
    }

    public function wasModified(): bool
    {
        return $this->modified;
    }

    private function touch(): void
    {
        $this->modified = true;
        $this->updatedAt = DomainDateTime::now();
    }
}
