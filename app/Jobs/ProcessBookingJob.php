<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessBookingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $bookingData;
    protected User $user;
    protected string $strategyClass;

    /**
     * Create a new job instance.
     */
    public function __construct(array $bookingData, User $user, string $strategyClass)
    {
        $this->bookingData = $bookingData;
        $this->user = $user;
        $this->strategyClass = $strategyClass;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('ProcessBookingJob: Started processing booking job', ['user_id' => $this->user->id, 'booking_data' => $this->bookingData, 'strategy' => $this->strategyClass]);
        $strategy = app()->make($this->strategyClass);
        $strategy->handle($this->bookingData, $this->user);
        Log::info('ProcessBookingJob: Finished processing booking job', ['user_id' => $this->user->id, 'booking_data' => $this->bookingData, 'strategy' => $this->strategyClass]);
    }
}
