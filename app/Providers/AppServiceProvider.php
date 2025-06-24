<?php

namespace App\Providers;

use App\Interfaces\AvailabilityRepositoryInterface;
use App\Interfaces\AvailabilityServiceInterface;
use App\Repositories\AvailabilityRepository;
use App\Services\AvailabilityService;
use App\Interfaces\SubscriptionRepositoryInterface;
use App\Repositories\SubscriptionRepository;
use App\Interfaces\SubscriptionServiceInterface;
use App\Services\SubscriptionService;
use App\Interfaces\BookingRepositoryInterface;
use App\Repositories\BookingRepository;
use App\Services\BookingService;
use App\Interfaces\BookingServiceInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AvailabilityServiceInterface::class, AvailabilityService::class);
        $this->app->bind(AvailabilityRepositoryInterface::class, AvailabilityRepository::class);
        $this->app->bind(SubscriptionServiceInterface::class, SubscriptionService::class);
        $this->app->bind(SubscriptionRepositoryInterface::class, SubscriptionRepository::class);
        $this->app->bind(BookingServiceInterface::class, BookingService::class);
        $this->app->bind(BookingRepositoryInterface::class, BookingRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
    }
}
