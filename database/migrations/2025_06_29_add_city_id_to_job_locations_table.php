<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('job_locations', function (Blueprint $table) {
            // Remove country_id and add city_id
            $table->dropColumn('country_id');
            $table->foreignId('city_id')->after('image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_locations', function (Blueprint $table) {
            // Restore country_id and remove city_id
            $table->dropColumn('city_id');
            $table->foreignId('country_id')->after('image');
        });
    }
};
