<?php

namespace Database\Factories;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $buildings = ['Castor', 'Pollux', 'Terra'];
        $floor = ['00', '01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '14'];
        $room = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16'];

        return [
            'building' => fake()->randomElement($buildings),
            'number' => fake()->randomElement($floor).fake()->randomElement($room),
            'max_residents' => $this->faker->numberBetween(1, 6),
        ];
    }
}
