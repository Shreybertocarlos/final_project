<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\AppliedJob;
use App\Services\ApplicationStatusService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class ApplicationStatusController extends Controller
{
    public function __construct(
        private ApplicationStatusService $statusService
    ) {}

    /**
     * Update application status via AJAX
     */
    public function updateStatus(Request $request): JsonResponse
    {
        $request->validate([
            'applied_job_id' => 'required|exists:applied_jobs,id',
            'status' => ['required', Rule::in(array_keys(AppliedJob::getStatusOptions()))],
            'notes' => 'nullable|string|max:1000'
        ]);

        try {
            $this->statusService->updateApplicationStatus(
                $request->applied_job_id,
                $request->status,
                null // No notes needed
            );

            return response()->json([
                'success' => true,
                'message' => 'Application status updated successfully',
                'status' => $request->status,
                'status_label' => AppliedJob::getStatusOptions()[$request->status]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get application context for candidate profile view
     */
    public function getApplicationContext(Request $request): JsonResponse
    {
        $request->validate([
            'candidate_id' => 'required|exists:candidates,user_id',
            'job_id' => 'required|exists:jobs,id'
        ]);

        $application = $this->statusService->getApplicationByContext(
            $request->candidate_id,
            $request->job_id
        );

        if (!$application) {
            return response()->json([
                'success' => false,
                'message' => 'Application not found'
            ], 404);
        }

        // Verify company owns the job
        if ($application->job->company_id !== auth()->user()->company->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'application' => [
                'id' => $application->id,
                'status' => $application->application_status,
                'status_label' => $application->status_label,
                'notes' => $application->notes,
                'applied_date' => $application->created_at->format('M d, Y')
            ]
        ]);
    }
}
