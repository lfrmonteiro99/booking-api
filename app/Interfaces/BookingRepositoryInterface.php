<?php

namespace App\Interfaces;

use App\Models\Booking;

interface BookingRepositoryInterface
{
    public function create(array $data): Booking;
    public function update(int $id, array $data): Booking;
    public function delete(int $id): bool;
    public function getById(int $id): ?Booking;
    public function getAllForUser(int $userId): array;
    public function getByIdForUser(int $id, int $userId): ?Booking;
    public function updateForUser(int $id, array $data, int $userId): ?Booking;
    public function deleteForUser(int $id, int $userId): bool;
} 