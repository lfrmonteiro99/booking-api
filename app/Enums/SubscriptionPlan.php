<?php

namespace App\Enums;

enum SubscriptionPlan: string
{
    case BASIC = 'basic';
    case PREMIUM = 'premium';
    case PRO = 'pro';
    case ENTERPRISE = 'enterprise';

    public function getDuration(): int
    {
        return match($this) {
            self::BASIC => 1, // 1 month
            self::PREMIUM => 6, // 6 months
            self::PRO => 12, // 12 months
            self::ENTERPRISE => 0, // Custom duration
        };
    }

    public function getDurationUnit(): string
    {
        return match($this) {
            self::BASIC, self::PREMIUM, self::PRO => 'months',
            self::ENTERPRISE => 'custom',
        };
    }
} 