<?php

namespace App\Services;

use App\Interfaces\BookingServiceInterface;
use App\Interfaces\BookingRepositoryInterface;
use App\Models\Booking;
use Carbon\Carbon;

class BookingService implements BookingServiceInterface
{
    protected BookingRepositoryInterface $repository;
    protected AvailabilityService $availabilityService;
    protected PricingService $pricingService;

    public function __construct(
        BookingRepositoryInterface $repository,
        AvailabilityService $availabilityService,
        PricingService $pricingService
    ) {
        $this->repository = $repository;
        $this->availabilityService = $availabilityService;
        $this->pricingService = $pricingService;
    }

    public function create(array $data): Booking
    {
        return $this->repository->create($data);
    }

    /**
     * Create booking with availability validation and pricing calculation
     */
    public function createWithPricing(array $data): Booking
    {
        // Validate availability
        $isAvailable = $this->availabilityService->validateRoomAvailability(
            $data['room_id'],
            $data['check_in'],
            $data['check_out'],
            $data['guests']
        );

        if (!$isAvailable) {
            throw new \Exception('Room is not available for the selected dates and guest count');
        }

        // Get pricing data
        $pricingData = $this->availabilityService->getRoomPricingData(
            $data['room_id'],
            $data['check_in'],
            $data['check_out']
        );

        // Calculate nights
        $checkIn = Carbon::parse($data['check_in']);
        $checkOut = Carbon::parse($data['check_out']);
        $nights = $checkIn->diffInDays($checkOut);

        // Calculate pricing
        $pricing = $this->pricingService->calculateBookingPrice($pricingData, $nights);

        // Add pricing to booking data
        $bookingData = array_merge($data, [
            'nights' => $pricing['nights'],
            'price_per_night' => $pricing['price_per_night'],
            'total_price' => $pricing['total_price'],
            'tax_amount' => $pricing['tax_amount'],
            'final_total' => $pricing['final_total'],
            'currency' => $pricing['currency'],
        ]);

        return $this->repository->create($bookingData);
    }

    public function update(int $id, array $data): Booking
    {
        return $this->repository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }

    public function getById(int $id): ?Booking
    {
        return $this->repository->getById($id);
    }

    public function getAllForUser(int $userId): array
    {
        return $this->repository->getAllForUser($userId);
    }

    public function getByIdForUser(int $id, int $userId): ?Booking
    {
        return $this->repository->getByIdForUser($id, $userId);
    }

    public function updateForUser(int $id, array $data, int $userId): ?Booking
    {
        return $this->repository->updateForUser($id, $data, $userId);
    }

    public function deleteForUser(int $id, int $userId): bool
    {
        return $this->repository->deleteForUser($id, $userId);
    }

    /**
     * Get pricing preview for potential booking
     */
    public function getPricingPreview(array $data, int $userId): array
    {
        // Validate availability
        $isAvailable = $this->availabilityService->validateRoomAvailability(
            $data['room_id'],
            $data['check_in'],
            $data['check_out'],
            $data['guests']
        );

        if (!$isAvailable) {
            return [
                'success' => false,
                'available' => false,
                'message' => 'Room is not available for the selected dates and guest count'
            ];
        }

        try {
            // Get pricing data
            $pricingData = $this->availabilityService->getRoomPricingData(
                $data['room_id'],
                $data['check_in'],
                $data['check_out']
            );

            // Calculate nights
            $checkIn = Carbon::parse($data['check_in']);
            $checkOut = Carbon::parse($data['check_out']);
            $nights = $checkIn->diffInDays($checkOut);

            // Calculate pricing
            $pricing = $this->pricingService->calculateBookingPrice($pricingData, $nights);

            return [
                'success' => true,
                'available' => true,
                'pricing' => $pricing
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'available' => false,
                'message' => 'Error calculating pricing: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get booking for user with proper authorization
     */
    public function getBookingForUser(int $id, int $userId): array
    {
        $booking = $this->getByIdForUser($id, $userId);
        
        if (!$booking) {
            return [
                'success' => false,
                'message' => 'Booking not found',
                'status_code' => 404
            ];
        }
        
        return [
            'success' => true,
            'booking' => $booking
        ];
    }

    /**
     * Update booking for user with authorization
     */
    public function updateBookingForUser(int $id, array $data, int $userId): array
    {
        $booking = $this->getByIdForUser($id, $userId);
        
        if (!$booking) {
            return [
                'success' => false,
                'message' => 'Booking not found',
                'status_code' => 404
            ];
        }
        
        return [
            'success' => true,
            'booking' => $booking
        ];
    }

    /**
     * Delete booking for user with authorization
     */
    public function deleteBookingForUser(int $id, int $userId): array
    {
        $booking = $this->getByIdForUser($id, $userId);
        
        if (!$booking) {
            return [
                'success' => false,
                'message' => 'Booking not found',
                'status_code' => 404
            ];
        }
        
        return [
            'success' => true,
            'booking' => $booking
        ];
    }
} 