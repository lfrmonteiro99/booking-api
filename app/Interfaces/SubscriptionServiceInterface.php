<?php

namespace App\Interfaces;

use App\Enums\SubscriptionPlan;
use App\Models\User;

interface SubscriptionServiceInterface
{
    public function subscribeUserToPlan(User $user, SubscriptionPlan|string $planName, array $data = []): array;
} 