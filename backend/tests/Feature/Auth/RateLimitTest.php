<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Auth\Domain\Exception\InvalidRefreshTokenException;
use App\User\Application\LoginUser\LoginUser;
use App\User\Application\LogoutUser\LogoutUser;
use App\User\Application\RefreshAuthentication\RefreshAuthentication;
use App\User\Domain\Exception\InvalidCredentialsException;
use Mockery;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    private const RESTAURANT_ID = '550e8400-e29b-41d4-a716-446655440000';

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_login_route_is_rate_limited_after_five_attempts_per_minute(): void
    {
        $useCase = Mockery::mock(LoginUser::class);
        $useCase->shouldReceive('__invoke')
            ->times(5)
            ->with(self::RESTAURANT_ID, 'rate@example.com', 'wrong-password')
            ->andThrow(InvalidCredentialsException::invalid());

        $this->app->instance(LoginUser::class, $useCase);
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1']);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $response = $this->postJson('/api/restaurants/'.self::RESTAURANT_ID.'/auth/login', [
                'email' => 'rate@example.com',
                'password' => 'wrong-password',
            ]);

            $this->assertNotSame(429, $response->getStatusCode());
        }

        $response = $this->postJson('/api/restaurants/'.self::RESTAURANT_ID.'/auth/login', [
            'email' => 'rate@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(429);
    }

    public function test_refresh_route_is_rate_limited_after_thirty_attempts_per_minute(): void
    {
        $useCase = Mockery::mock(RefreshAuthentication::class);
        $useCase->shouldReceive('__invoke')
            ->times(30)
            ->with(self::RESTAURANT_ID, '')
            ->andThrow(InvalidRefreshTokenException::notFound());

        $this->app->instance(RefreshAuthentication::class, $useCase);
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2']);

        for ($attempt = 1; $attempt <= 30; $attempt++) {
            $response = $this->postJson('/api/restaurants/'.self::RESTAURANT_ID.'/auth/refresh');

            $this->assertNotSame(429, $response->getStatusCode());
        }

        $response = $this->postJson('/api/restaurants/'.self::RESTAURANT_ID.'/auth/refresh');

        $response->assertStatus(429);
    }

    public function test_logout_route_is_rate_limited_after_thirty_attempts_per_minute(): void
    {
        $useCase = Mockery::mock(LogoutUser::class);
        $useCase->shouldReceive('__invoke')
            ->times(30)
            ->with(self::RESTAURANT_ID, '');

        $this->app->instance(LogoutUser::class, $useCase);
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.3']);

        for ($attempt = 1; $attempt <= 30; $attempt++) {
            $response = $this->postJson('/api/restaurants/'.self::RESTAURANT_ID.'/auth/logout');

            $response->assertStatus(204);
        }

        $response = $this->postJson('/api/restaurants/'.self::RESTAURANT_ID.'/auth/logout');

        $response->assertStatus(429);
    }
}
