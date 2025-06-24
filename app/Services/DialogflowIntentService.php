<?php

namespace App\Services;

use App\Interfaces\AvailabilityServiceInterface;
use App\Services\DialogflowService;
use Illuminate\Contracts\Container\Container;

class DialogflowIntentService
{
    protected DialogflowService $dialogflow;
    protected AvailabilityServiceInterface $availabilityService;
    protected Container $container;
    protected array $intentHandlers;

    public function __construct(
        DialogflowService $dialogflow,
        AvailabilityServiceInterface $availabilityService,
        Container $container
    ) {
        $this->dialogflow = $dialogflow;
        $this->availabilityService = $availabilityService;
        $this->container = $container;
        $this->intentHandlers = [
            'CheckAvailability' => \App\IntentHandlers\CheckAvailabilityIntentHandler::class,
        ];
    }

    /**
     * Process user message and return intent response
     */
    public function processUserMessage(string $userMessage): array
    {
        $dialogflowResponse = $this->dialogflow->detectIntent($userMessage);

        if (!$dialogflowResponse) {
            return [
                'success' => false,
                'error' => 'Invalid response from Dialogflow',
                'response' => $dialogflowResponse,
                'status_code' => 500
            ];
        }

        $availabilityResponse = $this->availabilityService->checkAvailability($dialogflowResponse);
        $naturalLanguageReply = $this->availabilityService->formatAvailabilityResponse($availabilityResponse);

        return [
            'success' => true,
            'intent' => $dialogflowResponse['queryResult']['intent']['displayName'] ?? null,
            'parameters' => $dialogflowResponse['queryResult']['parameters'] ?? [],
            'reply' => $naturalLanguageReply,
        ];
    }

    /**
     * Process Dialogflow webhook request
     */
    public function processWebhookRequest(array $queryResult): array
    {
        $intent = $queryResult['intent']['displayName'];
        $params = $queryResult['parameters'];
        $replyText = $queryResult['fulfillmentText'] ?? "Sorry, I didn't get that.";

        if (isset($this->intentHandlers[$intent])) {
            $handlerClass = $this->intentHandlers[$intent];
            $handler = $this->container->make($handlerClass);
            $replyText = $handler->handle($params);
        }

        return [
            'success' => true,
            'fulfillmentText' => $replyText
        ];
    }
}