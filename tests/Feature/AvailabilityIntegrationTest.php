<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\Room;
use App\Models\Availability;
use Illuminate\Support\Facades\Queue;

/**
 * Integration tests for availability checking and ingestion
 * Tests the complete availability flow from API to database
 */
class AvailabilityIntegrationTest extends IntegrationTestCase
{
    /**
     * Test availability checking endpoint
     * 
     * @test
     */
    public function it_checks_room_availability()
    {
        // Arrange: Prepare availability query
        $availabilityQuery = [
            'property_id' => $this->testProperty->property_id,
            'check_in' => now()->addDays(2)->format('Y-m-d'),
            'check_out' => now()->addDays(4)->format('Y-m-d'),
            'guests' => 2,
        ];

        // Act: Check availability
        $response = $this->getJson('/api/availability?' . http_build_query($availabilityQuery));

        // Assert: Check response structure
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'reply' => [
                         'status',
                         'property_id',
                         'rooms',
                     ]
                 ]);

        // Assert: Should find available rooms
        $responseData = $response->json();
        $this->assertEquals('success', $responseData['reply']['status']);
        $this->assertNotEmpty($responseData['reply']['rooms']);
    }

    /**
     * Test availability with no rooms available
     * 
     * @test
     */
    public function it_handles_no_availability()
    {
        // Arrange: Mark all rooms as unavailable
        Availability::where('room_id', $this->testRoom->id)
            ->update(['is_available' => false]);

        $availabilityQuery = [
            'property_id' => $this->testProperty->property_id,
            'check_in' => now()->addDays(2)->format('Y-m-d'),
            'check_out' => now()->addDays(4)->format('Y-m-d'),
            'guests' => 2,
        ];

        // Act: Check availability
        $response = $this->getJson('/api/availability?' . http_build_query($availabilityQuery));

        // Assert: Should return empty rooms
        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertEquals('success', $responseData['reply']['status']);
        $this->assertEmpty($responseData['reply']['rooms']);
    }

    /**
     * Test availability with guest count exceeding room capacity
     * 
     * @test
     */
    public function it_filters_rooms_by_guest_capacity()
    {
        // Arrange: Request more guests than room can accommodate
        $availabilityQuery = [
            'property_id' => $this->testProperty->property_id,
            'check_in' => now()->addDays(2)->format('Y-m-d'),
            'check_out' => now()->addDays(4)->format('Y-m-d'),
            'guests' => 5, // Room only accommodates 2
        ];

        // Act: Check availability
        $response = $this->getJson('/api/availability?' . http_build_query($availabilityQuery));

        // Assert: Should return no rooms
        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertEquals('success', $responseData['reply']['status']);
        $this->assertEmpty($responseData['reply']['rooms']);
    }

    /**
     * Test availability ingestion endpoint
     * 
     * @test
     */
    public function it_ingests_availability_data()
    {
        // Arrange: Prepare ingestion data
        Queue::fake(); // Prevent actual job processing

        $ingestionData = [
            [
                'property_id' => 'new-property-789',
                'rooms' => [
                    [
                        'room_id' => 'new-room-101',
                        'name' => 'Deluxe Room',
                        'availabilities' => [
                            [
                                'date' => now()->addDays(10)->format('Y-m-d'),
                                'price' => 150.00,
                                'allotment' => 3,
                            ],
                            [
                                'date' => now()->addDays(11)->format('Y-m-d'),
                                'price' => 160.00,
                                'allotment' => 2,
                            ],
                        ]
                    ]
                ]
            ]
        ];

        // Act: Ingest availability data
        $response = $this->postJson('/api/availability/ingest', $ingestionData);

        // Assert: Check response
        $response->assertStatus(202)
                 ->assertJson([
                     'message' => 'Availability ingestion initiated successfully.'
                 ]);

        // Assert: Verify background job was queued
        Queue::assertPushed(\App\Jobs\ProcessAvailabilityChunk::class);
    }

    /**
     * Test ingestion with invalid data
     * 
     * @test
     */
    public function it_validates_ingestion_data()
    {
        // Arrange: Invalid ingestion data (missing property_id)
        $invalidData = [
            [
                'rooms' => [] // Missing property_id
            ]
        ];

        // Act: Try to ingest invalid data
        $response = $this->postJson('/api/availability/ingest', $invalidData);

        // Assert: Should return validation error
        $response->assertStatus(400)
                 ->assertJson([
                     'message' => 'Invalid data format: property_id missing'
                 ]);
    }

    /**
     * Test ingestion with empty data
     * 
     * @test
     */
    public function it_handles_empty_ingestion_data()
    {
        // Act: Try to ingest empty data
        $response = $this->postJson('/api/availability/ingest', []);

        // Assert: Should return error
        $response->assertStatus(400)
                 ->assertJson([
                     'message' => 'No data provided for ingestion'
                 ]);
    }

    /**
     * Test availability validation errors
     * 
     * @test
     */
    public function it_validates_availability_request_parameters()
    {
        // Arrange: Invalid availability query (missing required fields)
        $invalidQuery = [
            'property_id' => '', // Invalid
            'check_in' => 'invalid-date', // Invalid
            'guests' => 'not-a-number', // Invalid
        ];

        // Act: Check availability with invalid data
        $response = $this->getJson('/api/availability?' . http_build_query($invalidQuery));

        // Assert: Should return validation errors
        $response->assertStatus(422);
    }

    /**
     * Test availability with date range exceeding limit
     * 
     * @test
     */
    public function it_limits_availability_date_range()
    {
        // Arrange: Request availability for more than 30 days
        $longRangeQuery = [
            'property_id' => $this->testProperty->property_id,
            'check_in' => now()->addDays(1)->format('Y-m-d'),
            'check_out' => now()->addDays(35)->format('Y-m-d'), // 34 days
            'guests' => 2,
        ];

        // Act: Check availability
        $response = $this->getJson('/api/availability?' . http_build_query($longRangeQuery));

        // Assert: Should return error
        $response->assertStatus(422);
        $responseData = $response->json();
        $this->assertEquals('error', $responseData['reply']['status']);
        $this->assertStringContainsString('30 days', $responseData['reply']['message']);
    }

    /**
     * Test availability caching (integration with Redis)
     * 
     * @test
     */
    public function it_caches_availability_responses()
    {
        // Arrange: Same availability query
        $availabilityQuery = [
            'property_id' => $this->testProperty->property_id,
            'check_in' => now()->addDays(2)->format('Y-m-d'),
            'check_out' => now()->addDays(4)->format('Y-m-d'),
            'guests' => 2,
        ];

        // Act: Make first request
        $firstResponse = $this->getJson('/api/availability?' . http_build_query($availabilityQuery));
        $firstResponse->assertStatus(200);

        // Act: Make second identical request (should hit cache)
        $secondResponse = $this->getJson('/api/availability?' . http_build_query($availabilityQuery));
        $secondResponse->assertStatus(200);

        // Assert: Both responses should be identical
        $this->assertEquals(
            $firstResponse->json(),
            $secondResponse->json()
        );
    }

    /**
     * Test complete availability flow with multiple properties
     * 
     * @test
     */
    public function it_handles_multiple_properties_availability()
    {
        // Arrange: Create second property with room
        $secondProperty = Property::factory()->create([
            'property_id' => 'second-property-456',
            'name' => 'Second Hotel',
        ]);

        $secondRoom = Room::factory()->create([
            'property_id' => $secondProperty->id,
            'room_id' => 'second-room-789',
            'name' => 'Second Room',
            'max_guests' => 4,
        ]);

        // Create availability for second property (for multiple days to ensure coverage)
        Availability::factory()->create([
            'room_id' => $secondRoom->id,
            'date' => now()->addDays(2)->format('Y-m-d'),
            'is_available' => true,
            'price' => 200.00,
            'max_guests' => 4,
        ]);
        
        // Also create for the next day to cover check_out
        Availability::factory()->create([
            'room_id' => $secondRoom->id,
            'date' => now()->addDays(3)->format('Y-m-d'),
            'is_available' => true,
            'price' => 200.00,
            'max_guests' => 4,
        ]);

        // Act: Check availability for first property
        $firstPropertyQuery = [
            'property_id' => $this->testProperty->property_id,
            'check_in' => now()->addDays(2)->format('Y-m-d'),
            'check_out' => now()->addDays(3)->format('Y-m-d'),
            'guests' => 2,
        ];

        $firstResponse = $this->getJson('/api/availability?' . http_build_query($firstPropertyQuery));

        // Act: Check availability for second property
        $secondPropertyQuery = [
            'property_id' => $secondProperty->property_id,
            'check_in' => now()->addDays(2)->format('Y-m-d'),
            'check_out' => now()->addDays(3)->format('Y-m-d'),
            'guests' => 4, // Match the max_guests of the room
        ];

        $secondResponse = $this->getJson('/api/availability?' . http_build_query($secondPropertyQuery));

        // Assert: Both should return results
        $firstResponse->assertStatus(200);
        $secondResponse->assertStatus(200);

        $firstData = $firstResponse->json();
        $secondData = $secondResponse->json();

        // Assert: Each should return their respective property
        $this->assertEquals($this->testProperty->property_id, $firstData['reply']['property_id']);
        $this->assertEquals($secondProperty->property_id, $secondData['reply']['property_id']);

        // Assert: Should find rooms for both
        $this->assertNotEmpty($firstData['reply']['rooms']);
        $this->assertNotEmpty($secondData['reply']['rooms']);
    }
}