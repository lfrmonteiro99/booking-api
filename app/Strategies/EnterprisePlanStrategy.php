<?php

namespace App\Strategies;

use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Interfaces\SubscriptionPlanStrategyInterface;
use App\Interfaces\SubscriptionRepositoryInterface;
use App\Models\User;
use App\Models\Subscription;
use Carbon\Carbon;

class EnterprisePlanStrategy implements SubscriptionPlanStrategyInterface
{
    protected SubscriptionRepositoryInterface $repository;

    public function setRepository(SubscriptionRepositoryInterface $repository): void
    {
        $this->repository = $repository;
    }

    public function subscribe(User $user, array $data): array
    {
        // Logic for Enterprise Plan subscription
        // This might involve custom setup, or a very long/indefinite period
        $startsAt = Carbon::now();
        // Enterprise plans might not have a fixed end date, or it's negotiated
        $endsAt = isset($data['ends_at']) ? Carbon::parse($data['ends_at']) : null;

        $subscription = $this->repository->createSubscription(
            $user,
            SubscriptionPlan::ENTERPRISE->value,
            $startsAt,
            $endsAt,
            SubscriptionStatus::ACTIVE->value
        );

        return [
            'message' => 'Successfully subscribed to Enterprise Plan.',
            'subscription' => $subscription->toArray()
        ];
    }

    public function getName(): string
    {
        return SubscriptionPlan::ENTERPRISE->value;
    }
} 