<?php

declare(strict_types=1);

namespace App\User\Application\RefreshAuthentication;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\User\Domain\ValueObject\IssuedAuthentication;

final class RefreshAuthenticationResponse
{
    private function __construct(
        private string $accessToken,
        private DomainDateTime $accessTokenExpiresAt,
        private string $refreshCredential,
        private DomainDateTime $refreshCredentialExpiresAt,
    ) {}

    public static function create(IssuedAuthentication $issuedAuthentication): self
    {
        return new self(
            accessToken: $issuedAuthentication->accessToken(),
            accessTokenExpiresAt: $issuedAuthentication->accessTokenExpiresAt(),
            refreshCredential: $issuedAuthentication->refreshToken(),
            refreshCredentialExpiresAt: $issuedAuthentication->refreshTokenExpiresAt(),
        );
    }

    public function refreshCredential(): string
    {
        return $this->refreshCredential;
    }

    public function refreshCredentialExpiresAt(): DomainDateTime
    {
        return $this->refreshCredentialExpiresAt;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'access_token' => $this->accessToken,
            'access_token_expires_at' => $this->accessTokenExpiresAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
