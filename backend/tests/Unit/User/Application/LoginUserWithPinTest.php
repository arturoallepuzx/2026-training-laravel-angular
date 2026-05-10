<?php

declare(strict_types=1);

namespace Tests\Unit\User\Application;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\UserRole;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Application\LoginUserWithPin\LoginUserWithPin;
use App\User\Application\LoginUserWithPin\LoginUserWithPinResponse;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\InvalidCredentialsException;
use App\User\Domain\Interfaces\PinHasherInterface;
use App\User\Domain\Interfaces\UserAuthenticationIssuerInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use App\User\Domain\ValueObject\AuthenticationSubject;
use App\User\Domain\ValueObject\IssuedAuthentication;
use App\User\Domain\ValueObject\PasswordHash;
use App\User\Domain\ValueObject\UserName;
use App\User\Domain\ValueObject\UserPinHash;
use Mockery;
use PHPUnit\Framework\TestCase;

class LoginUserWithPinTest extends TestCase
{
    private const HASHED_PASSWORD = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

    private const HASHED_PIN = '$2y$10$e0NRiAABtmjItdqEGaKYGeqexOPHSjbgWQLJFfh6jUSGH/nVgqUUG';

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_invoke_returns_response_with_user_data_and_tokens_when_pin_is_valid(): void
    {
        $user = $this->buildUser(UserPinHash::create(self::HASHED_PIN));
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

        $useCase = new LoginUserWithPin(
            $this->mockUserRepositoryReturning($user),
            $this->mockPinHasherReturning(true),
            $authenticationIssuer,
        );

        $response = $useCase(
            $user->restaurantId()->value(),
            $user->id()->value(),
            '1234',
        );

        $this->assertInstanceOf(LoginUserWithPinResponse::class, $response);
        $this->assertSame($issuedAuthentication->refreshToken(), $response->refreshToken());
        $this->assertSame([
            'user' => [
                'id' => $user->id()->value(),
                'restaurant_id' => $user->restaurantId()->value(),
                'role' => 'operator',
                'name' => 'Pin User',
                'email' => 'pin@example.com',
                'image_src' => null,
            ],
            'access_token' => $issuedAuthentication->accessToken(),
            'access_token_expires_at' => $issuedAuthentication->accessTokenExpiresAt()->format(\DateTimeInterface::ATOM),
        ], $response->toArray());
    }

    public function test_invoke_throws_invalid_credentials_when_user_has_no_pin(): void
    {
        $user = $this->buildUser(null);
        $authenticationIssuer = Mockery::mock(UserAuthenticationIssuerInterface::class);
        $authenticationIssuer->shouldNotReceive('issueFor');

        $useCase = new LoginUserWithPin(
            $this->mockUserRepositoryReturning($user),
            Mockery::mock(PinHasherInterface::class),
            $authenticationIssuer,
        );

        $this->expectException(InvalidCredentialsException::class);

        $useCase($user->restaurantId()->value(), $user->id()->value(), '1234');
    }

    public function test_invoke_throws_invalid_credentials_when_pin_does_not_match(): void
    {
        $user = $this->buildUser(UserPinHash::create(self::HASHED_PIN));
        $authenticationIssuer = Mockery::mock(UserAuthenticationIssuerInterface::class);
        $authenticationIssuer->shouldNotReceive('issueFor');

        $useCase = new LoginUserWithPin(
            $this->mockUserRepositoryReturning($user),
            $this->mockPinHasherReturning(false),
            $authenticationIssuer,
        );

        $this->expectException(InvalidCredentialsException::class);

        $useCase($user->restaurantId()->value(), $user->id()->value(), '9999');
    }

    private function buildUser(?UserPinHash $pinHash): User
    {
        return User::dddCreate(
            Uuid::generate(),
            UserRole::operator(),
            UserName::create('Pin User'),
            Email::create('pin@example.com'),
            PasswordHash::create(self::HASHED_PASSWORD),
            $pinHash,
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
        $mock->shouldReceive('findById')
            ->once()
            ->with(Mockery::type(Uuid::class), Mockery::type(Uuid::class))
            ->andReturn($user);

        return $mock;
    }

    private function mockPinHasherReturning(bool $verifies): PinHasherInterface
    {
        $mock = Mockery::mock(PinHasherInterface::class);
        $mock->shouldReceive('verify')
            ->once()
            ->with($verifies ? '1234' : '9999', Mockery::type(UserPinHash::class))
            ->andReturn($verifies);

        return $mock;
    }
}
