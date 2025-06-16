<?php

namespace Tests\Unit;

use App\Interfaces\AvailabilityRepositoryInterface;
use App\Services\AvailabilityService;
use App\Repositories\AvailabilityRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AvailabilityServiceTest extends TestCase
{
    private $availabilityRepository;
    private $availabilityService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->availabilityRepository = Mockery::mock(AvailabilityRepository::class);
        $this->availabilityService = new AvailabilityService($this->availabilityRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_checks_availability_successfully(): void
    {
        $data = [
            'property_id' => 'property_1',
            'check_in' => '2024-03-20',
            'check_out' => '2024-03-21',
            'guests' => 2,
        ];
        $expectedRooms = [
            ['room_id' => 'room_1', 'max_guests' => 2, 'total_price' => 100],
        ];

        $this->availabilityRepository
            ->shouldReceive('getAvailableRooms')
            ->once()
            ->with(
                $data['property_id'],
                Mockery::type('Carbon\\Carbon'),
                Mockery::type('Carbon\\Carbon'),
                $data['guests'],
                false
            )
            ->andReturn($expectedRooms);

        $result = $this->availabilityService->checkAvailability($data);

        $this->assertIsArray($result);
        $this->assertEquals('success', $result['status']);
        $this->assertEquals($data['property_id'], $result['property_id']);
        $this->assertEquals($expectedRooms, $result['rooms']);
    }

    #[Test]
    public function it_checks_availability_successfully_with_full_availability(): void
    {
        $data = [
            'property_id' => 'property_1',
            'check_in' => '2025-06-08',
            'check_out' => '2025-06-10',
            'guests' => 2,
            'full_availability' => true
        ];

        $expectedRooms = [
            [
                'room_id' => 'room_A',
                'max_guests' => 2,
                'total_price' => 300.00
            ]
        ];

        $this->availabilityRepository
            ->shouldReceive('getAvailableRooms')
            ->once()
            ->with(
                $data['property_id'],
                Mockery::type('Carbon\\Carbon'),
                Mockery::type('Carbon\\Carbon'),
                $data['guests'],
                true
            )
            ->andReturn($expectedRooms);

        $result = $this->availabilityService->checkAvailability($data);

        $this->assertIsArray($result);
        $this->assertEquals('success', $result['status']);
        $this->assertEquals($data['property_id'], $result['property_id']);
        $this->assertEquals($expectedRooms, $result['rooms']);
    }

    #[Test]
    public function it_returns_error_for_date_range_exceeding_30_days(): void
    {
        $data = [
            'property_id' => 'property_1',
            'check_in' => '2024-03-20',
            'check_out' => '2024-04-21',
            'guests' => 2,
        ];

        $result = $this->availabilityService->checkAvailability($data);

        $this->assertIsArray($result);
        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Date range cannot exceed 30 days', $result['message']);
    }

    #[Test]
    public function it_formats_successful_availability_response(): void
    {
        $response = [
            'status' => 'success',
            'property_id' => 'property_1',
            'rooms' => [
                ['room_id' => 'room_1', 'max_guests' => 2, 'total_price' => 100],
            ],
        ];

        $result = $this->availabilityService->formatAvailabilityResponse($response);

        $this->assertStringContainsString('Found 1 room(s) available for property property_1', $result);
        $this->assertStringContainsString('Room room_1 (max guests: 2) at a total price of $100.00', $result);
    }

    #[Test]
    public function it_formats_error_availability_response(): void
    {
        $response = [
            'status' => 'error',
            'message' => 'Invalid date range',
        ];

        $result = $this->availabilityService->formatAvailabilityResponse($response);

        $this->assertEquals('There was an error checking availability: Invalid date range', $result);
    }

    #[Test]
    public function it_formats_no_rooms_availability_response(): void
    {
        $response = [
            'status' => 'success',
            'property_id' => 'property_1',
            'rooms' => [],
        ];

        $result = $this->availabilityService->formatAvailabilityResponse($response);

        $this->assertEquals('No rooms are available for the selected criteria.', $result);
    }

    #[Test]
    public function reply_availability_returns_successful_message_with_rooms(): void
    {
        $params = [
            'property_id' => 'property_1',
            'check_in' => '2024-07-01',
            'check_out' => '2024-07-07',
            'guests' => 2,
            'full_availability' => true
        ];

        $mockedCheckAvailabilityResponse = [
            'status' => 'success',
            'property_id' => 'property_1',
            'rooms' => [
                ['room_id' => 'room_A', 'max_guests' => 2, 'total_price' => 150.00],
                ['room_id' => 'room_B', 'max_guests' => 3, 'total_price' => 200.00],
            ],
        ];

        $this->availabilityRepository
            ->shouldReceive('getAvailableRooms')
            ->once()
            ->with(
                $params['property_id'],
                Mockery::type('Carbon\\Carbon'),
                Mockery::type('Carbon\\Carbon'),
                $params['guests'],
                true // Expect true for full_availability
            )
            ->andReturn($mockedCheckAvailabilityResponse['rooms']);

        $result = $this->availabilityService->replyAvailability($params);

        $this->assertStringContainsString('Yes! We have 2 room(s) available from 2024-07-01 to 2024-07-07, starting at €150.00. Want to reserve now?', $result);
    }

    #[Test]
    public function reply_availability_returns_no_rooms_message(): void
    {
        $params = [
            'property_id' => 'property_1',
            'check_in' => '2024-07-01',
            'check_out' => '2024-07-07',
            'guests' => 2,
            'full_availability' => true
        ];

        $mockedCheckAvailabilityResponse = [
            'status' => 'success',
            'property_id' => 'property_1',
            'rooms' => [],
        ];

        $this->availabilityRepository
            ->shouldReceive('getAvailableRooms')
            ->once()
            ->with(
                $params['property_id'],
                Mockery::type('Carbon\\Carbon'),
                Mockery::type('Carbon\\Carbon'),
                $params['guests'],
                true
            )
            ->andReturn($mockedCheckAvailabilityResponse['rooms']);

        $result = $this->availabilityService->replyAvailability($params);

        $this->assertEquals('Sorry, no availability found for those dates and guest count.', $result);
    }

    #[Test]
    public function reply_availability_returns_error_message_from_check_availability(): void
    {
        $params = [
            'property_id' => 'property_1',
            'check_in' => '2024-03-20',
            'check_out' => '2024-04-21', // This will trigger the date range error
            'guests' => 2,
            'full_availability' => false
        ];

        $result = $this->availabilityService->replyAvailability($params);

        $this->assertEquals('Date range cannot exceed 30 days', $result);
    }

    #[Test]
    public function reply_availability_returns_successful_message_without_full_availability(): void
    {
        $params = [
            'property_id' => 'property_1',
            'check_in' => '2024-07-01',
            'check_out' => '2024-07-07',
            'guests' => 2,
            'full_availability' => false // Explicitly set to false
        ];

        $mockedCheckAvailabilityResponse = [
            'status' => 'success',
            'property_id' => 'property_1',
            'rooms' => [
                ['room_id' => 'room_C', 'max_guests' => 4, 'total_price' => 250.00],
            ],
        ];

        $this->availabilityRepository
            ->shouldReceive('getAvailableRooms')
            ->once()
            ->with(
                $params['property_id'],
                Mockery::type('Carbon\\Carbon'),
                Mockery::type('Carbon\\Carbon'),
                $params['guests'],
                false // Expect false for full_availability
            )
            ->andReturn($mockedCheckAvailabilityResponse['rooms']);

        $result = $this->availabilityService->replyAvailability($params);

        $this->assertStringContainsString('Yes! We have 1 room(s) available from 2024-07-01 to 2024-07-07, starting at €250.00. Want to reserve now?', $result);
    }
} 