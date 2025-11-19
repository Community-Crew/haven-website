<?php

namespace Database\Seeders;


use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use function Laravel\Prompts\table;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = database_path("seeders/data/Units.json");
        $json = file_exists($path) ? file_get_contents($path) : [];
        $rows = json_decode($json, true) ?: [];

        if(!empty($rows)){
            DB::table('Units')->insert($rows);
        }
    }
}
