<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use App\Auth\Domain\Interfaces\AccessTokenIssuerInterface;
use App\Auth\Domain\ValueObject\AccessTokenPayload;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\UserRole;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Application\LogoutAllUserSessions\LogoutAllUserSessions;
use Firebase\JWT\JWT;
use Mockery;
use Symfony\Component\HttpFoundation\Cookie;
use Tests\TestCase;

class LogoutAllUserSessionsTest extends TestCase
{
    protected function tearDown(): void
    {
        JWT::$timestamp = null;
        Mockery::close();
        parent::tearDown();
    }

    public function test_post_logout_all_returns_204_and_clears_refresh_cookie(): void
    {
        $userId = Uuid::generate();
        $restaurantId = Uuid::generate();
        $token = $this->issueToken($userId, $restaurantId);

        $useCase = Mockery::mock(LogoutAllUserSessions::class);
        $useCase->shouldReceive('__invoke')
            ->once()
            ->with($userId->value());

        $this->app->instance(LogoutAllUserSessions::class, $useCase);

        $response = $this->call(
            'POST',
            '/api/restaurants/'.$restaurantId->value().'/auth/logout-all',
            [],
            ['refresh_token' => 'incoming-refresh-credential'],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer '.$token,
            ],
        );

        $response->assertStatus(204);
        $this->assertSame('', $response->getContent());

        $cleared = $this->findCookie($response->headers->getCookies(), 'refresh_token');
        $this->assertNotNull($cleared);
        $this->assertSame('', $cleared->getValue());
        $this->assertTrue($cleared->isCleared(), 'cookie expiration must be in the past so the browser drops it');
        $this->assertSame('/api/restaurants/'.$restaurantId->value().'/auth', $cleared->getPath());
        $this->assertTrue($cleared->isHttpOnly());
        $this->assertSame('lax', strtolower((string) $cleared->getSameSite()));
    }

    public function test_post_logout_all_requires_access_token(): void
    {
        $restaurantId = Uuid::generate();

        $response = $this->postJson('/api/restaurants/'.$restaurantId->value().'/auth/logout-all');

        $response->assertStatus(401);
    }

    public function test_post_logout_all_requires_token_to_match_restaurant(): void
    {
        $userId = Uuid::generate();
        $tokenRestaurantId = Uuid::generate();
        $urlRestaurantId = Uuid::generate();
        $token = $this->issueToken($userId, $tokenRestaurantId);

        $response = $this->postJson(
            '/api/restaurants/'.$urlRestaurantId->value().'/auth/logout-all',
            [],
            ['Authorization' => 'Bearer '.$token],
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

    private function issueToken(Uuid $userId, Uuid $restaurantId): string
    {
        $issuedAt = DomainDateTime::create(new \DateTimeImmutable('2099-01-01 10:00:00'));
        $expiresAt = DomainDateTime::create(new \DateTimeImmutable('2099-01-01 10:15:00'));

        JWT::$timestamp = $issuedAt->value()->getTimestamp() + 60;

        $payload = AccessTokenPayload::create(
            $userId,
            $restaurantId,
            UserRole::admin(),
            Uuid::generate(),
            $issuedAt,
            $expiresAt,
        );

        return $this->app->make(AccessTokenIssuerInterface::class)->issue($payload)->value();
    }
}
