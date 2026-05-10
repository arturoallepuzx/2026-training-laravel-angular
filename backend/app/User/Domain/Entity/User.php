<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\UserRole;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\ValueObject\PasswordHash;
use App\User\Domain\ValueObject\UserName;
use App\User\Domain\ValueObject\UserPinHash;

class User
{
    private function __construct(
        private Uuid $id,
        private Uuid $restaurantId,
        private UserRole $role,
        private UserName $name,
        private Email $email,
        private PasswordHash $passwordHash,
        private ?UserPinHash $pinHash,
        private ?string $imageSrc,
        private DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
    ) {}

    public static function dddCreate(
        Uuid $restaurantId,
        UserRole $role,
        UserName $name,
        Email $email,
        PasswordHash $passwordHash,
        ?UserPinHash $pinHash = null,
        ?string $imageSrc = null,
    ): self {
        $now = DomainDateTime::now();

        return new self(
            Uuid::generate(),
            $restaurantId,
            $role,
            $name,
            $email,
            $passwordHash,
            $pinHash,
            $imageSrc,
            $now,
            $now,
        );
    }

    public static function fromPersistence(
        string $id,
        string $restaurantId,
        string $role,
        string $name,
        string $email,
        string $passwordHash,
        ?string $pinHash,
        ?string $imageSrc,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            Uuid::create($id),
            Uuid::create($restaurantId),
            UserRole::create($role),
            UserName::create($name),
            Email::create($email),
            PasswordHash::create($passwordHash),
            $pinHash !== null ? UserPinHash::create($pinHash) : null,
            $imageSrc,
            DomainDateTime::create($createdAt),
            DomainDateTime::create($updatedAt),
        );
    }

    public function updateName(UserName $name): void
    {
        $this->name = $name;
        $this->touch();
    }

    public function updateEmail(Email $email): void
    {
        $this->email = $email;
        $this->touch();
    }

    public function updateRole(UserRole $role): void
    {
        $this->role = $role;
        $this->touch();
    }

    public function updateImageSrc(?string $imageSrc): void
    {
        $this->imageSrc = $imageSrc;
        $this->touch();
    }

    public function changePassword(PasswordHash $passwordHash): void
    {
        $this->passwordHash = $passwordHash;
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

    public function role(): UserRole
    {
        return $this->role;
    }

    public function name(): UserName
    {
        return $this->name;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function passwordHash(): PasswordHash
    {
        return $this->passwordHash;
    }

    public function pinHash(): ?UserPinHash
    {
        return $this->pinHash;
    }

    public function imageSrc(): ?string
    {
        return $this->imageSrc;
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
