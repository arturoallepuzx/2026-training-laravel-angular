<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\Infrastructure\Http;

use App\Auth\Domain\Exception\ExpiredAccessTokenException;
use App\Auth\Domain\Exception\InvalidAccessTokenException;
use App\Auth\Domain\Interfaces\AccessTokenVerifierInterface;
use App\Auth\Domain\ValueObject\AccessTokenPayload;
use App\Auth\Infrastructure\Http\Middleware\AuthenticateAccessToken;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\UserRole;
use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Auth\AuthContextHolder;
use Illuminate\Http\Request;
use Mockery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAccessTokenTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_throws_invalid_when_authorization_header_missing(): void
    {
        $verifier = Mockery::mock(AccessTokenVerifierInterface::class);
        $verifier->shouldNotReceive('verify');

        $middleware = new AuthenticateAccessToken($verifier, new AuthContextHolder);

        $this->expectException(InvalidAccessTokenException::class);

        $middleware->handle(Request::create('/protected', 'GET'), fn () => new Response);
    }

    public function test_throws_invalid_when_authorization_has_no_bearer_prefix(): void
    {
        $verifier = Mockery::mock(AccessTokenVerifierInterface::class);
        $verifier->shouldNotReceive('verify');

        $middleware = new AuthenticateAccessToken($verifier, new AuthContextHolder);

        $request = Request::create('/protected', 'GET');
        $request->headers->set('Authorization', 'Basic abc');

        $this->expectException(InvalidAccessTokenException::class);

        $middleware->handle($request, fn () => new Response);
    }

    public function test_throws_invalid_when_bearer_token_is_empty(): void
    {
        $verifier = Mockery::mock(AccessTokenVerifierInterface::class);
        $verifier->shouldNotReceive('verify');

        $middleware = new AuthenticateAccessToken($verifier, new AuthContextHolder);

        $request = Request::create('/protected', 'GET');
        $request->headers->set('Authorization', 'Bearer ');

        $this->expectException(InvalidAccessTokenException::class);

        $middleware->handle($request, fn () => new Response);
    }

    public function test_propagates_verifier_exceptions(): void
    {
        $verifier = Mockery::mock(AccessTokenVerifierInterface::class);
        $verifier->shouldReceive('verify')->once()->andThrow(ExpiredAccessTokenException::expired());

        $middleware = new AuthenticateAccessToken($verifier, new AuthContextHolder);

        $request = Request::create('/protected', 'GET');
        $request->headers->set('Authorization', 'Bearer some-token');

        $this->expectException(ExpiredAccessTokenException::class);

        $middleware->handle($request, fn () => new Response);
    }

    public function test_binds_auth_context_and_calls_next_when_token_is_valid(): void
    {
        $userId = Uuid::generate();
        $restaurantId = Uuid::generate();
        $sessionId = Uuid::generate();

        $payload = AccessTokenPayload::create(
            $userId,
            $restaurantId,
            UserRole::admin(),
            $sessionId,
            DomainDateTime::create(new \DateTimeImmutable('2099-01-01 10:00:00')),
            DomainDateTime::create(new \DateTimeImmutable('2099-01-01 10:15:00')),
        );

        $verifier = Mockery::mock(AccessTokenVerifierInterface::class);
        $verifier->shouldReceive('verify')->once()->with('jwt-value')->andReturn($payload);

        $holder = new AuthContextHolder;
        $middleware = new AuthenticateAccessToken($verifier, $holder);

        $request = Request::create('/protected', 'GET');
        $request->headers->set('Authorization', 'Bearer jwt-value');

        $nextCalled = false;
        $response = $middleware->handle($request, function (Request $r) use (&$nextCalled) {
            $nextCalled = true;

            return new Response('next', 200);
        });

        $this->assertTrue($nextCalled);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('next', $response->getContent());

        $context = $holder->get();
        $this->assertNotNull($context);
        $this->assertSame($userId->value(), $context->userId()->value());
        $this->assertSame($restaurantId->value(), $context->restaurantId()->value());
        $this->assertTrue($context->role()->isAdmin());
        $this->assertSame($sessionId->value(), $context->sessionId()->value());
    }
}
