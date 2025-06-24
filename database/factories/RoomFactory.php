<?php

namespace Database\Factories;

use App\Models\Room;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Room>
 */
class RoomFactory extends Factory
{
    protected $model = Room::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'room_id' => $this->faker->uuid,
            'name' => $this->faker->word . ' Room',
            'description' => $this->faker->sentence,
            'max_guests' => $this->faker->numberBetween(1, 6),
        ];
    }
}
