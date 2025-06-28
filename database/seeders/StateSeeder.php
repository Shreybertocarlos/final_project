<?php

namespace Database\Seeders;

use App\Models\State;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $states = array(
            array(
                "id" => 1,
                "name" => "Koshi Province",
                "country_id" => 1,
                "created_at" => "2025-04-20 09:11:03",
                "updated_at" => "2025-04-21 21:23:19",
            ),
            array(
                "id" => 2,
                "name" => "Madhesh Province",
                "country_id" => 1,
                "created_at" => "2025-04-20 09:11:03",
                "updated_at" => "2025-04-21 21:23:19",
            ),
            array(
                "id" => 3,
                "name" => "Bagmati Province",
                "country_id" => 1,
                "created_at" => "2025-04-20 09:11:03",
                "updated_at" => "2025-04-21 21:23:19",
            ),
            array(
                "id" => 4,
                "name" => "Gandaki Province",
                "country_id" => 1,
                "created_at" => "2025-04-20 09:11:03",
                "updated_at" => "2025-04-21 21:23:19",
            ),
            array(
                "id" => 5,
                "name" => "Lumbini Province",
                "country_id" => 1,
                "created_at" => "2025-04-20 09:11:03",
                "updated_at" => "2025-04-21 21:23:19",
            ),
            array(
                "id" => 6,
                "name" => "Karnali Province",
                "country_id" => 1,
                "created_at" => "2025-04-20 09:11:03",
                "updated_at" => "2025-04-21 21:23:19",
            ),
            array(
                "id" => 7,
                "name" => "Sudurpashchim Province",
                "country_id" => 1,
                "created_at" => "2025-04-20 09:11:03",
                "updated_at" => "2025-04-21 21:23:19",
            ),
        );

        State::insert($states);

    }
}
