<?php

declare(strict_types=1);

namespace Tests\Unit\User\Application;

use App\Shared\Domain\ValueObject\Uuid;
use App\User\Application\LogoutAllUserSessions\LogoutAllUserSessions;
use App\User\Domain\Interfaces\UserAuthenticationGlobalRevokerInterface;
use Mockery;
use PHPUnit\Framework\TestCase;

class LogoutAllUserSessionsTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_invoke_delegates_to_global_revoker_with_user_uuid(): void
    {
        $userId = Uuid::generate();

        $globalRevoker = Mockery::mock(UserAuthenticationGlobalRevokerInterface::class);
        $globalRevoker->shouldReceive('revokeAllByUserId')
            ->once()
            ->with(Mockery::on(fn (Uuid $id): bool => $id->value() === $userId->value()));

        $useCase = new LogoutAllUserSessions($globalRevoker);

        $useCase($userId->value());

        $this->addToAssertionCount(1);
    }
}
