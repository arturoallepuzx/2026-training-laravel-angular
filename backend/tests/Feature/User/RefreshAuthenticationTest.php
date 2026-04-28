<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use App\Auth\Domain\Exception\InvalidRefreshTokenException;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\User\Application\RefreshAuthentication\RefreshAuthentication;
use App\User\Application\RefreshAuthentication\RefreshAuthenticationResponse;
use App\User\Domain\ValueObject\IssuedAuthentication;
use Mockery;
use Symfony\Component\HttpFoundation\Cookie;
use Tests\TestCase;

class RefreshAuthenticationTest extends TestCase
{
    private const RESTAURANT_ID = '550e8400-e29b-41d4-a716-446655440000';

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_post_refresh_returns_new_access_in_json_and_rotates_refresh_cookie(): void
    {
        $issuedAuthentication = IssuedAuthentication::create(
            'new-jwt',
            DomainDateTime::create(new \DateTimeImmutable('2026-12-01T12:00:00+00:00')),
            'new-refresh-credential',
            DomainDateTime::create(new \DateTimeImmutable('2026-12-31T12:00:00+00:00')),
        );

        $useCase = Mockery::mock(RefreshAuthentication::class);
        $useCase->shouldReceive('__invoke')
            ->once()
            ->with(self::RESTAURANT_ID, 'incoming-refresh-credential')
            ->andReturn(RefreshAuthenticationResponse::create($issuedAuthentication));

        $this->app->instance(RefreshAuthentication::class, $useCase);

        $response = $this->call(
            'POST',
            '/api/restaurants/'.self::RESTAURANT_ID.'/auth/refresh',
            [],
            ['refresh_token' => 'incoming-refresh-credential'],
            [],
            ['HTTP_ACCEPT' => 'application/json'],
        );

        $response->assertStatus(200);
        $response->assertJson([
            'access_token' => 'new-jwt',
            'access_token_expires_at' => '2026-12-01T12:00:00+00:00',
        ]);

        $payload = $response->json();
        $this->assertArrayNotHasKey('refresh_token', $payload);
        $this->assertArrayNotHasKey('refresh_credential', $payload);

        $rotatedCookie = $this->findCookie($response->headers->getCookies(), 'refresh_token');

        $this->assertNotNull($rotatedCookie);
        $this->assertSame('new-refresh-credential', $rotatedCookie->getValue());
        $this->assertTrue($rotatedCookie->isHttpOnly());
        $this->assertSame('lax', strtolower((string) $rotatedCookie->getSameSite()));
        $this->assertSame('/api/restaurants/'.self::RESTAURANT_ID.'/auth', $rotatedCookie->getPath());
    }

    public function test_post_refresh_passes_empty_credential_to_use_case_when_cookie_missing(): void
    {
        $useCase = Mockery::mock(RefreshAuthentication::class);
        $useCase->shouldReceive('__invoke')
            ->once()
            ->with(self::RESTAURANT_ID, '')
            ->andThrow(InvalidRefreshTokenException::notFound());

        $this->app->instance(RefreshAuthentication::class, $useCase);

        $response = $this->postJson('/api/restaurants/'.self::RESTAURANT_ID.'/auth/refresh');

        $response->assertStatus(401);
    }

    public function test_post_refresh_returns_401_when_use_case_throws_invalid_refresh_token(): void
    {
        $useCase = Mockery::mock(RefreshAuthentication::class);
        $useCase->shouldReceive('__invoke')
            ->once()
            ->andThrow(InvalidRefreshTokenException::notFound());

        $this->app->instance(RefreshAuthentication::class, $useCase);

        $response = $this->call(
            'POST',
            '/api/restaurants/'.self::RESTAURANT_ID.'/auth/refresh',
            [],
            ['refresh_token' => 'tampered'],
            [],
            ['HTTP_ACCEPT' => 'application/json'],
        );

        $response->assertStatus(401);
    }

    /**
     * @param  list<Cookie>  $cookies
     */
    private function findCookie(array $cookies, string $name): ?Cookie
    {
        foreach ($cookies as $cookie) {
            if ($cookie->getName() === $name) {
                return $cookie;
            }
        }

        return null;
    }
}
