<?php

declare(strict_types=1);

namespace App\Zone\Application\GetZoneById;

use App\Shared\Domain\ValueObject\Uuid;
use App\Zone\Domain\Exception\ZoneNotFoundException;
use App\Zone\Domain\Interfaces\ZoneRepositoryInterface;

class GetZoneById
{
    public function __construct(
        private ZoneRepositoryInterface $zoneRepository,
    ) {}

    public function __invoke(string $id, string $restaurantId): GetZoneByIdResponse
    {
        $zoneId = Uuid::create($id);

        $zone = $this->zoneRepository->findById(
            $zoneId,
            Uuid::create($restaurantId),
        );

        if ($zone === null) {
            throw ZoneNotFoundException::forId($zoneId);
        }

        return GetZoneByIdResponse::create($zone);
    }
}
