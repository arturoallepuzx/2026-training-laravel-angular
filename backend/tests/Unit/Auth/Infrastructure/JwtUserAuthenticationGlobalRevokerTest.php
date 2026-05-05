<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\Infrastructure;

use App\Auth\Domain\Interfaces\RefreshTokenRepositoryInterface;
use App\Auth\Infrastructure\Services\JwtUserAuthenticationGlobalRevoker;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use PHPUnit\Framework\TestCase;

class JwtUserAuthenticationGlobalRevokerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_revoke_all_by_user_id_delegates_to_refresh_token_repository(): void
    {
        $userId = Uuid::generate();

        $refreshTokenRepository = Mockery::mock(RefreshTokenRepositoryInterface::class);
        $refreshTokenRepository->shouldReceive('revokeAllByUserId')
            ->once()
            ->with(Mockery::on(fn (Uuid $id): bool => $id->value() === $userId->value()));

        $revoker = new JwtUserAuthenticationGlobalRevoker($refreshTokenRepository);

        $revoker->revokeAllByUserId($userId);

        $this->addToAssertionCount(1);
    }
}
