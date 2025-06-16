<?php

namespace App\Interfaces;

interface AvailabilityServiceInterface
{
    public function checkAvailability(array $data): array;
    public function formatAvailabilityResponse(array $response): string;
    public function replyAvailability(array $params): string;
}