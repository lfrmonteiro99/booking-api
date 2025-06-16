<?php

namespace Tests\Unit;

use App\Repositories\AvailabilityRepository;
use App\Services\AvailabilityService;
use Illuminate\Support\Collection;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AvailabilityRepositoryTest extends TestCase
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
    public function check_availability_returns_success_with_rooms()
    {
        $data = [
            'property_id' => 'property_1',
            'check_in' => '2024-03-20',
            'check_out' => '2024-03-21',
            'guests' => 2,
        ];
        $expectedRooms = [
            ['room_id' => 'room_1', 'max_guests' => 2, 'total_price' => 100],
            ['room_id' => 'room_2', 'max_guests' => 3, 'total_price' => 120],
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
    public function check_availability_returns_success_with_no_rooms()
    {
        $data = [
            'property_id' => 'property_1',
            'check_in' => '2024-03-20',
            'check_out' => '2024-03-21',
            'guests' => 2,
            'full_availability' => false
        ];
        $expectedRooms = [];

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
    public function check_availability_returns_success_with_full_availability_rooms()
    {
        $data = [
            'property_id' => 'property_1',
            'check_in' => '2025-06-08',
            'check_out' => '2025-06-10',
            'guests' => 2,
            'full_availability' => true
        ];

        // The repository should return an array of rooms with total_price and max_guests, 
        // even if not all dates are available, as long as full_availability is true.
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
}