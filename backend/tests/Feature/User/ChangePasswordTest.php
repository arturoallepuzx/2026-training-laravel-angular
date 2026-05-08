<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use App\Auth\Domain\Interfaces\AccessTokenIssuerInterface;
use App\Auth\Domain\ValueObject\AccessTokenPayload;
use App\Auth\Infrastructure\Persistence\Models\EloquentRefreshToken;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\UserRole;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        JWT::$timestamp = null;
        parent::tearDown();
    }

    public function test_user_changes_their_password(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $user = EloquentUser::factory()->create([
            'restaurant_id' => $restaurant->id,
            'password' => Hash::make('current-pass'),
        ]);

        $token = $this->issueToken(
            userId: Uuid::create($user->uuid),
            restaurantId: Uuid::create($restaurant->uuid),
            role: UserRole::operator(),
        );

        $response = $this->postJson(
            "/api/restaurants/{$restaurant->uuid}/auth/me/password",
            [
                'current_password' => 'current-pass',
                'password' => 'new-secret-1',
                'password_confirmation' => 'new-secret-1',
            ],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(204);

        $user->refresh();
        $this->assertTrue(Hash::check('new-secret-1', $user->password));
    }

    public function test_revokes_all_refresh_tokens_after_password_change(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $user = EloquentUser::factory()->create([
            'restaurant_id' => $restaurant->id,
            'password' => Hash::make('current-pass'),
        ]);

        EloquentRefreshToken::create([
            'uuid' => Uuid::generate()->value(),
            'session_uuid' => Uuid::generate()->value(),
            'user_id' => $user->id,
            'token_hash' => 'hash-1',
            'expires_at' => (new \DateTimeImmutable)->modify('+7 days'),
            'revoked_at' => null,
        ]);
        EloquentRefreshToken::create([
            'uuid' => Uuid::generate()->value(),
            'session_uuid' => Uuid::generate()->value(),
            'user_id' => $user->id,
            'token_hash' => 'hash-2',
            'expires_at' => (new \DateTimeImmutable)->modify('+7 days'),
            'revoked_at' => null,
        ]);

        $token = $this->issueToken(
            userId: Uuid::create($user->uuid),
            restaurantId: Uuid::create($restaurant->uuid),
            role: UserRole::operator(),
        );

        $response = $this->postJson(
            "/api/restaurants/{$restaurant->uuid}/auth/me/password",
            [
                'current_password' => 'current-pass',
                'password' => 'new-secret-1',
                'password_confirmation' => 'new-secret-1',
            ],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(204);

        $this->assertDatabaseCount('refresh_tokens', 2);
        $this->assertDatabaseMissing('refresh_tokens', [
            'user_id' => $user->id,
            'revoked_at' => null,
        ]);
    }

    public function test_returns_401_when_current_password_is_wrong(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $user = EloquentUser::factory()->create([
            'restaurant_id' => $restaurant->id,
            'password' => Hash::make('current-pass'),
        ]);

        $token = $this->issueToken(
            userId: Uuid::create($user->uuid),
            restaurantId: Uuid::create($restaurant->uuid),
            role: UserRole::operator(),
        );

        $response = $this->postJson(
            "/api/restaurants/{$restaurant->uuid}/auth/me/password",
            [
                'current_password' => 'wrong-pass',
                'password' => 'new-secret-1',
                'password_confirmation' => 'new-secret-1',
            ],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(401);
    }

    public function test_returns_422_when_password_confirmation_does_not_match(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $user = EloquentUser::factory()->create([
            'restaurant_id' => $restaurant->id,
            'password' => Hash::make('current-pass'),
        ]);

        $token = $this->issueToken(
            userId: Uuid::create($user->uuid),
            restaurantId: Uuid::create($restaurant->uuid),
            role: UserRole::operator(),
        );

        $response = $this->postJson(
            "/api/restaurants/{$restaurant->uuid}/auth/me/password",
            [
                'current_password' => 'current-pass',
                'password' => 'new-secret-1',
                'password_confirmation' => 'different',
            ],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(422);
    }

    public function test_returns_401_without_authorization(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();

        $response = $this->postJson(
            "/api/restaurants/{$restaurant->uuid}/auth/me/password",
            [
                'current_password' => 'current-pass',
                'password' => 'new-secret-1',
                'password_confirmation' => 'new-secret-1',
            ],
        );

        $response->assertStatus(401);
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
