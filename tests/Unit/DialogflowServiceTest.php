<?php

namespace Tests\Unit;

use App\Services\DialogflowService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DialogflowServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Log::shouldReceive('error')->byDefault();
    }

    #[Test]
    public function detect_intent_returns_expected_parameters()
    {
        $fakeParameters = [
            'property_id' => 'property_1',
            'check_in' => '2024-03-20',
            'check_out' => '2024-03-21',
            'guests' => 2,
        ];
        $fakeResponse = [
            'queryResult' => [
                'parameters' => $fakeParameters
            ]
        ];

        Http::fake([
            '*' => Http::response($fakeResponse, 200)
        ]);

        Config::set('dialogflow.project_id', 'dummy-project');
        Config::set('dialogflow.language', 'en');
        Config::set('dialogflow.session_id', 'dummy-session');
        Config::set('dialogflow.credentials_path', '/tmp/dummy.json');
        Config::set('dialogflow.region', 'global');

        $service = Mockery::mock(DialogflowService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('getGoogleAccessToken')->andReturn('fake-access-token');
        $service->__construct();

        $result = $service->detectIntent('Book a room at property 1 for March 20 to March 21 for 2 guests');

        $this->assertEquals($fakeParameters, $result);
    }

    #[Test]
    public function detect_intent_handles_missing_parameters()
    {
        $fakeParameters = [
            'property_id' => 'property_1',
        ];
        $fakeResponse = [
            'queryResult' => [
                'parameters' => $fakeParameters
            ]
        ];

        Http::fake([
            '*' => Http::response($fakeResponse, 200)
        ]);

        Config::set('dialogflow.project_id', 'dummy-project');
        Config::set('dialogflow.language', 'en');
        Config::set('dialogflow.session_id', 'dummy-session');
        Config::set('dialogflow.credentials_path', '/tmp/dummy.json');
        Config::set('dialogflow.region', 'global');

        $service = Mockery::mock(DialogflowService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('getGoogleAccessToken')->andReturn('fake-access-token');
        $service->__construct();

        $result = $service->detectIntent('Book a room at property_1');

        $this->assertEquals($fakeParameters, $result);
    }
} 