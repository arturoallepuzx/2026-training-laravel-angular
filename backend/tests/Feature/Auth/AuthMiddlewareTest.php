<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Auth\Domain\Interfaces\AccessTokenIssuerInterface;
use App\Auth\Domain\ValueObject\AccessTokenPayload;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\UserRole;
use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Auth\AuthContextHolder;
use Firebase\JWT\JWT;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthMiddlewareTest extends TestCase
{
    private const ROUTE_AUTH_ONLY = '/test-auth/protected';

    private const ROUTE_ADMIN_ONLY = '/test-auth/admin-only';

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('api')
            ->prefix('api')
            ->group(function (): void {
                Route::get(self::ROUTE_AUTH_ONLY, function () {
                    $context = app(AuthContextHolder::class)->get();

                    return new JsonResponse([
                        'user_id' => $context?->userId()->value(),
                        'role' => $context?->role()->value(),
                    ]);
                })->middleware('auth.access_token');

                Route::get(self::ROUTE_ADMIN_ONLY, fn () => new JsonResponse(['ok' => true]))
                    ->middleware(['auth.access_token', 'auth.role:admin']);
            });
    }

    protected function tearDown(): void
    {
        JWT::$timestamp = null;
        parent::tearDown();
    }

    public function test_returns_401_when_no_authorization_header(): void
    {
        $response = $this->getJson('/api'.self::ROUTE_AUTH_ONLY);

        $response->assertStatus(401);
    }

    public function test_returns_401_when_token_is_invalid(): void
    {
        $response = $this->getJson('/api'.self::ROUTE_AUTH_ONLY, [
            'Authorization' => 'Bearer not-a-jwt',
        ]);

        $response->assertStatus(401);
    }

    public function test_returns_200_with_auth_context_bound_when_token_is_valid(): void
    {
        [$token, $payload] = $this->issueToken(UserRole::supervisor());

        JWT::$timestamp = $payload->issuedAt()->value()->getTimestamp() + 60;

        $response = $this->getJson('/api'.self::ROUTE_AUTH_ONLY, [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'user_id' => $payload->userId()->value(),
            'role' => 'supervisor',
        ]);
    }

    public function test_returns_403_when_role_is_not_allowed(): void
    {
        [$token, $payload] = $this->issueToken(UserRole::operator());

        JWT::$timestamp = $payload->issuedAt()->value()->getTimestamp() + 60;

        $response = $this->getJson('/api'.self::ROUTE_ADMIN_ONLY, [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertStatus(403);
    }

    public function test_returns_200_when_role_matches(): void
    {
        [$token, $payload] = $this->issueToken(UserRole::admin());

        JWT::$timestamp = $payload->issuedAt()->value()->getTimestamp() + 60;

        $response = $this->getJson('/api'.self::ROUTE_ADMIN_ONLY, [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);
    }

    /**
     * @return array{0: string, 1: AccessTokenPayload}
     */
    private function issueToken(UserRole $role): array
    {
        $payload = AccessTokenPayload::create(
            Uuid::generate(),
            Uuid::generate(),
            $role,
            Uuid::generate(),
            DomainDateTime::create(new \DateTimeImmutable('2099-01-01 10:00:00')),
            DomainDateTime::create(new \DateTimeImmutable('2099-01-01 10:15:00')),
        );

        $token = $this->app->make(AccessTokenIssuerInterface::class)->issue($payload)->value();

        return [$token, $payload];
    }
}
