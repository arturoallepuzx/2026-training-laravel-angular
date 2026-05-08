<?php

declare(strict_types=1);

namespace Tests\Unit\User;

use App\User\Application\CreateUser\CreateUser;
use App\User\Application\CreateUser\CreateUserResponse;
use App\User\Domain\Entity\User;
use App\User\Domain\Interfaces\PasswordHasherInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use App\User\Domain\ValueObject\PasswordHash;
use Mockery;
use PHPUnit\Framework\TestCase;

class CreateUserTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_invoke_creates_user_persists_via_repository_and_returns_response(): void
    {
        $repository = Mockery::mock(UserRepositoryInterface::class);
        $passwordHasher = Mockery::mock(PasswordHasherInterface::class);

        $hashedPassword = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
        $restaurantId = '550e8400-e29b-41d4-a716-446655440000';

        $repository->shouldReceive('existsByEmail')
            ->once()
            ->andReturn(false);

        $passwordHasher->shouldReceive('hash')
            ->once()
            ->with('plain-password')
            ->andReturn(PasswordHash::create($hashedPassword));

        $repository->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function (User $user) use ($hashedPassword, $restaurantId) {
                return $user->restaurantId()->value() === $restaurantId
                    && $user->role()->isAdmin()
                    && $user->name()->value() === 'Create User'
                    && $user->email()->value() === 'create@example.com'
                    && $user->passwordHash()->value() === $hashedPassword
                    && $user->pin()?->value() === '1234'
                    && $user->imageSrc() === 'avatar.png';
            }));

        $createUser = new CreateUser($repository, $passwordHasher);
        $response = $createUser(
            $restaurantId,
            'admin',
            'Create User',
            'create@example.com',
            'plain-password',
            '1234',
            'avatar.png',
        );

        $this->assertInstanceOf(CreateUserResponse::class, $response);
        $this->assertSame($restaurantId, $response->restaurantId);
        $this->assertSame('admin', $response->role);
        $this->assertSame('Create User', $response->name);
        $this->assertSame('create@example.com', $response->email);
        $this->assertSame('avatar.png', $response->imageSrc);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $response->id
        );
    }
}
