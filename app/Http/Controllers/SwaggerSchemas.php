<?php

namespace App\Http\Controllers;

/**
 * @OA\Schema(
 *     schema="Booking",
 *     type="object",
 *     title="Booking",
 *     description="Booking model",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="room_id", type="integer", example=1),
 *     @OA\Property(property="property_id", type="integer", example=1),
 *     @OA\Property(property="check_in", type="string", format="date", example="2024-07-01"),
 *     @OA\Property(property="check_out", type="string", format="date", example="2024-07-03"),
 *     @OA\Property(property="guests", type="integer", example=2),
 *     @OA\Property(property="status", type="string", enum={"pending", "confirmed", "cancelled"}, example="pending"),
 *     @OA\Property(property="nights", type="integer", example=2),
 *     @OA\Property(property="price_per_night", type="number", format="float", example=120.00),
 *     @OA\Property(property="total_price", type="number", format="float", example=240.00),
 *     @OA\Property(property="tax_amount", type="number", format="float", example=24.00),
 *     @OA\Property(property="final_total", type="number", format="float", example=264.00),
 *     @OA\Property(property="currency", type="string", example="USD"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-06-24T12:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-06-24T12:00:00Z")
 * )
 */

/**
 * @OA\Schema(
 *     schema="BookingRequest",
 *     type="object",
 *     title="Booking Request",
 *     description="Request body for creating a booking",
 *     required={"room_id", "property_id", "check_in", "check_out", "guests"},
 *     @OA\Property(property="room_id", type="integer", example=1, description="ID of the room to book"),
 *     @OA\Property(property="property_id", type="integer", example=1, description="ID of the property"),
 *     @OA\Property(property="check_in", type="string", format="date", example="2024-07-01", description="Check-in date"),
 *     @OA\Property(property="check_out", type="string", format="date", example="2024-07-03", description="Check-out date"),
 *     @OA\Property(property="guests", type="integer", minimum=1, example=2, description="Number of guests")
 * )
 */

/**
 * @OA\Schema(
 *     schema="UpdateBookingRequest",
 *     type="object",
 *     title="Update Booking Request",
 *     description="Request body for updating a booking",
 *     @OA\Property(property="check_in", type="string", format="date", example="2024-07-01", description="New check-in date"),
 *     @OA\Property(property="check_out", type="string", format="date", example="2024-07-03", description="New check-out date"),
 *     @OA\Property(property="guests", type="integer", minimum=1, example=2, description="New number of guests")
 * )
 */

class SwaggerSchemas
{
    // This class exists only to hold the schema definitions
}