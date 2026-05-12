<?php

declare(strict_types=1);

namespace App\Zone\Application\ListZones;

use App\Shared\Domain\ValueObject\Uuid;
use App\Zone\Domain\Interfaces\ZoneRepositoryInterface;

class ListZones
{
    public function __construct(
        private ZoneRepositoryInterface $zoneRepository,
    ) {}

    public function __invoke(string $restaurantId): ListZonesResponse
    {
        $zones = $this->zoneRepository->findAllByRestaurantId(Uuid::create($restaurantId));

        return ListZonesResponse::create($zones);
    }
}
