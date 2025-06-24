<?php

namespace App\Enums;

use App\Enums\SubscriptionPlan;

enum SubscriptionPlanLimit: int
{
    case BASIC = 30;
    case PREMIUM = 35;
    case PRO = 40;
    case ENTERPRISE = 50;

    public static function getLimit(string $planName): int
    {
        return match (SubscriptionPlan::tryFrom(strtolower($planName))) {
            SubscriptionPlan::BASIC => self::BASIC->value,
            SubscriptionPlan::PREMIUM => self::PREMIUM->value,
            SubscriptionPlan::PRO => self::PRO->value,
            SubscriptionPlan::ENTERPRISE => self::ENTERPRISE->value,
            default => self::BASIC->value,
        };
    }
} 