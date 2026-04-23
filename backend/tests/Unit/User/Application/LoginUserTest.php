<?php

declare(strict_types=1);

namespace Tests\Unit\User\Application;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\UserRole;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Application\LoginUser\LoginUser;
use App\User\Application\LoginUser\LoginUserResponse;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\InvalidCredentialsException;
use App\User\Domain\Interfaces\PasswordHasherInterface;
use App\User\Domain\Interfaces\UserAuthenticationIssuerInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use App\User\Domain\ValueObject\AuthenticationSubject;
use App\User\Domain\ValueObject\IssuedAuthentication;
use App\User\Domain\ValueObject\PasswordHash;
use App\User\Domain\ValueObject\UserName;
use Mockery;
use PHPUnit\Framework\TestCase;

class LoginUserTest extends TestCase
{
    private const HASHED_PASSWORD = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_invoke_returns_response_with_user_data_and_tokens_when_credentials_are_valid(): void
    {
        $user = $this->buildUser();
        $issuedAuthentication = $this->buildIssuedAuthentication();

        $authenticationIssuer = Mockery::mock(UserAuthenticationIssuerInterface::class);
        $authenticationIssuer->shouldReceive('issueFor')
            ->once()
            ->with(Mockery::on(function (AuthenticationSubject $subject) use ($user): bool {
                return $subject->userId()->value() === $user->id()->value()
                    && $subject->restaurantId()->value() === $user->restaurantId()->value()
                    && $subject->role()->value() === $user->role()->value();
            }))
            ->andReturn($issuedAuthentication);

        $useCase = new LoginUser(
            $this->mockUserRepositoryReturning($user),
            $this->mockPasswordHasherReturning(true),
            $authenticationIssuer,
        );

        $response = $useCase(
            $user->restaurantId()->value(),
            'user@example.com',
            'plain-password',
        );

        $this->assertInstanceOf(LoginUserResponse::class, $response);

        $this->assertSame([
            'user' => [
                'id' => $user->id()->value(),
                'restaurant_id' => $user->restaurantId()->value(),
                'role' => 'admin',
                'name' => 'Logged User',
                'email' => 'user@example.com',
                'image_src' => null,
            ],
            'access_token' => $issuedAuthentication->accessToken(),
            'access_token_expires_at' => $issuedAuthentication->accessTokenExpiresAt()->format(\DateTimeInterface::ATOM),
        ], $response->toArray());

        $this->assertSame($issuedAuthentication->refreshToken(), $response->refreshToken());
        $this->assertEquals(
            $issuedAuthentication->refreshTokenExpiresAt()->value(),
            $response->refreshTokenExpiresAt()->value(),
        );
    }

    public function test_invoke_throws_invalid_credentials_when_user_not_found(): void
    {
        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $userRepository->shouldReceive('findByEmail')->once()->andReturn(null);

        $passwordHasher = Mockery::mock(PasswordHasherInterface::class);
        $passwordHasher->shouldNotReceive('verify');

        $authenticationIssuer = Mockery::mock(UserAuthenticationIssuerInterface::class);
        $authenticationIssuer->shouldNotReceive('issueFor');

        $useCase = new LoginUser($userRepository, $passwordHasher, $authenticationIssuer);

        $this->expectException(InvalidCredentialsException::class);

        $useCase(Uuid::generate()->value(), 'ghost@example.com', 'plain-password');
    }

    public function test_invoke_throws_invalid_credentials_when_password_does_not_match(): void
    {
        $user = $this->buildUser();

        $authenticationIssuer = Mockery::mock(UserAuthenticationIssuerInterface::class);
        $authenticationIssuer->shouldNotReceive('issueFor');

        $useCase = new LoginUser(
            $this->mockUserRepositoryReturning($user),
            $this->mockPasswordHasherReturning(false),
            $authenticationIssuer,
        );

        $this->expectException(InvalidCredentialsException::class);

        $useCase($user->restaurantId()->value(), 'user@example.com', 'wrong-password');
    }

    public function test_response_to_array_does_not_expose_refresh_token(): void
    {
        $user = $this->buildUser();
        $issuedAuthentication = $this->buildIssuedAuthentication();

        $authenticationIssuer = Mockery::mock(UserAuthenticationIssuerInterface::class);
        $authenticationIssuer->shouldReceive('issueFor')->once()->andReturn($issuedAuthentication);

        $useCase = new LoginUser(
            $this->mockUserRepositoryReturning($user),
            $this->mockPasswordHasherReturning(true),
            $authenticationIssuer,
        );

        $response = $useCase($user->restaurantId()->value(), 'user@example.com', 'plain-password');
        $array = $response->toArray();
        $flat = json_encode($array);

        $this->assertArrayNotHasKey('refresh_token', $array);
        $this->assertArrayNotHasKey('refresh_token_expires_at', $array);
        $this->assertStringNotContainsString($issuedAuthentication->refreshToken(), (string) $flat);
    }

    private function buildUser(): User
    {
        return User::dddCreate(
            Uuid::generate(),
            UserRole::admin(),
            UserName::create('Logged User'),
            Email::create('user@example.com'),
            PasswordHash::create(self::HASHED_PASSWORD),
            null,
            null,
        );
    }

    private function buildIssuedAuthentication(): IssuedAuthentication
    {
        return IssuedAuthentication::create(
            'jwt-value',
            DomainDateTime::create((new \DateTimeImmutable)->modify('+15 minutes')),
            'refresh-token-value',
            DomainDateTime::create((new \DateTimeImmutable)->modify('+30 days')),
        );
    }

    private function mockUserRepositoryReturning(User $user): UserRepositoryInterface
    {
        $mock = Mockery::mock(UserRepositoryInterface::class);
        $mock->shouldReceive('findByEmail')
            ->once()
            ->with(Mockery::type(Email::class), Mockery::type(Uuid::class))
            ->andReturn($user);

        return $mock;
    }

    private function mockPasswordHasherReturning(bool $verifies): PasswordHasherInterface
    {
        $mock = Mockery::mock(PasswordHasherInterface::class);
        $mock->shouldReceive('verify')
            ->once()
            ->andReturn($verifies);

        return $mock;
    }
}
