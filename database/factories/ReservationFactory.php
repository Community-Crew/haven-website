<?php

namespace Database\Factories;

use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Reservation>
 */
class ReservationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startAt = $this->faker->dateTimeBetween('now', '+3 weeks');

        return [
            'name' => $this->faker->name(),
            'start_at' => $startAt,
            'end_at' => $this->faker->dateTimeBetween($startAt, '+3 weeks'),
            'share_user' => $this->faker->randomElement([true, false]),
            'user_id' => 1,
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected', 'cancelled']),
            'room_id' => $this->faker->randomElement(Room::all()->pluck('id')->toArray()),
        ];
    }
}
