<?php

declare(strict_types=1);

namespace Tests\Unit\User\Application;

use App\Shared\Domain\ValueObject\Uuid;
use App\User\Application\LogoutUser\LogoutUser;
use App\User\Domain\Interfaces\UserAuthenticationRevokerInterface;
use Mockery;
use PHPUnit\Framework\TestCase;

class LogoutUserTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_invoke_delegates_to_revoker_with_uuid_and_credential(): void
    {
        $restaurantId = Uuid::generate();

        $revoker = Mockery::mock(UserAuthenticationRevokerInterface::class);
        $revoker->shouldReceive('revokeForRestaurant')
            ->once()
            ->with(
                Mockery::on(fn (Uuid $id): bool => $id->value() === $restaurantId->value()),
                'incoming-refresh-credential',
            );

        $useCase = new LogoutUser($revoker);

        $useCase($restaurantId->value(), 'incoming-refresh-credential');

        $this->addToAssertionCount(1);
    }

    public function test_invoke_passes_empty_credential_when_received(): void
    {
        $restaurantId = Uuid::generate();

        $revoker = Mockery::mock(UserAuthenticationRevokerInterface::class);
        $revoker->shouldReceive('revokeForRestaurant')
            ->once()
            ->with(Mockery::type(Uuid::class), '');

        $useCase = new LogoutUser($revoker);

        $useCase($restaurantId->value(), '');

        $this->addToAssertionCount(1);
    }
}
