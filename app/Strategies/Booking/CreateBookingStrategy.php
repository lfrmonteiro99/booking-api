<?php

namespace App\Strategies\Booking;

use App\Models\Booking;
use App\Models\Availability;
use App\Models\User;
use App\Mail\BookingConfirmationMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateBookingStrategy implements BookingActionStrategyInterface
{
    public function handle(array $data, User $user): void
    {
        Log::info('CreateBookingStrategy: About to start transaction', ['user_id' => $user->id, 'data' => $data]);
        try {
            DB::transaction(function () use ($data, $user) {
                Log::info('CreateBookingStrategy: Creating booking', ['user_id' => $user->id, 'data' => $data]);
                $booking = Booking::create(array_merge($data, ['user_id' => $user->id]));
                $dates = $this->getDateRange($booking->check_in, $booking->check_out);
                $updated = Availability::where('room_id', $booking->room_id)
                    ->whereIn('date', $dates)
                    ->update(['is_available' => false]);
                Log::info('CreateBookingStrategy: Updated availability', ['room_id' => $booking->room_id, 'dates' => $dates, 'updated_rows' => $updated]);
                Mail::to($user->email)->send(new BookingConfirmationMail($booking));
                Log::info('CreateBookingStrategy: Sent booking confirmation email', ['user_email' => $user->email, 'booking_id' => $booking->id]);
            });
            Log::info('CreateBookingStrategy: Transaction committed', ['user_id' => $user->id]);
        } catch (\Throwable $e) {
            Log::error('CreateBookingStrategy: Transaction failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    protected function getDateRange($start, $end): array
    {
        $dates = [];
        $current = strtotime($start);
        $end = strtotime($end);
        while ($current <= $end) {
            $dates[] = date('Y-m-d', $current);
            $current = strtotime('+1 day', $current);
        }
        return $dates;
    }
} 