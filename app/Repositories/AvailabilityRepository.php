<?php

namespace App\Repositories;

use App\Models\Property;
use Carbon\Carbon;
use App\Interfaces\AvailabilityRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class AvailabilityRepository implements AvailabilityRepositoryInterface
{
    public function getAvailableRooms(string $propertyId, Carbon $checkIn, Carbon $checkOut, int $guests, ?bool $fullAvailability = false): array|Collection
    {
        Log::info('AvailabilityRepository: getAvailableRooms called', [
            'propertyId' => $propertyId,
            'checkIn' => $checkIn->toDateString(),
            'checkOut' => $checkOut->toDateString(),
            'guests' => $guests
        ]);

        // Calculate the number of days for the requested period (inclusive of check-in and check-out)
        $numDays = $checkIn->diffInDays($checkOut) + 1;

        Log::info('AvailabilityRepository: Calculated numDays', ['numDays' => $numDays]);

        $property = Property::where('property_id', $propertyId)
            ->first();

        if (!$property) {
            Log::info('AvailabilityRepository: Property not found', ['propertyId' => $propertyId]);
            return []; // Property not found
        }

        Log::info('AvailabilityRepository: Property query result', [
            'property_found' => (bool) $property,
            'property_id_from_db' => $property->property_id ?? null,
            'property_internal_id_from_db' => $property->id ?? null,
        ]);

        // Now, get the rooms associated with this property's internal ID, applying filters
        $rooms = $property->rooms()
            ->when($guests, function ($query, $guests) {
                return $query->where('max_guests', '>=', $guests);
            })
            ->with(['availabilities' => function ($q) use ($checkIn, $checkOut) {
                $q->whereDate('date', '>=', $checkIn->toDateString())
                  ->whereDate('date', '<=', $checkOut->toDateString())
                  ->where('is_available', true);
            }])
            ->get();

        if ($rooms->isEmpty()) {
            Log::info('AvailabilityRepository: No rooms found for property after filtering', ['property_internal_id' => $property->id]);
            return []; 
        }

        if($fullAvailability) {
            return $rooms;
        }

        $availableRooms = [];
        foreach ($rooms as $room) {
            Log::info('AvailabilityRepository: Processing room', [
                'room_id' => $room->id,
                'room_property_id_fk' => $room->property_id,
                'max_guests' => $room->max_guests,
                'availabilities_count' => $room->availabilities->count(),
                'num_days_requested' => $numDays
            ]);

            Log::info('AvailabilityRepository: Availability check details', [
                'room_id' => $room->id,
                'availabilities_collection' => $room->availabilities->toArray(),
                'availabilities_count_for_comparison' => $room->availabilities->count(),
                'num_days_for_comparison' => (int) $numDays,
            ]);

            if ($room->availabilities->count() !== (int) $numDays) {
                Log::info('AvailabilityRepository: Room skipped - not enough availabilities or mismatch in days', [
                    'room_id' => $room->id,
                    'availabilities_count' => $room->availabilities->count(),
                    'num_days_requested' => $numDays
                ]);
                continue;
            }

            $totalPrice = 0;
            foreach ($room->availabilities as $avail) {
                $totalPrice += $avail->price;
            }
            $availableRooms[] = [
                'room_id' => $room->room_id,
                'max_guests' => $room->max_guests,
                'total_price' => round($totalPrice, 2),
            ];
        }

        return $availableRooms;
    }
}
