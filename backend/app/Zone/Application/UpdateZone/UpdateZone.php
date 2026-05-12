<?php

declare(strict_types=1);

namespace App\Zone\Application\UpdateZone;

use App\Shared\Domain\ValueObject\Uuid;
use App\Zone\Domain\Exception\ZoneNameAlreadyExistsException;
use App\Zone\Domain\Exception\ZoneNotFoundException;
use App\Zone\Domain\Interfaces\ZoneRepositoryInterface;
use App\Zone\Domain\ValueObject\ZoneName;

class UpdateZone
{
    public function __construct(
        private ZoneRepositoryInterface $zoneRepository,
    ) {}

    public function __invoke(string $id, string $restaurantId, string $name): UpdateZoneResponse
    {
        $zoneId = Uuid::create($id);
        $restaurantUuid = Uuid::create($restaurantId);

        $zone = $this->zoneRepository->findById($zoneId, $restaurantUuid);

        if ($zone === null) {
            throw ZoneNotFoundException::forId($zoneId);
        }

        $zoneName = ZoneName::create($name);

        if (! $zoneName->equals($zone->name())) {
            $existing = $this->zoneRepository->findByNameAndRestaurantId($zoneName, $restaurantUuid);

            if ($existing !== null && $existing->id()->value() !== $zone->id()->value()) {
                throw ZoneNameAlreadyExistsException::forName($zoneName->value());
            }

            $zone->updateName($zoneName);
        }

        $this->zoneRepository->update($zone);

        return UpdateZoneResponse::create($zone);
    }
}
