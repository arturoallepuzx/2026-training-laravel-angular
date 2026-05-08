<?php

declare(strict_types=1);

namespace App\Restaurant\Domain\Entity;

use App\Restaurant\Domain\ValueObject\LegalName;
use App\Restaurant\Domain\ValueObject\RestaurantName;
use App\Restaurant\Domain\ValueObject\TaxId;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\Uuid;

class Restaurant
{
    private function __construct(
        private Uuid $id,
        private RestaurantName $name,
        private LegalName $legalName,
        private TaxId $taxId,
        private Email $email,
        private DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
    ) {}

    public static function dddCreate(
        RestaurantName $name,
        LegalName $legalName,
        TaxId $taxId,
        Email $email,
    ): self {
        $now = DomainDateTime::now();

        return new self(
            Uuid::generate(),
            $name,
            $legalName,
            $taxId,
            $email,
            $now,
            $now,
        );
    }

    public static function fromPersistence(
        string $id,
        string $name,
        string $legalName,
        string $taxId,
        string $email,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            Uuid::create($id),
            RestaurantName::create($name),
            LegalName::create($legalName),
            TaxId::create($taxId),
            Email::create($email),
            DomainDateTime::create($createdAt),
            DomainDateTime::create($updatedAt),
        );
    }

    public function updateName(RestaurantName $name): void
    {
        $this->name = $name;
        $this->touch();
    }

    public function updateLegalName(LegalName $legalName): void
    {
        $this->legalName = $legalName;
        $this->touch();
    }

    public function updateTaxId(TaxId $taxId): void
    {
        $this->taxId = $taxId;
        $this->touch();
    }

    public function updateEmail(Email $email): void
    {
        $this->email = $email;
        $this->touch();
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function name(): RestaurantName
    {
        return $this->name;
    }

    public function legalName(): LegalName
    {
        return $this->legalName;
    }

    public function taxId(): TaxId
    {
        return $this->taxId;
    }

    public function email(): Email
    {
        return $this->email;
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
