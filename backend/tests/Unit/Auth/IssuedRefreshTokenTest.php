<?php

namespace Tests\Unit\Auth;

use App\Auth\Domain\Entity\RefreshToken;
use App\Auth\Domain\ValueObject\IssuedRefreshToken;
use App\Auth\Domain\ValueObject\RefreshTokenSecret;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

class IssuedRefreshTokenTest extends TestCase
{
    private const VALID_SECRET = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQ';

    public function test_stores_entity_and_secret(): void
    {
        $secret = RefreshTokenSecret::create(self::VALID_SECRET);
        $entity = RefreshToken::dddCreate(
            Uuid::generate(),
            Uuid::generate(),
            $secret,
            DomainDateTime::create(new \DateTimeImmutable('+1 hour')),
        );

        $issued = IssuedRefreshToken::create($entity, $secret);

        $this->assertSame($entity, $issued->entity());
        $this->assertSame($secret, $issued->secret());
    }
}
