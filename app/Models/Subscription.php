<?php

namespace App\Models;

use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class Subscription extends Model
{
    protected $fillable = [
        'user_id',
        'plan_name',
        'starts_at',
        'ends_at',
        'status',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'plan_name' => SubscriptionPlan::class,
        'status' => SubscriptionStatus::class,
    ];

    protected static function booted()
    {
        static::saved(function ($subscription) {
            Cache::forget("user.{$subscription->user_id}.subscription.plan");
        });

        static::deleted(function ($subscription) {
            Cache::forget("user.{$subscription->user_id}.subscription.plan");
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }
}
