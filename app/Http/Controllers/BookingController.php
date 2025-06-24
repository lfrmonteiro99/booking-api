<?php

namespace App\Http\Controllers;

use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Jobs\ProcessBookingJob;
use App\Strategies\Booking\CreateBookingStrategy;
use App\Strategies\Booking\UpdateBookingStrategy;
use App\Strategies\Booking\DeleteBookingStrategy;
use App\Http\Requests\BookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    protected BookingService $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    /**
     * @OA\Get(
     *     path="/api/bookings",
     *     summary="Get all bookings for authenticated user",
     *     tags={"Bookings"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of user's bookings",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Booking")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $userId = Auth::id();
        $bookings = $this->bookingService->getAllForUser($userId);
        return response()->json($bookings);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * @OA\Post(
     *     path="/api/bookings",
     *     summary="Create a new booking",
     *     tags={"Bookings"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/BookingRequest")
     *     ),
     *     @OA\Response(
     *         response=202,
     *         description="Booking is being processed",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Booking is being processed. You will receive a confirmation email shortly.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     */
    public function store(BookingRequest $request): JsonResponse
    {
        Log::info('BookingController@store: Booking creation request received', ['user_id' => Auth::id(), 'request' => $request->all()]);
        try {
        $validated = $request->validated();
        $user = Auth::user();
            ProcessBookingJob::dispatch($validated, $user, CreateBookingStrategy::class);
            Log::info('BookingController@store: Booking job dispatched', ['user_id' => $user->id, 'booking_data' => $validated]);
            return response()->json(['message' => 'Booking is being processed. You will receive a confirmation email shortly.'], 202);
        } catch (\Exception $e) {
            Log::error('BookingController@store: Error creating booking', ['error' => $e->getMessage(), 'user_id' => Auth::id()]);
            return response()->json(['message' => 'Error creating booking: ' . $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/bookings/{id}",
     *     summary="Get a specific booking",
     *     tags={"Bookings"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Booking ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Booking details",
     *         @OA\JsonContent(ref="#/components/schemas/Booking")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Booking not found or not owned by user"
     *     )
     * )
     */
    public function show($id): JsonResponse
    {
        $result = $this->bookingService->getBookingForUser($id, Auth::id());
        
        if (!$result['success']) {
            return response()->json(['message' => $result['message']], $result['status_code']);
        }
        
        return response()->json($result['booking']);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * @OA\Put(
     *     path="/api/bookings/{id}",
     *     summary="Update a specific booking",
     *     tags={"Bookings"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Booking ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/UpdateBookingRequest")
     *     ),
     *     @OA\Response(
     *         response=202,
     *         description="Booking update is being processed",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Booking update is being processed. You will receive an email shortly.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Booking not found or not owned by user"
     *     )
     * )
     */
    public function update(UpdateBookingRequest $request, $id): JsonResponse
    {
        $result = $this->bookingService->updateBookingForUser($id, $request->validated(), Auth::id());
        
        if (!$result['success']) {
            return response()->json(['message' => $result['message']], $result['status_code']);
        }
        
        $validated = $request->validated();
        $validated['id'] = $id;
        $user = Auth::user();
        ProcessBookingJob::dispatch($validated, $user, UpdateBookingStrategy::class);
        return response()->json(['message' => 'Booking update is being processed. You will receive an email shortly.'], 202);
    }

    /**
     * @OA\Delete(
     *     path="/api/bookings/{id}",
     *     summary="Cancel a specific booking",
     *     tags={"Bookings"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Booking ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=202,
     *         description="Booking cancellation is being processed",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Booking cancellation is being processed. You will receive an email shortly.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Booking not found or not owned by user"
     *     )
     * )
     */
    public function destroy($id): JsonResponse
    {
        $result = $this->bookingService->deleteBookingForUser($id, Auth::id());
        
        if (!$result['success']) {
            return response()->json(['message' => $result['message']], $result['status_code']);
        }
        
        $user = Auth::user();
        ProcessBookingJob::dispatch(['id' => $id], $user, DeleteBookingStrategy::class);
        return response()->json(['message' => 'Booking cancellation is being processed. You will receive an email shortly.'], 202);
    }

    /**
     * @OA\Get(
     *     path="/api/bookings/pricing-preview",
     *     summary="Get pricing preview for booking dates",
     *     tags={"Bookings"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="room_id",
     *         in="query",
     *         required=true,
     *         description="Room ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="check_in",
     *         in="query",
     *         required=true,
     *         description="Check-in date",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="check_out",
     *         in="query",
     *         required=true,
     *         description="Check-out date",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="guests",
     *         in="query",
     *         required=true,
     *         description="Number of guests",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Pricing preview",
     *         @OA\JsonContent(
     *             @OA\Property(property="available", type="boolean", example=true),
     *             @OA\Property(property="pricing", type="object",
     *                 @OA\Property(property="nights", type="integer", example=2),
     *                 @OA\Property(property="price_per_night", type="number", example=120.00),
     *                 @OA\Property(property="total_price", type="number", example=240.00),
     *                 @OA\Property(property="tax_amount", type="number", example=24.00),
     *                 @OA\Property(property="final_total", type="number", example=264.00),
     *                 @OA\Property(property="currency", type="string", example="USD")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid request or room not available"
     *     )
     * )
     */
    public function pricingPreview(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'room_id' => 'required|integer',
            'check_in' => 'required|date|after:today',
            'check_out' => 'required|date|after:check_in',
            'guests' => 'required|integer|min:1',
        ]);

        $result = $this->bookingService->getPricingPreview($validatedData, Auth::id());

        if (!$result['success']) {
            return response()->json([
                'available' => $result['available'],
                'message' => $result['message']
            ], 400);
        }

        return response()->json([
            'available' => $result['available'],
            'pricing' => $result['pricing']
        ]);
    }
}
