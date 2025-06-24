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
     * Display a listing of the resource.
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
     * Store a newly created resource in storage.
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
     * Display the specified resource.
     */
    public function show($id): JsonResponse
    {
        $booking = $this->bookingService->getById($id);
        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }
        return response()->json($booking);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBookingRequest $request, $id): JsonResponse
    {
        $validated = $request->validated();
        $validated['id'] = $id;
        $user = Auth::user();
        ProcessBookingJob::dispatch($validated, $user, UpdateBookingStrategy::class);
        return response()->json(['message' => 'Booking update is being processed. You will receive an email shortly.'], 202);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id): JsonResponse
    {
        $user = Auth::user();
        ProcessBookingJob::dispatch(['id' => $id], $user, DeleteBookingStrategy::class);
        return response()->json(['message' => 'Booking cancellation is being processed. You will receive an email shortly.'], 202);
    }
}
