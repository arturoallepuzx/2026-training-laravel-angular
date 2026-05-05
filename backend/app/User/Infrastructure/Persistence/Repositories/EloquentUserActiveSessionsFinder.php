<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence\Repositories;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\UserRole;
use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Persistence\RestaurantIdResolverInterface;
use App\User\Domain\Interfaces\UserActiveSessionsFinderInterface;
use App\User\Domain\ValueObject\UserActiveSessionsSummary;
use App\User\Domain\ValueObject\UserName;
use Illuminate\Support\Facades\DB;

class EloquentUserActiveSessionsFinder implements UserActiveSessionsFinderInterface
{
    public function __construct(
        private RestaurantIdResolverInterface $restaurantIdResolver,
    ) {}

    /**
     * @return list<UserActiveSessionsSummary>
     */
    public function findUsersWithActiveSessionsByRestaurantId(Uuid $restaurantId): array
    {
        $internalRestaurantId = $this->restaurantIdResolver->toInternalId($restaurantId);

        $rows = DB::table('users')
            ->join('refresh_tokens', 'users.id', '=', 'refresh_tokens.user_id')
            ->where('users.restaurant_id', $internalRestaurantId)
            ->whereNull('users.deleted_at')
            ->whereNull('refresh_tokens.revoked_at')
            ->where('refresh_tokens.expires_at', '>', DomainDateTime::now()->value())
            ->groupBy([
                'users.uuid',
                'users.name',
                'users.email',
                'users.role',
                'users.image_src',
            ])
            ->orderByDesc('last_seen_at')
            ->select([
                'users.uuid',
                'users.name',
                'users.email',
                'users.role',
                'users.image_src',
                DB::raw('COUNT(DISTINCT refresh_tokens.session_uuid) as active_sessions'),
                DB::raw('MAX(refresh_tokens.updated_at) as last_seen_at'),
            ])
            ->get();

        return $rows
            ->map(fn (object $row): UserActiveSessionsSummary => UserActiveSessionsSummary::create(
                Uuid::create((string) $row->uuid),
                UserName::create((string) $row->name),
                Email::create((string) $row->email),
                UserRole::create((string) $row->role),
                $row->image_src !== null ? (string) $row->image_src : null,
                (int) $row->active_sessions,
                DomainDateTime::create(new \DateTimeImmutable((string) $row->last_seen_at)),
            ))
            ->all();
    }
}
