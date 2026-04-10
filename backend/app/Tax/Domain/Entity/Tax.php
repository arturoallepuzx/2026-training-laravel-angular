<?php

namespace App\Tax\Domain\Entity;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tax\Domain\ValueObject\TaxName;
use App\Tax\Domain\ValueObject\TaxPercentage;

class Tax
{
    private function __construct(
        private Uuid $id,
        private Uuid $restaurantId,
        private TaxName $name,
        private TaxPercentage $percentage,
        private DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
    ) {}

    public static function dddCreate(
        Uuid $restaurantId,
        TaxName $name,
        TaxPercentage $percentage,
    ): self {
        $now = DomainDateTime::now();

        return new self(
            Uuid::generate(),
            $restaurantId,
            $name,
            $percentage,
            $now,
            $now,
        );
    }

    public static function fromPersistence(
        string $id,
        string $restaurantId,
        string $name,
        int $percentage,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            Uuid::create($id),
            Uuid::create($restaurantId),
            TaxName::create($name),
            TaxPercentage::create($percentage),
            DomainDateTime::create($createdAt),
            DomainDateTime::create($updatedAt),
        );
    }

    public function updateName(TaxName $name): void
    {
        $this->name = $name;
        $this->touch();
    }

    public function updatePercentage(TaxPercentage $percentage): void
    {
        $this->percentage = $percentage;
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

    public function name(): TaxName
    {
        return $this->name;
    }

    public function percentage(): TaxPercentage
    {
        return $this->percentage;
    }

    public function createdAt(): DomainDateTime
    {
        return $this->createdAt;
    }

    public function updatedAt(): DomainDateTime
    {
        return $this->updatedAt;
    }

    private function touch(): void
    {
        $this->updatedAt = DomainDateTime::now();
    }
}
