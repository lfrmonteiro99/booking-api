@component('mail::message')
# Booking Updated

Your booking has been successfully updated!

**Updated Booking Details:**
- Booking ID: {{ $booking->id }}
- Room ID: {{ $booking->room_id }}
- Property ID: {{ $booking->property_id }}
- Check-in: {{ $booking->check_in }}
- Check-out: {{ $booking->check_out }}
- Guests: {{ $booking->guests }}
- Status: {{ $booking->status }}

@component('mail::panel')
Please make note of your new check-in and check-out dates.
@endcomponent

If you have any questions, reply to this email.

Thanks,<br>
{{ config('app.name') }}
@endcomponent