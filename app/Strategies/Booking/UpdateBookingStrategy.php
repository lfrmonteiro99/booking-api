<?php

namespace App\Strategies\Booking;

use App\Models\Booking;
use App\Models\Availability;
use App\Models\User;
use App\Mail\UpdateBookingMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class UpdateBookingStrategy implements BookingActionStrategyInterface
{
    public function handle(array $data, User $user): void
    {
        DB::transaction(function () use ($data, $user) {
            $booking = Booking::findOrFail($data['id']);
            // Release old dates
            $oldDates = $this->getDateRange($booking->check_in, $booking->check_out);
            Availability::where('room_id', $booking->room_id)
                ->whereIn('date', $oldDates)
                ->update(['is_available' => true]);
            // Update booking
            $booking->update($data);
            // Block new dates
            $newDates = $this->getDateRange($booking->check_in, $booking->check_out);
            Availability::where('room_id', $booking->room_id)
                ->whereIn('date', $newDates)
                ->update(['is_available' => false]);
            Mail::to($user->email)->send(new UpdateBookingMail($booking));
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