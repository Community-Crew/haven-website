<?php

namespace Database\Seeders;

use App\Models\RegistrationCode;
use App\Models\Unit;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RegistrationCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = Unit::all();

        $units->each(function (Unit $unit) {
            $number = fake()->numberBetween(0,3);
            RegistrationCode::factory()
                ->count($number) // We want to create two codes
                ->create([
                    'unit_id' => $unit->id,
                ]);
        });
    }
}
