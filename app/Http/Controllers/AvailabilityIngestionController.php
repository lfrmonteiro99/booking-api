<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Bus;
use App\Jobs\ProcessAvailabilityChunk;

class AvailabilityIngestionController extends Controller
{
    /**
     * @OA\Post(
     *      path="/api/availability/ingest",
     *      summary="Ingest bulk availability data",
     *      tags={"Availability"},
     *      @OA\RequestBody(
     *          required=true,
     *          description="An array of properties with their room availabilities.",
     *          @OA\JsonContent(
     *              type="array",
     *              @OA\Items(
     *                  type="object",
     *                  required={"property_id", "rooms"},
     *                  @OA\Property(property="property_id", type="string", example="property-123"),
     *                  @OA\Property(
     *                      property="rooms",
     *                      type="array",
     *                      @OA\Items(
     *                          type="object",
     *                          required={"room_id", "name", "availabilities"},
     *                          @OA\Property(property="room_id", type="string", example="room-abc"),
     *                          @OA\Property(property="name", type="string", example="Double Room"),
     *                          @OA\Property(
     *                              property="availabilities",
     *                              type="array",
     *                              @OA\Items(
     *                                  type="object",
     *                                  required={"date", "price", "allotment"},
     *                                  @OA\Property(property="date", type="string", format="date", example="2024-06-20"),
     *                                  @OA\Property(property="price", type="number", format="float", example=150.75),
     *                                  @OA\Property(property="allotment", type="integer", example=5)
     *                              )
     *                          )
     *                      )
     *                  )
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=202,
     *          description="Availability ingestion initiated successfully."
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Invalid data format"
     *      ),
     *      security={{"sanctum": {}}}
     * )
     */
    public function ingest(Request $request)
    {
        $propertiesData = $request->all();

        if (empty($propertiesData)) {
            return response()->json(['message' => 'No data provided for ingestion'], 400);
        }

        foreach ($propertiesData as $propertyData) {
            $propertyId = $propertyData['property_id'] ?? null;
            $roomsData = $propertyData['rooms'] ?? [];

            if (empty($propertyId) || empty($roomsData)) {
                return response()->json(['message' => 'Invalid data format: property_id or rooms missing'], 400);
            }

            Bus::dispatch(new ProcessAvailabilityChunk($propertyId, $roomsData));
        }

        return response()->json(['message' => 'Availability ingestion initiated successfully.'], 202);
    }
} 