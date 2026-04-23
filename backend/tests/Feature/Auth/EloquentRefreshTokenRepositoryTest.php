<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Auth\Domain\Entity\RefreshToken;
use App\Auth\Domain\ValueObject\RefreshTokenSecret;
use App\Auth\Infrastructure\Persistence\Models\EloquentRefreshToken;
use App\Auth\Infrastructure\Persistence\Repositories\EloquentRefreshTokenRepository;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Persistence\EloquentUserIdResolver;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentRefreshTokenRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private function repository(): EloquentRefreshTokenRepository
    {
        return new EloquentRefreshTokenRepository(new EloquentRefreshToken, new EloquentUserIdResolver);
    }

    private function futureExpiration(): DomainDateTime
    {
        return DomainDateTime::create((new \DateTimeImmutable)->modify('+30 days'));
    }

    private function randomSecret(): RefreshTokenSecret
    {
        return RefreshTokenSecret::create(
            rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=')
        );
    }

    public function test_create_persists_refresh_token_row(): void
    {
        $user = EloquentUser::factory()->create();
        $secret = $this->randomSecret();
        $token = RefreshToken::dddCreate(
            Uuid::create($user->uuid),
            Uuid::generate(),
            $secret,
            $this->futureExpiration(),
        );

        $this->repository()->create($token);

        $this->assertDatabaseHas('refresh_tokens', [
            'uuid' => $token->id()->value(),
            'user_id' => $user->id,
            'session_uuid' => $token->sessionId()->value(),
            'token_hash' => $secret->hash()->value(),
            'revoked_at' => null,
            'replaced_by_id' => null,
        ]);
    }

    public function test_find_by_token_hash_returns_entity_when_exists(): void
    {
        $user = EloquentUser::factory()->create();
        $secret = $this->randomSecret();
        $token = RefreshToken::dddCreate(
            Uuid::create($user->uuid),
            Uuid::generate(),
            $secret,
            $this->futureExpiration(),
        );

        $repository = $this->repository();
        $repository->create($token);

        $found = $repository->findByTokenHash($secret->hash());

        $this->assertNotNull($found);
        $this->assertSame($token->id()->value(), $found->id()->value());
        $this->assertSame($user->uuid, $found->userId()->value());
        $this->assertSame($token->sessionId()->value(), $found->sessionId()->value());
        $this->assertTrue($secret->hash()->equals($found->tokenHash()));
        $this->assertFalse($found->isRevoked());
        $this->assertNull($found->replacedById());
    }

    public function test_find_by_token_hash_returns_null_when_not_found(): void
    {
        $secret = $this->randomSecret();

        $found = $this->repository()->findByTokenHash($secret->hash());

        $this->assertNull($found);
    }

    public function test_update_persists_revocation_and_self_reference_replacement(): void
    {
        $user = EloquentUser::factory()->create();
        $userId = Uuid::create($user->uuid);
        $sessionId = Uuid::generate();

        $oldToken = RefreshToken::dddCreate($userId, $sessionId, $this->randomSecret(), $this->futureExpiration());
        $newToken = RefreshToken::dddCreate($userId, $sessionId, $this->randomSecret(), $this->futureExpiration());

        $repository = $this->repository();
        $repository->create($oldToken);
        $repository->create($newToken);

        $oldToken->markReplacedBy($newToken->id());
        $repository->update($oldToken);

        $reloaded = $repository->findByTokenHash($oldToken->tokenHash());
        $newInternalId = EloquentRefreshToken::query()->where('uuid', $newToken->id()->value())->value('id');

        $this->assertNotNull($reloaded);
        $this->assertTrue($reloaded->isRevoked());
        $this->assertSame($newToken->id()->value(), $reloaded->replacedById()?->value());
        $this->assertDatabaseHas('refresh_tokens', [
            'uuid' => $oldToken->id()->value(),
            'replaced_by_id' => $newInternalId,
        ]);
    }
}
