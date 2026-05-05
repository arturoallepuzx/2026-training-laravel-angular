<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use App\Auth\Domain\Interfaces\AccessTokenIssuerInterface;
use App\Auth\Domain\ValueObject\AccessTokenPayload;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\UserRole;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Application\ForceLogoutUser\ForceLogoutUser;
use App\User\Domain\Exception\UserNotFoundException;
use Firebase\JWT\JWT;
use Mockery;
use Tests\TestCase;

class ForceLogoutUserTest extends TestCase
{
    protected function tearDown(): void
    {
        JWT::$timestamp = null;
        Mockery::close();
        parent::tearDown();
    }

    public function test_post_force_logout_returns_204_when_admin_targets_user_in_same_restaurant(): void
    {
        $restaurantId = Uuid::generate();
        $adminUserId = Uuid::generate();
        $targetUserId = Uuid::generate();
        $adminToken = $this->issueToken($adminUserId, $restaurantId, UserRole::admin());

        $useCase = Mockery::mock(ForceLogoutUser::class);
        $useCase->shouldReceive('__invoke')
            ->once()
            ->with($restaurantId->value(), $targetUserId->value());

        $this->app->instance(ForceLogoutUser::class, $useCase);

        $response = $this->postJson(
            '/api/restaurants/'.$restaurantId->value().'/users/'.$targetUserId->value().'/force-logout',
            [],
            ['Authorization' => 'Bearer '.$adminToken],
        );

        $response->assertStatus(204);
        $this->assertSame('', $response->getContent());
    }

    public function test_post_force_logout_returns_401_without_access_token(): void
    {
        $restaurantId = Uuid::generate();
        $targetUserId = Uuid::generate();

        $response = $this->postJson(
            '/api/restaurants/'.$restaurantId->value().'/users/'.$targetUserId->value().'/force-logout',
        );

        $response->assertStatus(401);
    }

    public function test_post_force_logout_returns_401_when_token_belongs_to_different_restaurant(): void
    {
        $tokenRestaurantId = Uuid::generate();
        $urlRestaurantId = Uuid::generate();
        $targetUserId = Uuid::generate();
        $adminToken = $this->issueToken(Uuid::generate(), $tokenRestaurantId, UserRole::admin());

        $response = $this->postJson(
            '/api/restaurants/'.$urlRestaurantId->value().'/users/'.$targetUserId->value().'/force-logout',
            [],
            ['Authorization' => 'Bearer '.$adminToken],
        );

        $response->assertStatus(401);
    }

    public function test_post_force_logout_returns_403_when_caller_is_not_admin(): void
    {
        $restaurantId = Uuid::generate();
        $targetUserId = Uuid::generate();
        $supervisorToken = $this->issueToken(Uuid::generate(), $restaurantId, UserRole::supervisor());

        $response = $this->postJson(
            '/api/restaurants/'.$restaurantId->value().'/users/'.$targetUserId->value().'/force-logout',
            [],
            ['Authorization' => 'Bearer '.$supervisorToken],
        );

        $response->assertStatus(403);
    }

    public function test_post_force_logout_returns_404_when_target_user_not_found(): void
    {
        $restaurantId = Uuid::generate();
        $targetUserId = Uuid::generate();
        $adminToken = $this->issueToken(Uuid::generate(), $restaurantId, UserRole::admin());

        $useCase = Mockery::mock(ForceLogoutUser::class);
        $useCase->shouldReceive('__invoke')
            ->once()
            ->andThrow(UserNotFoundException::forIdInRestaurant($targetUserId, $restaurantId));

        $this->app->instance(ForceLogoutUser::class, $useCase);

        $response = $this->postJson(
            '/api/restaurants/'.$restaurantId->value().'/users/'.$targetUserId->value().'/force-logout',
            [],
            ['Authorization' => 'Bearer '.$adminToken],
        );

        $response->assertStatus(404);
        $response->assertJsonPath('error', sprintf(
            'User "%s" not found in restaurant "%s".',
            $targetUserId->value(),
            $restaurantId->value(),
        ));
    }

    private function issueToken(Uuid $userId, Uuid $restaurantId, UserRole $role): string
    {
        $issuedAt = DomainDateTime::create(new \DateTimeImmutable('2099-01-01 10:00:00'));
        $expiresAt = DomainDateTime::create(new \DateTimeImmutable('2099-01-01 10:15:00'));

        JWT::$timestamp = $issuedAt->value()->getTimestamp() + 60;

        $payload = AccessTokenPayload::create(
            $userId,
            $restaurantId,
            $role,
            Uuid::generate(),
            $issuedAt,
            $expiresAt,
        );

        return $this->app->make(AccessTokenIssuerInterface::class)->issue($payload)->value();
    }
}
