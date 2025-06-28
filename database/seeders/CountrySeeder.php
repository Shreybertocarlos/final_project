<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $countries = array(

            array(
                "id" => 1,
                "name" => "Nepal",
                "created_at" => "2025-04-20 09:11:03",
                "updated_at" => "2025-04-21 21:23:19",
            ),
        );

        Country::insert($countries);

    }
}
