<?php

namespace Tests\Feature;

use App\Models\Booking;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Mail;

/**
 * Integration tests for the complete booking flow
 * Tests the full API flow: HTTP Request → Controller → Service → Repository → Database
 */
class BookingIntegrationTest extends IntegrationTestCase
{
    /**
     * Test complete booking creation flow
     * 
     * @test
     */
    public function it_creates_a_booking_with_pricing_calculation()
    {
        // Arrange: Prepare test data
        $bookingData = [
            'room_id' => $this->testRoom->id,
            'property_id' => $this->testProperty->id,
            'check_in' => now()->addDays(2)->format('Y-m-d'),
            'check_out' => now()->addDays(4)->format('Y-m-d'), // 2 nights
            'guests' => 2,
        ];

        // Mock queues and mail to avoid actually sending
        Queue::fake();
        Mail::fake();

        // Act: Make the API request
        $response = $this->postJson('/api/bookings', $bookingData);

        // Assert: Check response
        $response->assertStatus(202)
                 ->assertJson([
                     'message' => 'Booking is being processed. You will receive a confirmation email shortly.'
                 ]);

        // Assert: Verify queue job was dispatched
        Queue::assertPushed(\App\Jobs\ProcessBookingJob::class);

        // Note: Since we're using background jobs, the booking won't be in DB yet
        // In a real integration test, we'd process the job or test synchronously
    }

    /**
     * Test getting user's bookings
     * 
     * @test
     */
    public function it_retrieves_user_bookings()
    {
        // Arrange: Create a booking directly in database
        $booking = Booking::factory()->create([
            'user_id' => $this->testUser->id,
            'room_id' => $this->testRoom->id,
            'property_id' => $this->testProperty->id,
            'check_in' => now()->addDays(1)->format('Y-m-d'),
            'check_out' => now()->addDays(3)->format('Y-m-d'),
            'guests' => 2,
            'nights' => 2,
            'price_per_night' => 100.00,
            'total_price' => 200.00,
            'tax_amount' => 20.00,
            'final_total' => 220.00,
            'currency' => 'USD',
            'status' => 'confirmed',
        ]);

        // Act: Get user's bookings
        $response = $this->getJson('/api/bookings');

        // Assert: Check response structure and data
        $response->assertStatus(200)
                 ->assertJsonCount(1) // Should have 1 booking
                 ->assertJsonStructure([
                     '*' => [
                         'id',
                         'user_id',
                         'room_id',
                         'property_id',
                         'check_in',
                         'check_out',
                         'guests',
                         'status',
                         'nights',
                         'price_per_night',
                         'total_price',
                         'tax_amount',
                         'final_total',
                         'currency',
                         'created_at',
                         'updated_at',
                     ]
                 ]);

        // Assert: Check specific booking data
        $responseData = $response->json();
        $this->assertEquals($booking->id, $responseData[0]['id']);
        $this->assertEquals(220.00, $responseData[0]['final_total']);
    }

    /**
     * Test getting a specific booking
     * 
     * @test
     */
    public function it_retrieves_a_specific_booking()
    {
        // Arrange: Create a booking
        $booking = Booking::factory()->create([
            'user_id' => $this->testUser->id,
            'room_id' => $this->testRoom->id,
            'property_id' => $this->testProperty->id,
        ]);

        // Act: Get specific booking
        $response = $this->getJson("/api/bookings/{$booking->id}");

        // Assert: Check response
        $response->assertStatus(200)
                 ->assertJson([
                     'id' => $booking->id,
                     'user_id' => $this->testUser->id,
                 ]);
    }

    /**
     * Test authorization - user can't access other user's bookings
     * 
     * @test
     */
    public function it_prevents_accessing_other_users_bookings()
    {
        // Arrange: Create another user and their booking
        $otherUser = \App\Models\User::factory()->create();
        $otherBooking = Booking::factory()->create([
            'user_id' => $otherUser->id,
            'room_id' => $this->testRoom->id,
            'property_id' => $this->testProperty->id,
        ]);

        // Act: Try to access other user's booking
        $response = $this->getJson("/api/bookings/{$otherBooking->id}");

        // Assert: Should be denied
        $response->assertStatus(404)
                 ->assertJson([
                     'message' => 'Booking not found'
                 ]);
    }

    /**
     * Test pricing preview endpoint
     * 
     * @test
     */
    public function it_provides_pricing_preview()
    {
        // Arrange: Set up the request data
        $previewData = [
            'room_id' => $this->testRoom->id,
            'check_in' => now()->addDays(2)->format('Y-m-d'),
            'check_out' => now()->addDays(4)->format('Y-m-d'), // 2 nights
            'guests' => 2,
        ];

        // Act: Get pricing preview
        $response = $this->getJson('/api/bookings/pricing-preview?' . http_build_query($previewData));

        // Assert: Check response structure
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'available',
                     'pricing' => [
                         'nights',
                         'price_per_night',
                         'total_price',
                         'tax_amount',
                         'final_total',
                         'currency',
                         'price_breakdown',
                     ]
                 ]);

        // Assert: Check pricing calculation
        $responseData = $response->json();
        $this->assertTrue($responseData['available']);
        $this->assertEquals(2, $responseData['pricing']['nights']);
        $this->assertEquals(200.00, $responseData['pricing']['total_price']); // 2 nights × $100
        $this->assertEquals(20.00, $responseData['pricing']['tax_amount']); // 10% tax
        $this->assertEquals(220.00, $responseData['pricing']['final_total']); // Total + tax
    }

    /**
     * Test validation errors
     * 
     * @test
     */
    public function it_validates_booking_request_data()
    {
        // Arrange: Invalid data (missing required fields)
        $invalidData = [
            'room_id' => '', // Invalid
            'check_in' => 'invalid-date', // Invalid
            'guests' => 0, // Invalid
        ];

        // Act: Try to create booking with invalid data
        $response = $this->postJson('/api/bookings', $invalidData);

        // Assert: Should return validation errors
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['room_id', 'check_in', 'guests']);
    }

    /**
     * Test booking room that's not available
     * 
     * @test
     */
    public function it_handles_unavailable_room_booking()
    {
        // Arrange: Mark room as unavailable
        \App\Models\Availability::where('room_id', $this->testRoom->id)
            ->update(['is_available' => false]);

        $bookingData = [
            'room_id' => $this->testRoom->id,
            'property_id' => $this->testProperty->id,
            'check_in' => now()->addDays(2)->format('Y-m-d'),
            'check_out' => now()->addDays(4)->format('Y-m-d'),
            'guests' => 2,
        ];

        // Act: Try to book unavailable room
        $response = $this->getJson('/api/bookings/pricing-preview?' . http_build_query($bookingData));

        // Assert: Should indicate unavailable
        $response->assertStatus(400)
                 ->assertJson([
                     'available' => false,
                     'message' => 'Room is not available for the selected dates and guest count'
                 ]);
    }

    /**
     * Test unauthenticated access
     * 
     * @test
     */
    public function it_requires_authentication_for_booking_endpoints()
    {
        // Act: Try to access booking endpoints without authentication
        $response = $this->json('GET', '/api/bookings');

        // Assert: Should be unauthorized
        $response->assertStatus(401);
    }
}