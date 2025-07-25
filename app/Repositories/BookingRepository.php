<?php

namespace App\Repositories;

use App\Interfaces\BookingRepositoryInterface;
use App\Models\Booking;

class BookingRepository implements BookingRepositoryInterface
{
    public function create(array $data): Booking
    {
        return Booking::create($data);
    }

    public function update(int $id, array $data): Booking
    {
        $booking = Booking::findOrFail($id);
        $booking->update($data);
        return $booking;
    }

    public function delete(int $id): bool
    {
        $booking = Booking::findOrFail($id);
        return $booking->delete();
    }

    public function getById(int $id): ?Booking
    {
        return Booking::find($id);
    }

    public function getAllForUser(int $userId): array
    {
        return Booking::where('user_id', $userId)->get()->all();
    }

    public function getByIdForUser(int $id, int $userId): ?Booking
    {
        return Booking::where('id', $id)->where('user_id', $userId)->first();
    }

    public function updateForUser(int $id, array $data, int $userId): ?Booking
    {
        $booking = $this->getByIdForUser($id, $userId);
        
        if (!$booking) {
            return null;
        }
        
        $booking->update($data);
        return $booking;
    }

    public function deleteForUser(int $id, int $userId): bool
    {
        $booking = $this->getByIdForUser($id, $userId);
        
        if (!$booking) {
            return false;
        }
        
        return $booking->delete();
    }
} 