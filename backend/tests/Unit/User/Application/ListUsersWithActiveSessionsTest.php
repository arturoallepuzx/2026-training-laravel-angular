<?php

declare(strict_types=1);

namespace Tests\Unit\User\Application;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\UserRole;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Application\ListUsersWithActiveSessions\ListUsersWithActiveSessions;
use App\User\Domain\Interfaces\UserActiveSessionsFinderInterface;
use App\User\Domain\ValueObject\UserActiveSessionsSummary;
use App\User\Domain\ValueObject\UserName;
use Mockery;
use PHPUnit\Framework\TestCase;

class ListUsersWithActiveSessionsTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_invoke_returns_users_with_active_sessions_without_sensitive_fields(): void
    {
        $restaurantId = Uuid::generate();
        $userId = Uuid::generate();

        $summary = UserActiveSessionsSummary::create(
            $userId,
            UserName::create('Juan'),
            Email::create('juan@tpv.test'),
            UserRole::operator(),
            null,
            2,
            DomainDateTime::create(new \DateTimeImmutable('2026-05-05T12:00:00+00:00')),
        );

        $finder = Mockery::mock(UserActiveSessionsFinderInterface::class);
        $finder->shouldReceive('findUsersWithActiveSessionsByRestaurantId')
            ->once()
            ->with(Mockery::on(fn (Uuid $id): bool => $id->value() === $restaurantId->value()))
            ->andReturn([$summary]);

        $response = (new ListUsersWithActiveSessions($finder))($restaurantId->value());
        $payload = $response->toArray();

        $this->assertSame($userId->value(), $payload['users'][0]['id']);
        $this->assertSame('Juan', $payload['users'][0]['name']);
        $this->assertSame('juan@tpv.test', $payload['users'][0]['email']);
        $this->assertSame('operator', $payload['users'][0]['role']);
        $this->assertNull($payload['users'][0]['image_src']);
        $this->assertSame(2, $payload['users'][0]['active_sessions']);
        $this->assertSame('2026-05-05T12:00:00+00:00', $payload['users'][0]['last_seen_at']);
        $this->assertArrayNotHasKey('token_hash', $payload['users'][0]);
        $this->assertArrayNotHasKey('refresh_token', $payload['users'][0]);
        $this->assertArrayNotHasKey('session_uuid', $payload['users'][0]);
    }
}
