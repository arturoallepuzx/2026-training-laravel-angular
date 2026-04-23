<?php

declare(strict_types=1);

namespace Tests\Feature\Shared;

use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Persistence\EloquentUserIdResolver;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EloquentUserIdResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_to_internal_id_returns_bigint_for_existing_uuid(): void
    {
        $user = EloquentUser::factory()->create();

        $internalId = (new EloquentUserIdResolver)->toInternalId(Uuid::create($user->uuid));

        $this->assertSame((int) $user->id, $internalId);
    }

    public function test_to_domain_uuid_returns_uuid_for_existing_internal_id(): void
    {
        $user = EloquentUser::factory()->create();

        $uuid = (new EloquentUserIdResolver)->toDomainUuid((int) $user->id);

        $this->assertSame($user->uuid, $uuid->value());
    }

    public function test_to_internal_id_throws_when_uuid_does_not_exist(): void
    {
        $this->expectException(ModelNotFoundException::class);

        (new EloquentUserIdResolver)->toInternalId(Uuid::generate());
    }

    public function test_to_domain_uuid_throws_when_internal_id_does_not_exist(): void
    {
        $this->expectException(ModelNotFoundException::class);

        (new EloquentUserIdResolver)->toDomainUuid(999_999);
    }

    public function test_resolver_warms_reverse_cache_on_single_db_hit(): void
    {
        $user = EloquentUser::factory()->create();
        $resolver = new EloquentUserIdResolver;

        DB::enableQueryLog();
        DB::flushQueryLog();

        $resolver->toInternalId(Uuid::create($user->uuid));
        $resolver->toDomainUuid((int) $user->id);
        $resolver->toInternalId(Uuid::create($user->uuid));
        $resolver->toDomainUuid((int) $user->id);

        $this->assertCount(1, DB::getQueryLog());
    }
}
