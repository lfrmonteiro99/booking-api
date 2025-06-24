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
    
    /**
     * Get pricing data for a room between specific dates
     */
    public function getRoomPricingData(int $roomId, Carbon $checkIn, Carbon $checkOut): Collection;
    
    /**
     * Check if room is available for booking (availability + guest capacity)
     */
    public function isRoomAvailableForBooking(int $roomId, Carbon $checkIn, Carbon $checkOut, int $guests): bool;
}
