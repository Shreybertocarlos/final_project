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
        Schema::create('job_search_index', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_id');
            $table->string('term', 255);
            $table->integer('term_freq');
            $table->integer('doc_length');
            $table->integer('doc_freq');
            $table->timestamps();
            
            // Foreign key constraint
            $table->foreign('job_id')->references('id')->on('jobs')->onDelete('cascade');
            
            // Indexes for performance
            $table->index('term');
            $table->index('job_id');
            $table->index(['term', 'job_id']);
            $table->index(['term', 'doc_freq']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_search_index');
    }
};
