<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Auth\Domain\Interfaces\AccessTokenIssuerInterface;
use App\Auth\Domain\ValueObject\AccessTokenPayload;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\UserRole;
use App\Shared\Domain\ValueObject\Uuid;
use Firebase\JWT\JWT;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class EnsureTokenMatchesRestaurantTest extends TestCase
{
    private const ROUTE_PATH = '/test-tenant/restaurants/{restaurantId}/dummy';

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('api')
            ->prefix('api')
            ->group(function (): void {
                Route::get(self::ROUTE_PATH, fn () => new JsonResponse(['ok' => true]))
                    ->whereUuid('restaurantId')
                    ->middleware(['auth.access_token', 'auth.restaurant']);
            });
    }

    protected function tearDown(): void
    {
        JWT::$timestamp = null;
        parent::tearDown();
    }

    public function test_returns_200_when_token_restaurant_matches_url(): void
    {
        $restaurantId = Uuid::generate();
        $token = $this->issueToken($restaurantId);

        $response = $this->getJson(
            '/api/test-tenant/restaurants/'.$restaurantId->value().'/dummy',
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);
    }

    public function test_returns_401_when_token_restaurant_does_not_match_url(): void
    {
        $tokenRestaurantId = Uuid::generate();
        $urlRestaurantId = Uuid::generate();
        $token = $this->issueToken($tokenRestaurantId);

        $response = $this->getJson(
            '/api/test-tenant/restaurants/'.$urlRestaurantId->value().'/dummy',
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertStatus(401);
    }

    public function test_returns_401_when_no_token_provided(): void
    {
        $response = $this->getJson(
            '/api/test-tenant/restaurants/'.Uuid::generate()->value().'/dummy',
        );

        $response->assertStatus(401);
    }

    private function issueToken(Uuid $restaurantId): string
    {
        $issuedAt = DomainDateTime::create(new \DateTimeImmutable('2099-01-01 10:00:00'));
        $expiresAt = DomainDateTime::create(new \DateTimeImmutable('2099-01-01 10:15:00'));

        JWT::$timestamp = $issuedAt->value()->getTimestamp() + 60;

        $payload = AccessTokenPayload::create(
            Uuid::generate(),
            $restaurantId,
            UserRole::admin(),
            Uuid::generate(),
            $issuedAt,
            $expiresAt,
        );

        return $this->app->make(AccessTokenIssuerInterface::class)->issue($payload)->value();
    }
}
