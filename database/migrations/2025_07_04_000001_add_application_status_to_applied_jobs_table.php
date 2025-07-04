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
        Schema::table('applied_jobs', function (Blueprint $table) {
            $table->enum('application_status', ['under_review', 'shortlisted', 'called_for_interview', 'rejected'])
                  ->default('under_review')
                  ->after('candidate_id');
            $table->timestamp('status_updated_at')->nullable()->after('application_status');
            $table->foreignId('status_updated_by')->nullable()->after('status_updated_at');
            $table->text('notes')->nullable()->after('status_updated_by');
            
            // Add indexes for performance
            $table->index('application_status', 'idx_application_status');
            $table->index(['job_id', 'application_status'], 'idx_job_status');
            
            // Foreign key constraint
            $table->foreign('status_updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applied_jobs', function (Blueprint $table) {
            $table->dropForeign(['status_updated_by']);
            $table->dropIndex('idx_application_status');
            $table->dropIndex('idx_job_status');
            $table->dropColumn(['application_status', 'status_updated_at', 'status_updated_by', 'notes']);
        });
    }
};
