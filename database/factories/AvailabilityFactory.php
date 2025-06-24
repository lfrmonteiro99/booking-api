<?php

namespace Database\Factories;

use App\Models\Availability;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

class AvailabilityFactory extends Factory
{
    protected $model = Availability::class;

    public function definition(): array
    {
        return [
            'room_id' => Room::factory(),
            'date' => $this->faker->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'is_available' => true,
            'price' => $this->faker->randomFloat(2, 50, 300),
            'max_guests' => $this->faker->numberBetween(1, 6),
        ];
    }

    /**
     * Make the availability unavailable
     */
    public function unavailable()
    {
        return $this->state(fn (array $attributes) => [
            'is_available' => false,
        ]);
    }

    /**
     * Set specific date
     */
    public function forDate(string $date)
    {
        return $this->state(fn (array $attributes) => [
            'date' => $date,
        ]);
    }

    /**
     * Set specific price
     */
    public function withPrice(float $price)
    {
        return $this->state(fn (array $attributes) => [
            'price' => $price,
        ]);
    }
}