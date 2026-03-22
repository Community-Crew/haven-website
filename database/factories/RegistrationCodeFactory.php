<?php

namespace Database\Factories;

use App\Models\RegistrationCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RegistrationCode>
 */
class RegistrationCodeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'is_used' => $this->faker->boolean(),
        ];
    }
}
