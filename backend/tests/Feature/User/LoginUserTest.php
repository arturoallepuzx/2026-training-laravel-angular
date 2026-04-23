<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\UserRole;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Application\LoginUser\LoginUser;
use App\User\Application\LoginUser\LoginUserResponse;
use App\User\Domain\Entity\User;
use App\User\Domain\ValueObject\IssuedAuthentication;
use App\User\Domain\ValueObject\PasswordHash;
use App\User\Domain\ValueObject\UserName;
use Mockery;
use Symfony\Component\HttpFoundation\Cookie;
use Tests\TestCase;

class LoginUserTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_post_login_returns_json_and_sets_refresh_cookie_without_exposing_refresh_token(): void
    {
        $restaurantId = '550e8400-e29b-41d4-a716-446655440000';

        $user = User::dddCreate(
            Uuid::create($restaurantId),
            UserRole::operator(),
            UserName::create('Waiter'),
            Email::create('waiter@example.com'),
            PasswordHash::create('$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
            null,
            null,
        );

        $issuedAuthentication = IssuedAuthentication::create(
            'jwt-token',
            DomainDateTime::create(new \DateTimeImmutable('2026-12-01T12:00:00+00:00')),
            'refresh-token-raw',
            DomainDateTime::create(new \DateTimeImmutable('2026-12-31T12:00:00+00:00')),
        );

        $useCase = Mockery::mock(LoginUser::class);
        $useCase->shouldReceive('__invoke')
            ->once()
            ->with($restaurantId, 'waiter@example.com', 'plain-password')
            ->andReturn(LoginUserResponse::create($user, $issuedAuthentication));

        $this->app->instance(LoginUser::class, $useCase);

        $response = $this->postJson("/api/restaurants/{$restaurantId}/auth/login", [
            'email' => 'waiter@example.com',
            'password' => 'plain-password',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'user' => [
                'id' => $user->id()->value(),
                'restaurant_id' => $restaurantId,
                'role' => 'operator',
                'name' => 'Waiter',
                'email' => 'waiter@example.com',
                'image_src' => null,
            ],
            'access_token' => 'jwt-token',
            'access_token_expires_at' => '2026-12-01T12:00:00+00:00',
        ]);

        $payload = $response->json();
        $this->assertArrayNotHasKey('refresh_token', $payload);
        $this->assertArrayNotHasKey('refresh_token_expires_at', $payload);

        $refreshCookie = $this->findCookie($response->headers->getCookies(), 'refresh_token');

        $this->assertNotNull($refreshCookie);
        $this->assertSame('refresh-token-raw', $refreshCookie->getValue());
        $this->assertTrue($refreshCookie->isHttpOnly());
        $this->assertFalse($refreshCookie->isSecure());
        $this->assertSame('lax', strtolower((string) $refreshCookie->getSameSite()));
        $this->assertSame('/api/restaurants/'.$restaurantId.'/auth', $refreshCookie->getPath());
    }

    public function test_post_login_returns_validation_errors_when_payload_is_invalid(): void
    {
        $restaurantId = '550e8400-e29b-41d4-a716-446655440000';

        $response = $this->postJson("/api/restaurants/{$restaurantId}/auth/login", []);

        $response->assertStatus(422);
        $response->assertJsonPath('error', 'The given data was invalid.');
        $response->assertJsonStructure([
            'error',
            'details' => ['email', 'password'],
        ]);
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
