<?php

namespace Tests\Unit;

use App\Http\Controllers\AvailabilityIngestionController;
use App\Jobs\ProcessAvailabilityChunk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AvailabilityIngestionControllerTest extends TestCase
{
    private $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new AvailabilityIngestionController();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_returns_error_if_no_data_provided()
    {
        $request = new Request();
        $request->headers->set('Content-Type', 'application/json');

        $response = $this->controller->ingest($request);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(['message' => 'No data provided for ingestion'], $response->getData(true));
    }

    #[Test]
    public function it_returns_error_if_property_id_or_rooms_missing()
    {
        $request = new Request();
        $request->headers->set('Content-Type', 'application/json');
        $request->json()->set('property_id', 'property_1');

        $response = $this->controller->ingest($request);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(['message' => 'Invalid data format: property_id or rooms missing'], $response->getData(true));
    }

    #[Test]
    public function it_successfully_ingests_data_and_dispatches_job()
    {
        $requestData = [
            'property_id' => 'property_1',
            'rooms' => [
                ['room_id' => 'room_1', 'max_guests' => 2],
            ],
        ];
        $request = new Request();
        $request->headers->set('Content-Type', 'application/json');
        $request->replace([$requestData]);

        Queue::fake();

        $response = $this->controller->ingest($request);

        $this->assertEquals(202, $response->getStatusCode());
        $this->assertEquals(['message' => 'Availability ingestion initiated successfully.'], $response->getData(true));

        Queue::assertPushed(ProcessAvailabilityChunk::class, function ($job) use ($requestData) {
            return $job->getPropertyId() === $requestData['property_id'] &&
                   $job->getRoomsData() === $requestData['rooms'];
        });
    }
} 