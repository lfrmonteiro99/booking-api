<?php

namespace App\Providers;

use App\Enums\SubscriptionPlanLimit;
use App\Enums\SubscriptionStatus;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            $user = $request->user();

            // Get cached subscription info or fetch from DB
            $planName = Cache::remember(
                "user.{$user->id}.subscription.plan",
                now()->addHours(1), // Cache for 1 hour
                function () use ($user) {
                    $subscription = $user->subscriptions()
                        ->where('status', SubscriptionStatus::ACTIVE->value)
                        ->latest('starts_at')
                        ->first();
                    
                    return strtolower($subscription->plan_name->value ?? 'basic');
                }
            );

            $limit = SubscriptionPlanLimit::getLimit($planName);
            
            return Limit::perMinute($limit)
                ->by($user->id)
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'message' => 'Too many requests. Please try again later.',
                        'retry_after' => $headers['Retry-After'] ?? 60
                    ], 429);
                });
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
} 