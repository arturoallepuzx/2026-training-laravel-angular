<?php

declare(strict_types=1);

namespace App\User\Application\LoginUser;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\User\Domain\Entity\User;
use App\User\Domain\ValueObject\IssuedAuthentication;

final class LoginUserResponse
{
    private function __construct(
        private string $userId,
        private string $restaurantId,
        private string $role,
        private string $name,
        private string $email,
        private ?string $imageSrc,
        private string $accessToken,
        private DomainDateTime $accessTokenExpiresAt,
        private string $refreshToken,
        private DomainDateTime $refreshTokenExpiresAt,
    ) {}

    public static function create(
        User $user,
        IssuedAuthentication $issuedAuthentication,
    ): self {
        return new self(
            userId: $user->id()->value(),
            restaurantId: $user->restaurantId()->value(),
            role: $user->role()->value(),
            name: $user->name()->value(),
            email: $user->email()->value(),
            imageSrc: $user->imageSrc(),
            accessToken: $issuedAuthentication->accessToken(),
            accessTokenExpiresAt: $issuedAuthentication->accessTokenExpiresAt(),
            refreshToken: $issuedAuthentication->refreshToken(),
            refreshTokenExpiresAt: $issuedAuthentication->refreshTokenExpiresAt(),
        );
    }

    public function refreshToken(): string
    {
        return $this->refreshToken;
    }

    public function refreshTokenExpiresAt(): DomainDateTime
    {
        return $this->refreshTokenExpiresAt;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'user' => [
                'id' => $this->userId,
                'restaurant_id' => $this->restaurantId,
                'role' => $this->role,
                'name' => $this->name,
                'email' => $this->email,
                'image_src' => $this->imageSrc,
            ],
            'access_token' => $this->accessToken,
            'access_token_expires_at' => $this->accessTokenExpiresAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
