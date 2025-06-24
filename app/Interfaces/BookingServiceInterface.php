<?php

namespace App\Interfaces;

use App\Models\Booking;

interface BookingServiceInterface
{
    public function create(array $data): Booking;
    public function update(int $id, array $data): Booking;
    public function delete(int $id): bool;
    public function getById(int $id): ?Booking;
    public function getAllForUser(int $userId): array;
} 