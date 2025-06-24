<?php

namespace App\Services;

use Illuminate\Support\Collection;

class PricingService
{
    const TAX_RATE = 0.10; // 10% tax rate
    const DEFAULT_CURRENCY = 'USD';

    /**
     * Calculate pricing details from availability data
     */
    public function calculateBookingPrice(Collection $availabilities, int $nights): array
    {
        if ($nights <= 0) {
            throw new \InvalidArgumentException('Number of nights must be greater than 0');
        }

        if ($availabilities->count() !== $nights) {
            throw new \Exception('Availability data count does not match number of nights');
        }

        // Calculate total price
        $totalPrice = $availabilities->sum('price');
        $averagePricePerNight = $totalPrice / $nights;

        // Calculate tax
        $taxAmount = $totalPrice * self::TAX_RATE;
        $finalTotal = $totalPrice + $taxAmount;

        return [
            'nights' => $nights,
            'price_per_night' => round($averagePricePerNight, 2),
            'total_price' => round($totalPrice, 2),
            'tax_amount' => round($taxAmount, 2),
            'final_total' => round($finalTotal, 2),
            'currency' => self::DEFAULT_CURRENCY,
            'price_breakdown' => $availabilities->map(function ($availability) {
                return [
                    'date' => $availability->date->format('Y-m-d'),
                    'price' => $availability->price
                ];
            })->toArray()
        ];
    }

    /**
     * Calculate just the subtotal from pricing data
     */
    public function calculateSubtotal(Collection $availabilities): float
    {
        return round($availabilities->sum('price'), 2);
    }

    /**
     * Calculate tax amount
     */
    public function calculateTax(float $subtotal): float
    {
        return round($subtotal * self::TAX_RATE, 2);
    }

    /**
     * Calculate final total with tax
     */
    public function calculateTotal(float $subtotal, float $tax): float
    {
        return round($subtotal + $tax, 2);
    }
}