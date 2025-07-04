# BM25 Interview - Manual Candidate Shortlisting & Application Status Tracking

## Overview

This document outlines the implementation of manual candidate shortlisting and application status tracking features that integrate with the existing BM25 ranking system. The implementation follows the established architectural patterns, mirroring the bookmark functionality's service-oriented design and UI consistency.

## Current System Analysis

### Existing Architecture
- **BM25 Ranking**: Implemented in `CandidateRankingService` with weighted scoring
- **Application Flow**: Companies view ranked applicants → Click "View Profile" → Navigate to candidate details
- **Current URL Pattern**: `/candidates/{slug}` for candidate profiles
- **Bookmark Pattern**: AJAX-based actions with consistent UI styling
- **Applied Jobs**: Basic table showing Company, Salary, Date, Status (Active/Expired), Action

### Key Files Analyzed
- `resources/views/frontend/company-dashboard/applications/ranked.blade.php` - Ranked applicants view
- `resources/views/frontend/pages/candidate-details.blade.php` - Candidate profile view
- `resources/views/frontend/candidate-dashboard/my-job/index.blade.php` - Applied jobs view
- `app/Models/AppliedJob.php` - Current application model (basic structure)
- `app/Http/Controllers/Frontend/CandidateJobBookmarkController.php` - Bookmark pattern reference

## Implementation Requirements

### 1. Database Schema Changes

#### 1.1 Add Application Status to Applied Jobs Table
```sql
-- Migration: add_application_status_to_applied_jobs_table.php
ALTER TABLE applied_jobs ADD COLUMN application_status ENUM('under_review', 'shortlisted', 'called_for_interview', 'rejected') DEFAULT 'under_review';
ALTER TABLE applied_jobs ADD COLUMN status_updated_at TIMESTAMP NULL;
ALTER TABLE applied_jobs ADD COLUMN status_updated_by BIGINT UNSIGNED NULL;
ALTER TABLE applied_jobs ADD COLUMN notes TEXT NULL;
ALTER TABLE applied_jobs ADD INDEX idx_application_status (application_status);
ALTER TABLE applied_jobs ADD INDEX idx_job_status (job_id, application_status);
```

#### 1.2 Create Application Status History Table
```sql
-- Migration: create_application_status_history_table.php
CREATE TABLE application_status_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    applied_job_id BIGINT UNSIGNED NOT NULL,
    previous_status ENUM('under_review', 'shortlisted', 'called_for_interview', 'rejected') NULL,
    new_status ENUM('under_review', 'shortlisted', 'called_for_interview', 'rejected') NOT NULL,
    changed_by BIGINT UNSIGNED NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (applied_job_id) REFERENCES applied_jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_applied_job (applied_job_id),
    INDEX idx_status_change (new_status, created_at)
);
```

### 2. Model Updates

#### 2.1 Enhanced AppliedJob Model
```php
// app/Models/AppliedJob.php - Add to existing model
protected $fillable = [
    'job_id', 'candidate_id', 'application_status', 
    'status_updated_at', 'status_updated_by', 'notes'
];

protected $casts = [
    'status_updated_at' => 'datetime',
];

// Relationships
public function statusUpdatedBy(): BelongsTo {
    return $this->belongsTo(User::class, 'status_updated_by');
}

public function statusHistory(): HasMany {
    return $this->hasMany(ApplicationStatusHistory::class, 'applied_job_id');
}

// Status constants
const STATUS_UNDER_REVIEW = 'under_review';
const STATUS_SHORTLISTED = 'shortlisted';
const STATUS_CALLED_FOR_INTERVIEW = 'called_for_interview';
const STATUS_REJECTED = 'rejected';

public static function getStatusOptions(): array {
    return [
        self::STATUS_UNDER_REVIEW => 'Under Review',
        self::STATUS_SHORTLISTED => 'Shortlisted',
        self::STATUS_CALLED_FOR_INTERVIEW => 'Called for Interview',
        self::STATUS_REJECTED => 'Rejected',
    ];
}

public function getStatusLabelAttribute(): string {
    return self::getStatusOptions()[$this->application_status] ?? 'Unknown';
}

public function getStatusBadgeClassAttribute(): string {
    return match($this->application_status) {
        self::STATUS_UNDER_REVIEW => 'bg-info',
        self::STATUS_SHORTLISTED => 'bg-success',
        self::STATUS_CALLED_FOR_INTERVIEW => 'bg-warning',
        self::STATUS_REJECTED => 'bg-danger',
        default => 'bg-secondary'
    };
}
```

#### 2.2 New ApplicationStatusHistory Model
```php
// app/Models/ApplicationStatusHistory.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationStatusHistory extends Model
{
    protected $table = 'application_status_history';
    
    protected $fillable = [
        'applied_job_id', 'previous_status', 'new_status', 
        'changed_by', 'notes'
    ];

    public function appliedJob(): BelongsTo {
        return $this->belongsTo(AppliedJob::class, 'applied_job_id');
    }

    public function changedBy(): BelongsTo {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
```

### 3. Service Layer Implementation

#### 3.1 Application Status Service
```php
// app/Services/ApplicationStatusService.php
<?php

namespace App\Services;

use App\Models\AppliedJob;
use App\Models\ApplicationStatusHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ApplicationStatusService
{
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

    public function getApplicationsForJob(int $jobId, ?string $status = null): \Illuminate\Contracts\Pagination\LengthAwarePaginator {
        $query = AppliedJob::with(['candidate.experience', 'candidate.profession'])
                           ->where('job_id', $jobId);
        
        if ($status) {
            $query->where('application_status', $status);
        }
        
        return $query->paginate(20);
    }

    public function getStatusStatistics(int $jobId): array {
        return AppliedJob::where('job_id', $jobId)
                        ->selectRaw('application_status, COUNT(*) as count')
                        ->groupBy('application_status')
                        ->pluck('count', 'application_status')
                        ->toArray();
    }
}
```

### 4. Controller Implementation

#### 4.1 Application Status Controller
```php
// app/Http/Controllers/Frontend/ApplicationStatusController.php
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

    public function updateStatus(Request $request): JsonResponse {
        $request->validate([
            'applied_job_id' => 'required|exists:applied_jobs,id',
            'status' => ['required', Rule::in(array_keys(AppliedJob::getStatusOptions()))],
            'notes' => 'nullable|string|max:1000'
        ]);

        try {
            $this->statusService->updateApplicationStatus(
                $request->applied_job_id,
                $request->status,
                $request->notes
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

    public function getApplicationContext(Request $request): JsonResponse {
        $request->validate([
            'candidate_id' => 'required|exists:candidates,user_id',
            'job_id' => 'required|exists:jobs,id'
        ]);

        $application = AppliedJob::where([
            'candidate_id' => $request->candidate_id,
            'job_id' => $request->job_id
        ])->first();

        if (!$application) {
            return response()->json([
                'success' => false,
                'message' => 'Application not found'
            ], 404);
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
```

### 5. Frontend Implementation

#### 5.1 Enhanced Candidate Profile View
```php
// Update app/Http/Controllers/Frontend/FrontendCandidatePageController.php
public function show(string $slug, Request $request): View {
    $candidate = Candidate::with(['profession', 'experience', 'skills.skill', 'languages', 'experiences', 'educations'])
                        ->where(['profile_complete' => 1, 'visibility' => 1, 'slug' => $slug])
                        ->firstOrFail();

    // Get job context if viewing from company dashboard
    $jobId = $request->get('job_id');
    $application = null;
    $isCompanyView = false;

    if ($jobId && auth()->check() && auth()->user()->role === 'company') {
        $application = AppliedJob::where([
            'candidate_id' => $candidate->user_id,
            'job_id' => $jobId
        ])->with('job')->first();

        // Verify company owns the job
        if ($application && $application->job->company_id === auth()->user()->company->id) {
            $isCompanyView = true;
        }
    }

    return view('frontend.pages.candidate-details', compact('candidate', 'application', 'isCompanyView', 'jobId'));
}
```

#### 5.2 Updated Candidate Details Template
```blade
{{-- Add to resources/views/frontend/pages/candidate-details.blade.php after line 162 --}}
@if($isCompanyView && $application)
<div class="mt-30 border-top pt-30">
    <h6 class="mb-20">Application Management</h6>
    <div class="application-status-section">
        <div class="row">
            <div class="col-md-6">
                <div class="status-info mb-3">
                    <small class="text-muted">Current Status:</small><br>
                    <span class="badge {{ $application->status_badge_class }} status-badge" id="current-status-badge">
                        {{ $application->status_label }}
                    </span>
                </div>
                <div class="application-date mb-3">
                    <small class="text-muted">Applied on:</small><br>
                    <strong>{{ $application->created_at->format('M d, Y') }}</strong>
                </div>
            </div>
            <div class="col-md-6">
                <div class="action-buttons">
                    @if($application->application_status !== 'shortlisted')
                    <button class="btn btn-success btn-sm mb-2 status-action-btn"
                            data-action="shortlist"
                            data-status="shortlisted"
                            data-application-id="{{ $application->id }}">
                        <i class="fas fa-star"></i> Shortlist Candidate
                    </button>
                    @endif

                    @if($application->application_status !== 'called_for_interview')
                    <button class="btn btn-warning btn-sm mb-2 status-action-btn"
                            data-action="interview"
                            data-status="called_for_interview"
                            data-application-id="{{ $application->id }}">
                        <i class="fas fa-phone"></i> Call for Interview
                    </button>
                    @endif

                    @if($application->application_status !== 'rejected')
                    <button class="btn btn-danger btn-sm mb-2 status-action-btn"
                            data-action="reject"
                            data-status="rejected"
                            data-application-id="{{ $application->id }}">
                        <i class="fas fa-times"></i> Reject Application
                    </button>
                    @endif
                </div>
            </div>
        </div>

        @if($application->notes)
        <div class="notes-section mt-3">
            <small class="text-muted">Notes:</small>
            <p class="border p-2 rounded bg-light">{{ $application->notes }}</p>
        </div>
        @endif
    </div>
</div>

{{-- Status Update Modal --}}
<div class="modal fade" id="statusUpdateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Application Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="statusUpdateForm">
                    @csrf
                    <input type="hidden" id="applicationId" name="applied_job_id">
                    <input type="hidden" id="newStatus" name="status">

                    <div class="mb-3">
                        <label class="form-label">Action:</label>
                        <p class="fw-bold" id="actionDescription"></p>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes (Optional):</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"
                                placeholder="Add any notes about this status change..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmStatusUpdate">Confirm</button>
            </div>
        </div>
    </div>
</div>
@endif
```

#### 5.3 JavaScript Implementation
```javascript
{{-- Add to resources/views/frontend/pages/candidate-details.blade.php in @push('scripts') section --}}
@if($isCompanyView && $application)
<script>
$(document).ready(function() {
    // Status action button handlers
    $('.status-action-btn').on('click', function(e) {
        e.preventDefault();

        const action = $(this).data('action');
        const status = $(this).data('status');
        const applicationId = $(this).data('application-id');

        // Set modal data
        $('#applicationId').val(applicationId);
        $('#newStatus').val(status);

        // Set action description
        const actionDescriptions = {
            'shortlist': 'Shortlist this candidate for further consideration',
            'interview': 'Call this candidate for an interview',
            'reject': 'Reject this application'
        };

        $('#actionDescription').text(actionDescriptions[action] || 'Update application status');

        // Show modal
        $('#statusUpdateModal').modal('show');
    });

    // Confirm status update
    $('#confirmStatusUpdate').on('click', function() {
        const formData = {
            applied_job_id: $('#applicationId').val(),
            status: $('#newStatus').val(),
            notes: $('#notes').val(),
            _token: $('meta[name="csrf-token"]').attr('content')
        };

        $.ajax({
            url: '{{ route("company.application.status.update") }}',
            method: 'POST',
            data: formData,
            beforeSend: function() {
                $('#confirmStatusUpdate').prop('disabled', true).text('Updating...');
            },
            success: function(response) {
                if (response.success) {
                    // Update status badge
                    const badgeClasses = {
                        'under_review': 'bg-info',
                        'shortlisted': 'bg-success',
                        'called_for_interview': 'bg-warning',
                        'rejected': 'bg-danger'
                    };

                    $('#current-status-badge')
                        .removeClass('bg-info bg-success bg-warning bg-danger')
                        .addClass(badgeClasses[response.status])
                        .text(response.status_label);

                    // Hide/show appropriate buttons
                    $('.status-action-btn').show();
                    $(`.status-action-btn[data-status="${response.status}"]`).hide();

                    // Close modal and show success message
                    $('#statusUpdateModal').modal('hide');
                    notyf.success(response.message);

                    // Reset form
                    $('#statusUpdateForm')[0].reset();
                } else {
                    notyf.error(response.message);
                }
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors || {};
                Object.values(errors).forEach(errorArray => {
                    errorArray.forEach(error => notyf.error(error));
                });
            },
            complete: function() {
                $('#confirmStatusUpdate').prop('disabled', false).text('Confirm');
            }
        });
    });
});
</script>
@endif
```

### 6. Enhanced Applied Jobs View for Candidates

#### 6.1 Updated Applied Jobs Template
```blade
{{-- Update resources/views/frontend/candidate-dashboard/my-job/index.blade.php table headers --}}
<thead>
    <tr>
        <th>Company</th>
        <th>Salary</th>
        <th>Date</th>
        <th>Job Status</th>
        <th>Application Status</th>
        <th style="width: 15%">Action</th>
    </tr>
</thead>

{{-- Update table body to include application status --}}
<tbody class="experience-tbody">
    @forelse ($appliedJobs as $appliedJob)
        <tr>
            <td>
                <div class="d-flex ">
                    <img style="width: 50px; height: 50px; object-fit:cover;"
                        src="{{ asset($appliedJob->job->company->logo) }}" alt="">
                    <div style="padding-left: 15px">
                        <h6>{{ $appliedJob->job->company->name }}</h6>
                        <b>{{ $appliedJob->job?->company?->companyCountry->name }}</b>
                    </div>
                </div>
            </td>
            <td>
                @if ($appliedJob->job->salary_mode === 'range')
                    {{ $appliedJob->job->min_salary }} - {{ $appliedJob->job->max_salary }}
                    {{ config('settings.site_default_currency') }}
                @else
                    {{ $appliedJob->job->custom_salary }}
                @endif
            </td>
            <td>{{ formatDate($appliedJob->created_at) }}</td>
            <td>
                @if($appliedJob->job->deadline < date('Y-m-d'))
                    <span class="badge bg-danger">Expired</span>
                @else
                    <span class="badge bg-success">Active</span>
                @endif
            </td>
            <td>
                <span class="badge {{ $appliedJob->status_badge_class }}">
                    {{ $appliedJob->status_label }}
                </span>
                @if($appliedJob->status_updated_at)
                    <br><small class="text-muted">
                        Updated {{ $appliedJob->status_updated_at->diffForHumans() }}
                    </small>
                @endif
            </td>
            <td>
                @if($appliedJob->job->deadline < date('Y-m-d'))
                    <a href="javascript:;" class="btn-sm btn btn-secondary">
                        <i class="fas fa-eye" aria-hidden="true"></i>
                    </a>
                @else
                    <a href="{{ route('jobs.show', $appliedJob->job->slug) }}" class="btn-sm btn btn-primary">
                        <i class="fas fa-eye" aria-hidden="true"></i>
                    </a>
                @endif
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="6" class="text-center">No data found!</td>
        </tr>
    @endforelse
</tbody>
```

### 7. Route Definitions

#### 7.1 Add to routes/web.php (Company Routes Section)
```php
// Add within the company route group (around line 144)
Route::post('application/status/update', [ApplicationStatusController::class, 'updateStatus'])
     ->name('application.status.update');
Route::get('application/context', [ApplicationStatusController::class, 'getApplicationContext'])
     ->name('application.context');
```

#### 7.2 Update Candidate Profile Route
```php
// Update the existing candidate show route to accept job_id parameter
Route::get('candidates/{slug}', [FrontendCandidatePageController::class, 'show'])
     ->name('candidates.show');
```

### 8. Enhanced Ranked Applications View

#### 8.1 Update View Profile Links
```blade
{{-- Update resources/views/frontend/company-dashboard/applications/ranked.blade.php line 115-118 --}}
<td class="text-center">
    <a href="{{ route('candidates.show', ['slug' => $application->candidate->slug, 'job_id' => $job->id]) }}"
       class="btn btn-primary btn-sm">
        <i class="fas fa-eye"></i> View Profile
    </a>
</td>
```

#### 8.2 Add Status Filter to Ranked View
```blade
{{-- Add after line 41 in ranked.blade.php --}}
<div class="card-body border-bottom">
    <div class="row align-items-center">
        <div class="col-md-8">
            <div class="btn-group" role="group">
                <a href="{{ route('company.job.applications.rank', $job->id) }}"
                   class="btn btn-outline-primary {{ !request('status') ? 'active' : '' }}">
                    All Applications
                </a>
                <a href="{{ route('company.job.applications.rank', ['id' => $job->id, 'status' => 'shortlisted']) }}"
                   class="btn btn-outline-success {{ request('status') === 'shortlisted' ? 'active' : '' }}">
                    Shortlisted
                </a>
                <a href="{{ route('company.job.applications.rank', ['id' => $job->id, 'status' => 'called_for_interview']) }}"
                   class="btn btn-outline-warning {{ request('status') === 'called_for_interview' ? 'active' : '' }}">
                    Interview
                </a>
                <a href="{{ route('company.job.applications.rank', ['id' => $job->id, 'status' => 'rejected']) }}"
                   class="btn btn-outline-danger {{ request('status') === 'rejected' ? 'active' : '' }}">
                    Rejected
                </a>
            </div>
        </div>
        <div class="col-md-4 text-end">
            @if($statusStats = app(App\Services\ApplicationStatusService::class)->getStatusStatistics($job->id))
                <small class="text-muted">
                    Shortlisted: {{ $statusStats['shortlisted'] ?? 0 }} |
                    Interview: {{ $statusStats['called_for_interview'] ?? 0 }} |
                    Rejected: {{ $statusStats['rejected'] ?? 0 }}
                </small>
            @endif
        </div>
    </div>
</div>
```

### 9. Security Considerations

#### 9.1 Authorization Middleware
```php
// app/Http/Middleware/EnsureCompanyOwnsJob.php
<?php

namespace App\Http\Middleware;

use App\Models\Job;
use Closure;
use Illuminate\Http\Request;

class EnsureCompanyOwnsJob
{
    public function handle(Request $request, Closure $next)
    {
        $jobId = $request->route('id') ?? $request->input('job_id');

        if ($jobId) {
            $job = Job::find($jobId);
            if (!$job || $job->company_id !== auth()->user()->company->id) {
                abort(403, 'Unauthorized access to job applications');
            }
        }

        return $next($request);
    }
}
```

#### 9.2 Request Validation
```php
// app/Http/Requests/Frontend/UpdateApplicationStatusRequest.php
<?php

namespace App\Http\Requests\Frontend;

use App\Models\AppliedJob;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApplicationStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $appliedJob = AppliedJob::find($this->applied_job_id);
        return $appliedJob &&
               $appliedJob->job->company_id === auth()->user()->company->id;
    }

    public function rules(): array
    {
        return [
            'applied_job_id' => 'required|exists:applied_jobs,id',
            'status' => ['required', Rule::in(array_keys(AppliedJob::getStatusOptions()))],
            'notes' => 'nullable|string|max:1000'
        ];
    }
}
```

### 10. Testing Approach

#### 10.1 Feature Tests
```php
// tests/Feature/ApplicationStatusTest.php
<?php

namespace Tests\Feature;

use App\Models\AppliedJob;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_can_update_application_status()
    {
        // Setup test data
        $company = Company::factory()->create();
        $companyUser = User::factory()->create(['role' => 'company']);
        $company->user_id = $companyUser->id;
        $company->save();

        $job = Job::factory()->create(['company_id' => $company->id]);
        $candidate = Candidate::factory()->create();
        $application = AppliedJob::factory()->create([
            'job_id' => $job->id,
            'candidate_id' => $candidate->user_id
        ]);

        // Test status update
        $response = $this->actingAs($companyUser)
                         ->postJson(route('company.application.status.update'), [
                             'applied_job_id' => $application->id,
                             'status' => 'shortlisted',
                             'notes' => 'Great candidate'
                         ]);

        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('applied_jobs', [
            'id' => $application->id,
            'application_status' => 'shortlisted'
        ]);
    }

    public function test_unauthorized_company_cannot_update_application_status()
    {
        // Test unauthorized access
        $otherCompany = Company::factory()->create();
        $otherUser = User::factory()->create(['role' => 'company']);
        $otherCompany->user_id = $otherUser->id;
        $otherCompany->save();

        $company = Company::factory()->create();
        $job = Job::factory()->create(['company_id' => $company->id]);
        $application = AppliedJob::factory()->create(['job_id' => $job->id]);

        $response = $this->actingAs($otherUser)
                         ->postJson(route('company.application.status.update'), [
                             'applied_job_id' => $application->id,
                             'status' => 'shortlisted'
                         ]);

        $response->assertStatus(400);
    }
}
```

### 11. Database Seeders Update

#### 11.1 Update Applied Jobs Seeder
```php
// Update database/seeders/NepalDemoApplicationSeeder.php
private function createApplication($job, $candidate)
{
    $existingApplication = AppliedJob::where([
        'job_id' => $job->id,
        'candidate_id' => $candidate->user_id
    ])->first();

    if ($existingApplication) {
        return;
    }

    $applicationDate = $this->getApplicationDate($job);

    // Randomly assign application status for demo purposes
    $statuses = ['under_review', 'shortlisted', 'called_for_interview', 'rejected'];
    $weights = [60, 25, 10, 5]; // Weighted probability
    $status = $this->getWeightedRandomStatus($statuses, $weights);

    AppliedJob::create([
        'job_id' => $job->id,
        'candidate_id' => $candidate->user_id,
        'application_status' => $status,
        'status_updated_at' => $status !== 'under_review' ? $applicationDate->addDays(rand(1, 7)) : null,
        'created_at' => $applicationDate,
        'updated_at' => $applicationDate,
    ]);
}

private function getWeightedRandomStatus(array $statuses, array $weights): string
{
    $totalWeight = array_sum($weights);
    $random = rand(1, $totalWeight);

    $currentWeight = 0;
    foreach ($statuses as $index => $status) {
        $currentWeight += $weights[$index];
        if ($random <= $currentWeight) {
            return $status;
        }
    }

    return $statuses[0]; // Fallback
}
```

## Implementation Summary

This implementation provides:

1. **Complete Application Status Tracking**: Four status levels with history tracking
2. **Company Profile Integration**: Shortlisting controls directly in candidate profiles
3. **Enhanced Applied Jobs View**: Real-time status visibility for candidates
4. **Service-Oriented Architecture**: Following existing patterns with proper separation of concerns
5. **Security**: Authorization middleware and request validation
6. **UI Consistency**: Matching existing bookmark functionality patterns
7. **AJAX Integration**: Seamless status updates without page refresh
8. **Backward Compatibility**: All existing functionality remains intact

The implementation follows the established BM25 architectural patterns and maintains consistency with the existing codebase while adding powerful new functionality for manual candidate management.
```
