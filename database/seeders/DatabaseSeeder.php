<?php

namespace Database\Seeders;

use App\Models\RegistrationCode;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use phpDocumentor\Reflection\DocBlock\Tags\Factory\Factory;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UnitSeeder::class,
            RegistrationCodeSeeder::class,
        ]);
    }
}
