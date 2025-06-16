<?php

namespace App\Interfaces;

use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Models\User;
use App\Models\Subscription;

interface SubscriptionRepositoryInterface
{
    public function createSubscription(
        User $user, 
        SubscriptionPlan|string $planName, 
        \Carbon\Carbon $startsAt, 
        ?\Carbon\Carbon $endsAt, 
        SubscriptionStatus|string $status = SubscriptionStatus::ACTIVE
    ): Subscription;
} 