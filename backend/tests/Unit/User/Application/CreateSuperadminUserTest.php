<?php

declare(strict_types=1);

namespace Tests\Unit\User\Application;

use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Application\CreateSuperadminUser\CreateSuperadminUser;
use App\User\Application\CreateSuperadminUser\CreateSuperadminUserResponse;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserEmailAlreadyExistsException;
use App\User\Domain\Interfaces\PasswordHasherInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use App\User\Domain\ValueObject\PasswordHash;
use Mockery;
use PHPUnit\Framework\TestCase;

class CreateSuperadminUserTest extends TestCase
{
    private const HASHED_PASSWORD = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_invoke_resolves_superadmin_restaurant_creates_superadmin_and_persists_user(): void
    {
        $superadminRestaurantId = Uuid::create('550e8400-e29b-41d4-a716-446655440000');

        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $passwordHasher = Mockery::mock(PasswordHasherInterface::class);

        $userRepository->shouldReceive('existsByEmail')
            ->once()
            ->with(Mockery::on(fn (Email $email): bool => $email->value() === 'superadmin-two@yurest.local'))
            ->andReturn(false);

        $passwordHasher->shouldReceive('hash')
            ->once()
            ->with('plain-password')
            ->andReturn(PasswordHash::create(self::HASHED_PASSWORD));

        $userRepository->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function (User $user) use ($superadminRestaurantId): bool {
                return $user->restaurantId()->value() === $superadminRestaurantId->value()
                    && $user->role()->isSuperadmin()
                    && $user->name()->value() === 'Second Superadmin'
                    && $user->email()->value() === 'superadmin-two@yurest.local'
                    && $user->passwordHash()->value() === self::HASHED_PASSWORD
                    && $user->pinHash() === null
                    && $user->imageSrc() === null;
            }));

        $useCase = new CreateSuperadminUser(
            $userRepository,
            $passwordHasher,
        );

        $response = $useCase(
            $superadminRestaurantId->value(),
            'Second Superadmin',
            'superadmin-two@yurest.local',
            'plain-password',
        );

        $this->assertInstanceOf(CreateSuperadminUserResponse::class, $response);
        $this->assertSame('Second Superadmin', $response->name);
        $this->assertSame('superadmin-two@yurest.local', $response->email);
    }

    public function test_invoke_throws_when_email_already_exists(): void
    {
        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $passwordHasher = Mockery::mock(PasswordHasherInterface::class);

        $userRepository->shouldReceive('existsByEmail')
            ->once()
            ->with(Mockery::on(fn (Email $email): bool => $email->value() === 'taken@yurest.local'))
            ->andReturn(true);

        $passwordHasher->shouldNotReceive('hash');
        $userRepository->shouldNotReceive('create');

        $useCase = new CreateSuperadminUser(
            $userRepository,
            $passwordHasher,
        );

        $this->expectException(UserEmailAlreadyExistsException::class);

        $useCase(Uuid::generate()->value(), 'Taken Superadmin', 'taken@yurest.local', 'plain-password');
    }
}
