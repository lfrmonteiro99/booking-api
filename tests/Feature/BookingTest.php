<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Room;
use App\Models\Property;
use App\Models\Booking;
use App\Models\Availability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $property;
    protected $room;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->property = Property::factory()->create();
        $this->room = Room::factory()->create(['property_id' => $this->property->id]);
        
        // Create availability data for the next 30 days
        for ($i = 0; $i <= 30; $i++) {
            Availability::factory()->create([
                'room_id' => $this->room->id,
                'date' => now()->addDays($i)->format('Y-m-d'),
                'is_available' => true,
                'price' => 100.00,
                'max_guests' => 4,
            ]);
        }
    }

    public function test_user_can_list_their_bookings()
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'room_id' => $this->room->id,
            'property_id' => $this->property->id,
        ]);
        $response = $this->actingAs($this->user)->getJson('/api/bookings');
        $response->assertOk()->assertJsonFragment(['id' => $booking->id]);
    }

    public function test_user_can_create_a_booking()
    {
        $data = [
            'room_id' => $this->room->id,
            'property_id' => $this->property->id,
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDay()->toDateString(),
            'guests' => 2,
        ];
        $response = $this->actingAs($this->user)->postJson('/api/bookings', $data);
        $response->assertStatus(202)->assertJson(['message' => 'Booking is being processed. You will receive a confirmation email shortly.']);
    }

    public function test_user_can_view_a_booking()
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'room_id' => $this->room->id,
            'property_id' => $this->property->id,
        ]);
        $response = $this->actingAs($this->user)->getJson('/api/bookings/' . $booking->id);
        $response->assertOk()->assertJsonFragment(['id' => $booking->id]);
    }

    public function test_user_can_update_a_booking()
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'room_id' => $this->room->id,
            'property_id' => $this->property->id,
            'guests' => 2,
        ]);
        $response = $this->actingAs($this->user)->putJson('/api/bookings/' . $booking->id, ['guests' => 3]);
        $response->assertStatus(202)->assertJson(['message' => 'Booking update is being processed. You will receive an email shortly.']);
    }

    public function test_user_can_delete_a_booking()
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'room_id' => $this->room->id,
            'property_id' => $this->property->id,
        ]);
        $response = $this->actingAs($this->user)->deleteJson('/api/bookings/' . $booking->id);
        $response->assertStatus(202)->assertJson(['message' => 'Booking cancellation is being processed. You will receive an email shortly.']);
    }
} 