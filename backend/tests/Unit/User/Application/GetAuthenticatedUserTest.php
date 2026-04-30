<?php

declare(strict_types=1);

namespace Tests\Unit\User\Application;

use App\Shared\Domain\Exception\AuthenticationRequiredException;
use App\Shared\Domain\ValueObject\AuthContext;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\UserRole;
use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Auth\AuthContextHolder;
use App\User\Application\GetAuthenticatedUser\GetAuthenticatedUser;
use App\User\Application\GetAuthenticatedUser\GetAuthenticatedUserResponse;
use App\User\Domain\Entity\User;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use App\User\Domain\ValueObject\PasswordHash;
use App\User\Domain\ValueObject\UserName;
use Mockery;
use PHPUnit\Framework\TestCase;

class GetAuthenticatedUserTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_invoke_returns_response_with_user_data_when_context_and_user_exist(): void
    {
        $restaurantId = Uuid::generate();
        $userId = Uuid::generate();

        $holder = new AuthContextHolder;
        $holder->bind(AuthContext::create($userId, $restaurantId, UserRole::admin(), Uuid::generate()));

        $user = $this->buildUser($userId, $restaurantId);

        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $userRepository->shouldReceive('findById')
            ->once()
            ->with(
                Mockery::on(fn (Uuid $id): bool => $id->value() === $userId->value()),
                Mockery::on(fn (Uuid $rid): bool => $rid->value() === $restaurantId->value()),
            )
            ->andReturn($user);

        $useCase = new GetAuthenticatedUser($holder, $userRepository);

        $response = $useCase($restaurantId->value());

        $this->assertInstanceOf(GetAuthenticatedUserResponse::class, $response);
        $this->assertSame([
            'user' => [
                'id' => $userId->value(),
                'restaurant_id' => $restaurantId->value(),
                'role' => 'admin',
                'name' => 'Authenticated User',
                'email' => 'me@example.com',
                'image_src' => null,
            ],
        ], $response->toArray());
    }

    public function test_invoke_throws_authentication_required_when_no_context_bound(): void
    {
        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $userRepository->shouldNotReceive('findById');

        $useCase = new GetAuthenticatedUser(new AuthContextHolder, $userRepository);

        $this->expectException(AuthenticationRequiredException::class);

        $useCase(Uuid::generate()->value());
    }

    public function test_invoke_throws_authentication_required_when_user_not_found_in_restaurant(): void
    {
        $holder = new AuthContextHolder;
        $holder->bind(AuthContext::create(
            Uuid::generate(),
            Uuid::generate(),
            UserRole::operator(),
            Uuid::generate(),
        ));

        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $userRepository->shouldReceive('findById')->once()->andReturn(null);

        $useCase = new GetAuthenticatedUser($holder, $userRepository);

        $this->expectException(AuthenticationRequiredException::class);

        $useCase(Uuid::generate()->value());
    }

    private function buildUser(Uuid $id, Uuid $restaurantId): User
    {
        return User::fromPersistence(
            id: $id->value(),
            restaurantId: $restaurantId->value(),
            role: 'admin',
            name: 'Authenticated User',
            email: 'me@example.com',
            passwordHash: PasswordHash::create('$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')->value(),
            pin: null,
            imageSrc: null,
            createdAt: new \DateTimeImmutable,
            updatedAt: new \DateTimeImmutable,
        );
    }
}
