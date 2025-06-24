<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\DialogflowController;
use App\Http\Controllers\AvailabilityIngestionController;
use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\BookingController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Dialogflow Webhook (public, as called by Dialogflow itself)
Route::post('/dialogflow/webhook', [DialogflowController::class, 'detect'])->middleware('dialogflow.secret');

// Protected routes
Route::middleware(['throttle:api', 'auth:sanctum'])->group(function () {
    Route::post('/availability/ingest', [AvailabilityIngestionController::class, 'ingest']);
    Route::post('/dialog/detect', [DialogflowController::class, 'detectIntent']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::get('/availability', [AvailabilityController::class, 'check']);
    Route::post('/subscribe', [SubscriptionController::class, 'subscribe']);
    Route::apiResource('/bookings', BookingController::class);
});