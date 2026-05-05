<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use App\Auth\Domain\Interfaces\AccessTokenIssuerInterface;
use App\Auth\Domain\ValueObject\AccessTokenPayload;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\UserRole;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Application\ListUsersWithActiveSessions\ListUsersWithActiveSessions;
use App\User\Application\ListUsersWithActiveSessions\ListUsersWithActiveSessionsResponse;
use App\User\Domain\ValueObject\UserActiveSessionsSummary;
use App\User\Domain\ValueObject\UserName;
use Firebase\JWT\JWT;
use Mockery;
use Tests\TestCase;

class ListUsersWithActiveSessionsTest extends TestCase
{
    protected function tearDown(): void
    {
        JWT::$timestamp = null;
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_users_active_sessions_returns_users_for_admin(): void
    {
        $restaurantId = Uuid::generate();
        $userId = Uuid::generate();
        $token = $this->issueToken(
            restaurantId: $restaurantId,
            role: UserRole::admin(),
        );

        $useCase = Mockery::mock(ListUsersWithActiveSessions::class);
        $useCase->shouldReceive('__invoke')
            ->once()
            ->with($restaurantId->value())
            ->andReturn(ListUsersWithActiveSessionsResponse::create([
                UserActiveSessionsSummary::create(
                    $userId,
                    UserName::create('Juan'),
                    Email::create('juan@tpv.test'),
                    UserRole::operator(),
                    null,
                    2,
                    DomainDateTime::create(new \DateTimeImmutable('2026-05-05T12:00:00+00:00')),
                ),
            ]));

        $this->app->instance(ListUsersWithActiveSessions::class, $useCase);

        $response = $this->getJson(
            '/api/restaurants/'.$restaurantId->value().'/users/active-sessions',
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(200);
        $response->assertJson([
            'users' => [
                [
                    'id' => $userId->value(),
                    'name' => 'Juan',
                    'email' => 'juan@tpv.test',
                    'role' => 'operator',
                    'image_src' => null,
                    'active_sessions' => 2,
                    'last_seen_at' => '2026-05-05T12:00:00+00:00',
                ],
            ],
        ]);
    }

    public function test_get_users_active_sessions_returns_403_for_operator(): void
    {
        $restaurantId = Uuid::generate();
        $token = $this->issueToken(
            restaurantId: $restaurantId,
            role: UserRole::operator(),
        );

        $response = $this->getJson(
            '/api/restaurants/'.$restaurantId->value().'/users/active-sessions',
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(403);
    }

    public function test_get_users_active_sessions_returns_401_without_token(): void
    {
        $restaurantId = Uuid::generate();

        $response = $this->getJson('/api/restaurants/'.$restaurantId->value().'/users/active-sessions');

        $response->assertStatus(401);
    }

    public function test_get_users_active_sessions_returns_401_when_token_belongs_to_different_restaurant(): void
    {
        $tokenRestaurantId = Uuid::generate();
        $urlRestaurantId = Uuid::generate();
        $token = $this->issueToken(
            restaurantId: $tokenRestaurantId,
            role: UserRole::admin(),
        );

        $response = $this->getJson(
            '/api/restaurants/'.$urlRestaurantId->value().'/users/active-sessions',
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(401);
    }

    private function issueToken(Uuid $restaurantId, UserRole $role): string
    {
        $issuedAt = DomainDateTime::create(new \DateTimeImmutable('2099-01-01 10:00:00'));
        $expiresAt = DomainDateTime::create(new \DateTimeImmutable('2099-01-01 10:15:00'));

        JWT::$timestamp = $issuedAt->value()->getTimestamp() + 60;

        $payload = AccessTokenPayload::create(
            Uuid::generate(),
            $restaurantId,
            $role,
            Uuid::generate(),
            $issuedAt,
            $expiresAt,
        );

        return $this->app->make(AccessTokenIssuerInterface::class)->issue($payload)->value();
    }
}
