<?php

namespace App\Http\Controllers;

use App\Http\Requests\AvailabilityRequest;
use App\Interfaces\AvailabilityServiceInterface;
use Illuminate\Http\JsonResponse;

class AvailabilityController extends Controller
{
    protected AvailabilityServiceInterface $availabilityService;

    public function __construct(AvailabilityServiceInterface $availabilityService)
    {
        $this->availabilityService = $availabilityService;
    }

    /**
     * @OA\Get(
     *     path="/api/availability",
     *     summary="Check room availability",
     *     tags={"Availability"},
     *     @OA\Parameter(
     *         name="property_id",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="check_in",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *      @OA\Parameter(
     *         name="check_out",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *      @OA\Parameter(
     *         name="guests",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response"
     *     ),
     *      @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     security={{"sanctum": {}}}
     * )
     */
    public function check(AvailabilityRequest $request): JsonResponse
    {
        $result = $this->availabilityService->checkAvailability($request->validated());
        // The formatAvailabilityResponse method is in AvailabilityService

        return response()->json(['reply' => $result], $result['status'] === 'error' ? 422 : 200);
    }
}