<?php

namespace Database\Seeders;


use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Unit::factory()
            ->count(50)
            ->create();
    }
}
