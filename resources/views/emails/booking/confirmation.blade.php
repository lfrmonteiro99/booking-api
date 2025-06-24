@component('mail::message')
# Booking Confirmation

Thank you for your booking!

**Booking Details:**
- Booking ID: {{ $booking->id }}
- Room ID: {{ $booking->room_id }}
- Property ID: {{ $booking->property_id }}
- Check-in: {{ $booking->check_in }}
- Check-out: {{ $booking->check_out }}
- Guests: {{ $booking->guests }}
- Status: {{ $booking->status }}

If you have any questions, reply to this email.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
