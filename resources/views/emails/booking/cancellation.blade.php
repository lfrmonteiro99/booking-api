@component('mail::message')
# Booking Cancelled

Your booking has been successfully cancelled.

**Cancelled Booking Details:**
- Booking ID: {{ $booking->id }}
- Room ID: {{ $booking->room_id }}
- Property ID: {{ $booking->property_id }}
- Original Check-in: {{ $booking->check_in }}
- Original Check-out: {{ $booking->check_out }}
- Guests: {{ $booking->guests }}

@component('mail::panel')
If this cancellation was made in error, please contact us immediately.
@endcomponent

If you have any questions, reply to this email.

Thanks,<br>
{{ config('app.name') }}
@endcomponent