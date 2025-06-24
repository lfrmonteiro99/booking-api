<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\User;
use App\Models\Property;
use App\Models\Room;
use App\Models\Availability;

/**
 * Base class for integration tests
 * Provides common setup and helper methods
 */
abstract class IntegrationTestCase extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $testUser;
    protected Property $testProperty;
    protected Room $testRoom;
    protected string $authToken;

    /**
     * Set up test environment before each test
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Run migrations
        $this->artisan('migrate');
        
        // Create test data
        $this->createTestUser();
        $this->createTestProperty();
        $this->createTestRoom();
        $this->createTestAvailability();
    }

    /**
     * Create a test user and authenticate
     */
    protected function createTestUser(): void
    {
        $this->testUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Create auth token
        $this->authToken = $this->testUser->createToken('test-token')->plainTextToken;
    }

    /**
     * Create test property
     */
    protected function createTestProperty(): void
    {
        $this->testProperty = Property::factory()->create([
            'property_id' => 'test-property-123',
            'name' => 'Test Hotel',
        ]);
    }

    /**
     * Create test room
     */
    protected function createTestRoom(): void
    {
        $this->testRoom = Room::factory()->create([
            'property_id' => $this->testProperty->id,
            'room_id' => 'test-room-456',
            'name' => 'Test Room',
            'max_guests' => 2,
        ]);
    }

    /**
     * Create test availability data
     */
    protected function createTestAvailability(): void
    {
        // Create availability for next 7 days
        for ($i = 1; $i <= 7; $i++) {
            Availability::factory()->create([
                'room_id' => $this->testRoom->id,
                'date' => now()->addDays($i)->format('Y-m-d'),
                'is_available' => true,
                'price' => 100.00,
                'max_guests' => 2,
            ]);
        }
    }

    /**
     * Get authenticated headers for API requests
     */
    protected function getAuthHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->authToken,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Make authenticated GET request
     */
    public function getJson($uri, array $headers = [], $options = 0)
    {
        return parent::getJson($uri, array_merge($this->getAuthHeaders(), $headers), $options);
    }

    /**
     * Make authenticated POST request
     */
    public function postJson($uri, array $data = [], array $headers = [], $options = 0)
    {
        return parent::postJson($uri, $data, array_merge($this->getAuthHeaders(), $headers), $options);
    }

    /**
     * Make authenticated PUT request
     */
    public function putJson($uri, array $data = [], array $headers = [], $options = 0)
    {
        return parent::putJson($uri, $data, array_merge($this->getAuthHeaders(), $headers), $options);
    }

    /**
     * Make authenticated DELETE request
     */
    public function deleteJson($uri, array $data = [], array $headers = [], $options = 0)
    {
        return parent::deleteJson($uri, $data, array_merge($this->getAuthHeaders(), $headers), $options);
    }

    /**
     * Assert JSON response structure
     */
    protected function assertJsonStructure($response, array $structure)
    {
        $response->assertJsonStructure($structure);
    }

    /**
     * Print response for debugging
     */
    protected function debugResponse($response)
    {
        echo "\n=== RESPONSE DEBUG ===\n";
        echo "Status: " . $response->getStatusCode() . "\n";
        echo "Content: " . $response->getContent() . "\n";
        echo "=====================\n";
    }
}