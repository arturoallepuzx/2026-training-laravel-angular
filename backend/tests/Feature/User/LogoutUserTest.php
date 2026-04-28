<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use App\User\Application\LogoutUser\LogoutUser;
use Mockery;
use Symfony\Component\HttpFoundation\Cookie;
use Tests\TestCase;

class LogoutUserTest extends TestCase
{
    private const RESTAURANT_ID = '550e8400-e29b-41d4-a716-446655440000';

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_post_logout_returns_204_and_clears_refresh_cookie_when_credential_present(): void
    {
        $useCase = Mockery::mock(LogoutUser::class);
        $useCase->shouldReceive('__invoke')
            ->once()
            ->with(self::RESTAURANT_ID, 'incoming-refresh-credential');

        $this->app->instance(LogoutUser::class, $useCase);

        $response = $this->call(
            'POST',
            '/api/restaurants/'.self::RESTAURANT_ID.'/auth/logout',
            [],
            ['refresh_token' => 'incoming-refresh-credential'],
            [],
            ['HTTP_ACCEPT' => 'application/json'],
        );

        $response->assertStatus(204);
        $this->assertSame('', $response->getContent());

        $cleared = $this->findCookie($response->headers->getCookies(), 'refresh_token');
        $this->assertNotNull($cleared);
        $this->assertSame('', $cleared->getValue());
        $this->assertTrue($cleared->isCleared(), 'cookie expiration must be in the past so the browser drops it');
        $this->assertSame('/api/restaurants/'.self::RESTAURANT_ID.'/auth', $cleared->getPath());
        $this->assertTrue($cleared->isHttpOnly());
        $this->assertSame('lax', strtolower((string) $cleared->getSameSite()));
    }

    public function test_post_logout_is_idempotent_when_cookie_missing(): void
    {
        $useCase = Mockery::mock(LogoutUser::class);
        $useCase->shouldReceive('__invoke')
            ->once()
            ->with(self::RESTAURANT_ID, '');

        $this->app->instance(LogoutUser::class, $useCase);

        $response = $this->postJson('/api/restaurants/'.self::RESTAURANT_ID.'/auth/logout');

        $response->assertStatus(204);
        $cleared = $this->findCookie($response->headers->getCookies(), 'refresh_token');
        $this->assertNotNull($cleared, 'controller must always emit a cleared cookie even when none is sent');
        $this->assertTrue($cleared->isCleared());
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
