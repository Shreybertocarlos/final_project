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
        Schema::create('application_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applied_job_id')->constrained('applied_jobs')->onDelete('cascade');
            $table->enum('previous_status', ['under_review', 'shortlisted', 'called_for_interview', 'rejected'])->nullable();
            $table->enum('new_status', ['under_review', 'shortlisted', 'called_for_interview', 'rejected']);
            $table->foreignId('changed_by')->constrained('users')->onDelete('cascade');
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
            
            // Indexes for performance
            $table->index('applied_job_id', 'idx_applied_job');
            $table->index(['new_status', 'created_at'], 'idx_status_change');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_status_history');
    }
};
