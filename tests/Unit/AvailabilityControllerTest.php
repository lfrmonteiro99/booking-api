<?php

namespace Tests\Unit;

use App\Http\Controllers\AvailabilityController;
use App\Services\AvailabilityService;
use App\Http\Requests\AvailabilityRequest;
use Illuminate\Http\Request;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class AvailabilityControllerTest extends TestCase
{
    private $availabilityService;
    private $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->availabilityService = Mockery::mock(AvailabilityService::class);
        $this->controller = new AvailabilityController($this->availabilityService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_returns_successful_availability_response()
    {
        $today = Carbon::now()->format('Y-m-d');
        $tomorrow = Carbon::now()->addDay()->format('Y-m-d');

        $requestData = [
            'property_id' => 'property_1',
            'check_in' => $today,
            'check_out' => $tomorrow,
            'guests' => 2,
        ];

        $request = new AvailabilityRequest($requestData);
        $validator = Validator::make($requestData, $request->rules());
        $request->setValidator($validator);

        $serviceResponse = [
            'status' => 'success',
            'property_id' => 'property_1',
            'rooms' => [
                ['room_id' => 'room_1', 'max_guests' => 2, 'total_price' => 100],
            ],
        ];

        $this->availabilityService
            ->shouldReceive('checkAvailability')
            ->once()
            ->with($request->all())
            ->andReturn($serviceResponse);

        $response = $this->controller->check($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['reply' => $serviceResponse], $response->getData(true));
    }

    #[Test]
    public function it_returns_error_availability_response()
    {
        $today = Carbon::now()->format('Y-m-d');
        $futureDate = Carbon::now()->addDays(31)->format('Y-m-d');

        $requestData = [
            'property_id' => 'property_1',
            'check_in' => $today,
            'check_out' => $futureDate,
            'guests' => 2,
        ];

        $request = new AvailabilityRequest($requestData);
        $validator = Validator::make($requestData, $request->rules());
        $request->setValidator($validator);

        $serviceResponse = [
            'status' => 'error',
            'message' => 'Date range cannot exceed 30 days',
        ];

        $this->availabilityService
            ->shouldReceive('checkAvailability')
            ->once()
            ->with($request->all())
            ->andReturn($serviceResponse);

        $response = $this->controller->check($request);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals(['reply' => $serviceResponse], $response->getData(true));
    }
} 