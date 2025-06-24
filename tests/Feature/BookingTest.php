<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Room;
use App\Models\Property;
use App\Models\Booking;
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
        $response->assertCreated()->assertJsonFragment(['room_id' => $this->room->id]);
        $this->assertDatabaseHas('bookings', ['user_id' => $this->user->id, 'room_id' => $this->room->id]);
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
        $response->assertOk()->assertJsonFragment(['guests' => 3]);
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'guests' => 3]);
    }

    public function test_user_can_delete_a_booking()
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'room_id' => $this->room->id,
            'property_id' => $this->property->id,
        ]);
        $response = $this->actingAs($this->user)->deleteJson('/api/bookings/' . $booking->id);
        $response->assertOk()->assertJsonFragment(['message' => 'Booking deleted successfully']);
        $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
    }
} 