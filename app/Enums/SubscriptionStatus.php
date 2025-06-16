<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case EXPIRED = 'expired';
    case CANCELLED = 'cancelled';

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }
} 