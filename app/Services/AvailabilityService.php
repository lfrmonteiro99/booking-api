<?php

namespace App\Services;

use App\Interfaces\AvailabilityRepositoryInterface;
use App\Interfaces\AvailabilityServiceInterface;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class AvailabilityService implements AvailabilityServiceInterface
{
    protected $repository;

    public function __construct(AvailabilityRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function checkAvailability(array $data): array
    {
        // Normalize dates to avoid time-related bugs.
        // Use $data directly since it's now validated data from FormRequest.
        $checkIn = Carbon::parse($data['check_in'] ?? now()->toDateString())->startOfDay();
        $checkOut = Carbon::parse($data['check_out'] ?? now()->addDay()->toDateString())->endOfDay();

        // Check date range limit
        if ($checkIn->diffInDays($checkOut) > 30) {
            return [
                'status' => 'error',
                'message' => 'Date range cannot exceed 30 days'
            ];
        }

        $cacheKey = "availability:{$data['property_id']}:{$checkIn->toDateString()}:{$checkOut->toDateString()}:{$data['guests']}";
        
        $response = Cache::tags(['availability_property:' . $data['property_id']])->remember($cacheKey, 300, function() use ($data, $checkIn, $checkOut) {
            $rooms = $this->repository->getAvailableRooms(
                $data['property_id'],
                $checkIn,
                $checkOut,
                (int) $data['guests'],
                $data['full_availability'] ?? false
            );
        
            return [
                'status'      => 'success',
                'property_id' => $data['property_id'],
                'rooms'       => $rooms,
            ];
        });

        return $response;
    }

    public function formatAvailabilityResponse(array $response): string
    {
        if ($response['status'] === 'error') {
            return 'There was an error checking availability: ' . ($response['message'] ?? 'Unknown error');
        }

        if (empty($response['rooms'])) {
            return 'No rooms are available for the selected criteria.';
        }

        $propertyId = $response['property_id'] ?? 'the requested property';
        $roomCount = count($response['rooms']);

        $reply = "Found {$roomCount} room(s) available for property {$propertyId}. ";
        $roomDetails = [];

        foreach ($response['rooms'] as $room) {
            $roomId = $room['room_id'] ?? 'unknown room';
            $maxGuests = $room['max_guests'] ?? 'N/A';
            $totalPrice = isset($room['total_price']) ? number_format($room['total_price'], 2, '.', '') : 'N/A';
            $roomDetails[] = "Room {$roomId} (max guests: {$maxGuests}) at a total price of \${$totalPrice}.";
        }

        $reply .= implode(' ', $roomDetails);

        return $reply;
    }

    public function replyAvailability(array $params): string
    {
        $resp = $this->checkAvailability([
            'property_id' => $params['property_id'] ?? null,
            'check_in'    => $params['check_in'] ?? null,
            'check_out'   => $params['check_out'] ?? null,
            'guests'      => $params['guests'] ?? null,
            'full_availability' => $params['full_availability'] ?? false // Add full_availability from params
        ]);

        if ($resp['status'] === 'error') {
            return $resp['message'];
        }

        $count = count($resp['rooms']);
        // The price is now available within each room's availability data if full_availability is true
        // For simplicity, let's assume we still want a starting price from the first room for general reply
        $price = $count && isset($resp['rooms'][0]['total_price']) ? number_format($resp['rooms'][0]['total_price'], 2, '.', '') : null;

        if ($count) {
            return "Yes! We have {$count} room(s) available from {$params['check_in']} to {$params['check_out']}, starting at €{$price}. Want to reserve now?";
        }

        return "Sorry, no availability found for those dates and guest count.";
    }

    /**
     * Validate room availability for booking
     */
    public function validateRoomAvailability(int $roomId, string $checkIn, string $checkOut, int $guests): bool
    {
        $checkInDate = Carbon::parse($checkIn);
        $checkOutDate = Carbon::parse($checkOut);
        
        return $this->repository->isRoomAvailableForBooking($roomId, $checkInDate, $checkOutDate, $guests);
    }

    /**
     * Get room pricing data for booking calculations
     */
    public function getRoomPricingData(int $roomId, string $checkIn, string $checkOut): \Illuminate\Support\Collection
    {
        $checkInDate = Carbon::parse($checkIn);
        $checkOutDate = Carbon::parse($checkOut);
        
        return $this->repository->getRoomPricingData($roomId, $checkInDate, $checkOutDate);
    }
}
