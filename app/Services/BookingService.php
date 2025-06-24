<?php

namespace App\Services;

use App\Interfaces\BookingServiceInterface;
use App\Interfaces\BookingRepositoryInterface;
use App\Models\Booking;

class BookingService implements BookingServiceInterface
{
    protected BookingRepositoryInterface $repository;

    public function __construct(BookingRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function create(array $data): Booking
    {
        return $this->repository->create($data);
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
} 