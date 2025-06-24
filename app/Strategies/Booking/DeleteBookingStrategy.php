<?php

namespace App\Strategies\Booking;

use App\Models\Booking;
use App\Models\Availability;
use App\Models\User;
use App\Mail\CancelBookingMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class DeleteBookingStrategy implements BookingActionStrategyInterface
{
    public function handle(array $data, User $user): void
    {
        DB::transaction(function () use ($data, $user) {
            $booking = Booking::where('id', $data['id'])
                ->where('user_id', $user->id)
                ->firstOrFail();
            $dates = $this->getDateRange($booking->check_in, $booking->check_out);
            Availability::where('room_id', $booking->room_id)
                ->whereIn('date', $dates)
                ->update(['is_available' => true]);
            $booking->delete();
            Mail::to($user->email)->send(new CancelBookingMail($booking));
        });
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