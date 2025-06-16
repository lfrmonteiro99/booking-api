<?php

namespace App\IntentHandlers;

use App\Interfaces\AvailabilityServiceInterface;
use App\Interfaces\DialogflowIntentHandlerInterface;

class CheckAvailabilityIntentHandler implements DialogflowIntentHandlerInterface
{
    protected AvailabilityServiceInterface $availabilityService;

    public function __construct(AvailabilityServiceInterface $availabilityService)
    {
        $this->availabilityService = $availabilityService;
    }

    public function handle(array $params): string
    {
        return $this->availabilityService->replyAvailability($params);
    }
} 