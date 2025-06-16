<?php

namespace App\Repositories;

use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Interfaces\SubscriptionRepositoryInterface;
use App\Models\User;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;

class SubscriptionRepository implements SubscriptionRepositoryInterface
{
    public function createSubscription(User $user, SubscriptionPlan|string $planName, Carbon $startsAt, ?Carbon $endsAt, SubscriptionStatus|string $status = SubscriptionStatus::ACTIVE): Subscription
    {
        try {
            return DB::transaction(function () use ($user, $planName, $startsAt, $endsAt, $status) {
                // Deactivate all of the user's currently active subscriptions in a single query.
                $user->subscriptions()
                    ->where('status', SubscriptionStatus::ACTIVE->value)
                    ->update(['status' => SubscriptionStatus::INACTIVE->value]);

                $planValue = $planName instanceof SubscriptionPlan ? $planName->value : $planName;
                $statusValue = $status instanceof SubscriptionStatus ? $status->value : $status;

                // Create the new subscription
                $subscription = $user->subscriptions()->create([
                    'plan_name' => $planValue,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'status' => $statusValue,
                ]);

                return $subscription;
            });
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') { // MySQL unique constraint violation code
                throw new \RuntimeException('User already has an active subscription', 409);
            }
            throw $e;
        } catch (\Exception $e) {
            throw $e;
        }
    }
} 