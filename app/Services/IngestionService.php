<?php

namespace App\Services;

use Illuminate\Support\Facades\Bus;
use App\Jobs\ProcessAvailabilityChunk;

class IngestionService
{
    /**
     * Process availability ingestion data
     */
    public function processAvailabilityIngestion(array $propertiesData): array
    {
        if (empty($propertiesData)) {
            return [
                'success' => false,
                'message' => 'No data provided for ingestion',
                'status_code' => 400
            ];
        }

        // Validate and process each property
        foreach ($propertiesData as $propertyData) {
            $validationResult = $this->validatePropertyData($propertyData);
            
            if (!$validationResult['valid']) {
                return [
                    'success' => false,
                    'message' => $validationResult['message'],
                    'status_code' => 400
                ];
            }

            // Dispatch job for valid property data
            Bus::dispatch(new ProcessAvailabilityChunk(
                $propertyData['property_id'], 
                $propertyData['rooms']
            ));
        }

        return [
            'success' => true,
            'message' => 'Availability ingestion initiated successfully.',
            'status_code' => 202
        ];
    }

    /**
     * Validate individual property data structure
     */
    private function validatePropertyData(array $propertyData): array
    {
        $propertyId = $propertyData['property_id'] ?? null;
        $roomsData = $propertyData['rooms'] ?? [];

        if (empty($propertyId)) {
            return [
                'valid' => false,
                'message' => 'Invalid data format: property_id missing'
            ];
        }

        if (empty($roomsData)) {
            return [
                'valid' => false,
                'message' => 'Invalid data format: rooms missing or empty'
            ];
        }

        return ['valid' => true];
    }
}