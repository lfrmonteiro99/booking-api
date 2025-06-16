<?php

namespace App\Strategies;

use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Interfaces\SubscriptionPlanStrategyInterface;
use App\Interfaces\SubscriptionRepositoryInterface;
use App\Models\User;
use App\Models\Subscription;
use Carbon\Carbon;

class ProPlanStrategy implements SubscriptionPlanStrategyInterface
{
    protected SubscriptionRepositoryInterface $repository;

    public function setRepository(SubscriptionRepositoryInterface $repository): void
    {
        $this->repository = $repository;
    }

    public function subscribe(User $user, array $data): array
    {
        $startsAt = Carbon::now();
        $endsAt = Carbon::now()->addMonths(SubscriptionPlan::PRO->getDuration());

        $subscription = $this->repository->createSubscription(
            $user,
            SubscriptionPlan::PRO->value,
            $startsAt,
            $endsAt,
            SubscriptionStatus::ACTIVE->value
        );

        return [
            'message' => 'Successfully subscribed to Pro Plan.',
            'subscription' => $subscription->toArray()
        ];
    }

    public function getName(): string
    {
        return SubscriptionPlan::PRO->value;
    }
} 