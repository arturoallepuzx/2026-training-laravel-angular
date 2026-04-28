<?php

declare(strict_types=1);

namespace Tests\Unit\User\Application;

use App\Auth\Domain\Exception\InvalidRefreshTokenException;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Application\RefreshAuthentication\RefreshAuthentication;
use App\User\Application\RefreshAuthentication\RefreshAuthenticationResponse;
use App\User\Domain\Interfaces\UserAuthenticationRefresherInterface;
use App\User\Domain\ValueObject\IssuedAuthentication;
use Mockery;
use PHPUnit\Framework\TestCase;

class RefreshAuthenticationTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_invoke_returns_response_with_rotated_pair(): void
    {
        $restaurantId = Uuid::generate();
        $issuedAuthentication = $this->buildIssuedAuthentication();

        $refresher = Mockery::mock(UserAuthenticationRefresherInterface::class);
        $refresher->shouldReceive('refreshForRestaurant')
            ->once()
            ->with(
                Mockery::on(fn (Uuid $id): bool => $id->value() === $restaurantId->value()),
                'incoming-refresh-credential',
            )
            ->andReturn($issuedAuthentication);

        $useCase = new RefreshAuthentication($refresher);

        $response = $useCase($restaurantId->value(), 'incoming-refresh-credential');

        $this->assertInstanceOf(RefreshAuthenticationResponse::class, $response);
        $this->assertSame([
            'access_token' => 'new-jwt',
            'access_token_expires_at' => $issuedAuthentication->accessTokenExpiresAt()->format(\DateTimeInterface::ATOM),
        ], $response->toArray());

        $this->assertSame('new-refresh-credential', $response->refreshCredential());
        $this->assertEquals(
            $issuedAuthentication->refreshTokenExpiresAt()->value(),
            $response->refreshCredentialExpiresAt()->value(),
        );
    }

    public function test_invoke_propagates_refresher_exceptions(): void
    {
        $refresher = Mockery::mock(UserAuthenticationRefresherInterface::class);
        $refresher->shouldReceive('refreshForRestaurant')
            ->once()
            ->andThrow(InvalidRefreshTokenException::notFound());

        $useCase = new RefreshAuthentication($refresher);

        $this->expectException(InvalidRefreshTokenException::class);

        $useCase(Uuid::generate()->value(), 'any');
    }

    public function test_response_to_array_does_not_expose_refresh_credential(): void
    {
        $issuedAuthentication = $this->buildIssuedAuthentication();

        $refresher = Mockery::mock(UserAuthenticationRefresherInterface::class);
        $refresher->shouldReceive('refreshForRestaurant')->once()->andReturn($issuedAuthentication);

        $useCase = new RefreshAuthentication($refresher);

        $response = $useCase(Uuid::generate()->value(), 'incoming-refresh-credential');
        $array = $response->toArray();
        $flat = (string) json_encode($array);

        $this->assertArrayNotHasKey('refresh_token', $array);
        $this->assertArrayNotHasKey('refresh_credential', $array);
        $this->assertStringNotContainsString($issuedAuthentication->refreshToken(), $flat);
    }

    private function buildIssuedAuthentication(): IssuedAuthentication
    {
        return IssuedAuthentication::create(
            'new-jwt',
            DomainDateTime::create((new \DateTimeImmutable)->modify('+15 minutes')),
            'new-refresh-credential',
            DomainDateTime::create((new \DateTimeImmutable)->modify('+30 days')),
        );
    }
}
