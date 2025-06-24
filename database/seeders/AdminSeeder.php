<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('admins')->truncate();
        Schema::enableForeignKeyConstraints();

        $admin = new Admin();
        $admin->name ='ShreyAdmin';
        $admin->email='shreyadmin@gmail.com';
        $admin->password=bcrypt('password');
        $admin->save();

        // assign role
        $admin->assignRole('Super Admin');
    }
}
