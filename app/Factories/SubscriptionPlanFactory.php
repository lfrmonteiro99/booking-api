<?php

namespace App\Factories;

use App\Enums\SubscriptionPlan;
use App\Interfaces\SubscriptionPlanStrategyInterface;
use App\Interfaces\SubscriptionRepositoryInterface;
use App\Strategies\BasicPlanStrategy;
use App\Strategies\PremiumPlanStrategy;
use App\Strategies\ProPlanStrategy;
use App\Strategies\EnterprisePlanStrategy;
use InvalidArgumentException;

class SubscriptionPlanFactory
{
    protected SubscriptionRepositoryInterface $repository;

    public function __construct(SubscriptionRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function create(string $planName): SubscriptionPlanStrategyInterface
    {
        $strategy = match (SubscriptionPlan::tryFrom(strtolower($planName))) {
            SubscriptionPlan::BASIC => new BasicPlanStrategy(),
            SubscriptionPlan::PREMIUM => new PremiumPlanStrategy(),
            SubscriptionPlan::PRO => new ProPlanStrategy(),
            SubscriptionPlan::ENTERPRISE => new EnterprisePlanStrategy(),
            default => throw new InvalidArgumentException("Invalid subscription plan: {$planName}"),
        };

        $strategy->setRepository($this->repository);
        return $strategy;
    }
} 