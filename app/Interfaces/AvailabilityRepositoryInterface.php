<?php

namespace App\Interfaces;

use Carbon\Carbon;
use Illuminate\Support\Collection;

interface AvailabilityRepositoryInterface
{
    /**
     * @param string $propertyId
     * @param Carbon $checkIn
     * @param Carbon $checkOut
     * @param int $guests
     * @param bool|null $fullAvailability
     * @return array|Collection
     */
    public function getAvailableRooms(string $propertyId, Carbon $checkIn, Carbon $checkOut, int $guests, ?bool $fullAvailability = false): array|Collection;
}
