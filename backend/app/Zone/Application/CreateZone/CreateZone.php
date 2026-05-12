<?php

declare(strict_types=1);

namespace App\Zone\Application\CreateZone;

use App\Shared\Domain\ValueObject\Uuid;
use App\Zone\Domain\Entity\Zone;
use App\Zone\Domain\Exception\ZoneNameAlreadyExistsException;
use App\Zone\Domain\Interfaces\ZoneRepositoryInterface;
use App\Zone\Domain\ValueObject\ZoneName;

class CreateZone
{
    public function __construct(
        private ZoneRepositoryInterface $zoneRepository,
    ) {}

    public function __invoke(string $restaurantId, string $name): CreateZoneResponse
    {
        $restaurantUuid = Uuid::create($restaurantId);
        $zoneName = ZoneName::create($name);

        if ($this->zoneRepository->findByNameAndRestaurantId($zoneName, $restaurantUuid) !== null) {
            throw ZoneNameAlreadyExistsException::forName($zoneName->value());
        }

        $zone = Zone::dddCreate(
            $restaurantUuid,
            $zoneName,
        );

        $this->zoneRepository->create($zone);

        return CreateZoneResponse::create($zone);
    }
}
