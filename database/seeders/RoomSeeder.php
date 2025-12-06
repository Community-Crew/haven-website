<?php

namespace Database\Seeders;

use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = database_path("seeders/data/rooms.json");
        $json = file_exists($path) ? file_get_contents($path) : [];
        $rows = json_decode($json, true) ?: [];

        foreach ($rows as $row) {
            Room::create($row);
        }
    }
}
