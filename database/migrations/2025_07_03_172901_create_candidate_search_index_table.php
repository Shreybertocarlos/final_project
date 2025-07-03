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
        Schema::create('candidate_search_index', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('candidate_id');
            $table->string('term', 255);
            $table->integer('term_freq');
            $table->integer('doc_length');
            $table->integer('doc_freq');
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('candidate_id')->references('id')->on('candidates')->onDelete('cascade');

            // Indexes for performance
            $table->index('term');
            $table->index('candidate_id');
            $table->index(['term', 'candidate_id']);
            $table->index(['term', 'doc_freq']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidate_search_index');
    }
};
