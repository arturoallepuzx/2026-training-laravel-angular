<?php

declare(strict_types=1);

namespace App\User\Domain\ValueObject;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\UserRole;
use App\Shared\Domain\ValueObject\Uuid;

final class UserActiveSessionsSummary
{
    private function __construct(
        private Uuid $userId,
        private UserName $name,
        private Email $email,
        private UserRole $role,
        private ?string $imageSrc,
        private int $activeSessions,
        private DomainDateTime $lastSeenAt,
    ) {
        if ($activeSessions <= 0) {
            throw new \InvalidArgumentException('Active sessions count must be greater than 0.');
        }
    }

    public static function create(
        Uuid $userId,
        UserName $name,
        Email $email,
        UserRole $role,
        ?string $imageSrc,
        int $activeSessions,
        DomainDateTime $lastSeenAt,
    ): self {
        return new self($userId, $name, $email, $role, $imageSrc, $activeSessions, $lastSeenAt);
    }

    public function userId(): Uuid
    {
        return $this->userId;
    }

    public function name(): UserName
    {
        return $this->name;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function role(): UserRole
    {
        return $this->role;
    }

    public function imageSrc(): ?string
    {
        return $this->imageSrc;
    }

    public function activeSessions(): int
    {
        return $this->activeSessions;
    }

    public function lastSeenAt(): DomainDateTime
    {
        return $this->lastSeenAt;
    }
}
