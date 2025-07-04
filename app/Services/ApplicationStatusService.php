<?php

namespace App\Services;

use App\Models\AppliedJob;
use App\Models\ApplicationStatusHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ApplicationStatusService
{
    /**
     * Update application status with history tracking
     */
    public function updateApplicationStatus(
        int $appliedJobId, 
        string $newStatus, 
        ?string $notes = null
    ): bool {
        return DB::transaction(function () use ($appliedJobId, $newStatus, $notes) {
            $appliedJob = AppliedJob::findOrFail($appliedJobId);
            
            // Verify company owns this job
            if ($appliedJob->job->company_id !== Auth::user()->company->id) {
                throw new \Exception('Unauthorized access to application');
            }
            
            $previousStatus = $appliedJob->application_status;
            
            // Update application status
            $appliedJob->update([
                'application_status' => $newStatus,
                'status_updated_at' => now(),
                'status_updated_by' => Auth::id(),
                'notes' => $notes
            ]);
            
            // Record status history
            ApplicationStatusHistory::create([
                'applied_job_id' => $appliedJobId,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'changed_by' => Auth::id(),
                'notes' => $notes
            ]);
            
            return true;
        });
    }

    /**
     * Get applications for a job with optional status filter
     */
    public function getApplicationsForJob(int $jobId, ?string $status = null): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = AppliedJob::with(['candidate.experience', 'candidate.profession'])
                           ->where('job_id', $jobId);
        
        if ($status) {
            $query->where('application_status', $status);
        }
        
        return $query->paginate(20);
    }

    /**
     * Get status statistics for a job
     */
    public function getStatusStatistics(int $jobId): array
    {
        return AppliedJob::where('job_id', $jobId)
                        ->selectRaw('application_status, COUNT(*) as count')
                        ->groupBy('application_status')
                        ->pluck('count', 'application_status')
                        ->toArray();
    }

    /**
     * Get application by candidate and job
     */
    public function getApplicationByContext(int $candidateId, int $jobId): ?AppliedJob
    {
        return AppliedJob::where([
            'candidate_id' => $candidateId,
            'job_id' => $jobId
        ])->with('job')->first();
    }
}
