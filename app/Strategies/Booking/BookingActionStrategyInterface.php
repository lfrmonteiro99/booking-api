<?php

namespace App\Strategies\Booking;

use App\Models\User;

interface BookingActionStrategyInterface
{
    public function handle(array $data, User $user): void;
} 