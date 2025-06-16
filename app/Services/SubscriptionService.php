<?php

namespace App\Services;

use App\Factories\SubscriptionPlanFactory;
use App\Interfaces\SubscriptionServiceInterface;
use App\Models\User;
use App\Enums\SubscriptionPlan;

class SubscriptionService implements SubscriptionServiceInterface
{
    protected SubscriptionPlanFactory $factory;

    public function __construct(SubscriptionPlanFactory $factory)
    {
        $this->factory = $factory;
    }

    public function subscribeUserToPlan(User $user, SubscriptionPlan|string $planName, array $data = []): array
    {
        $strategy = $this->factory->create($planName);
        return $strategy->subscribe($user, $data);
    }
} 