<?php

declare(strict_types=1);

namespace Tests\Unit\User\Application;

use App\Shared\Domain\ValueObject\UserRole;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Application\ForceLogoutUser\ForceLogoutUser;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Interfaces\UserAuthenticationGlobalRevokerInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use App\User\Domain\ValueObject\PasswordHash;
use Mockery;
use PHPUnit\Framework\TestCase;

class ForceLogoutUserTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_invoke_revokes_all_sessions_when_target_user_belongs_to_restaurant(): void
    {
        $restaurantId = Uuid::generate();
        $targetUserId = Uuid::generate();
        $targetUser = $this->buildUser($targetUserId, $restaurantId);

        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $userRepository->shouldReceive('findById')
            ->once()
            ->with(
                Mockery::on(fn (Uuid $id): bool => $id->value() === $targetUserId->value()),
                Mockery::on(fn (Uuid $rid): bool => $rid->value() === $restaurantId->value()),
            )
            ->andReturn($targetUser);

        $globalRevoker = Mockery::mock(UserAuthenticationGlobalRevokerInterface::class);
        $globalRevoker->shouldReceive('revokeAllByUserId')
            ->once()
            ->with(Mockery::on(fn (Uuid $id): bool => $id->value() === $targetUserId->value()));

        $useCase = new ForceLogoutUser($userRepository, $globalRevoker);

        $useCase($restaurantId->value(), $targetUserId->value());

        $this->addToAssertionCount(1);
    }

    public function test_invoke_throws_user_not_found_when_target_user_does_not_belong_to_restaurant(): void
    {
        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $userRepository->shouldReceive('findById')->once()->andReturn(null);

        $globalRevoker = Mockery::mock(UserAuthenticationGlobalRevokerInterface::class);
        $globalRevoker->shouldNotReceive('revokeAllByUserId');

        $useCase = new ForceLogoutUser($userRepository, $globalRevoker);

        $this->expectException(UserNotFoundException::class);

        $useCase(Uuid::generate()->value(), Uuid::generate()->value());
    }

    private function buildUser(Uuid $id, Uuid $restaurantId): User
    {
        return User::fromPersistence(
            id: $id->value(),
            restaurantId: $restaurantId->value(),
            role: UserRole::operator()->value(),
            name: 'Target User',
            email: 'target@example.com',
            passwordHash: PasswordHash::create('$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')->value(),
            pinHash: null,
            imageSrc: null,
            createdAt: new \DateTimeImmutable,
            updatedAt: new \DateTimeImmutable,
        );
    }
}
