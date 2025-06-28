<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\AppliedJob;
use App\Models\City;
use App\Models\Country;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\JobType;
use App\Models\State;
use App\Services\JobSearchService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FrontendJobPageController extends Controller
{
    protected $jobSearchService;

    public function __construct(JobSearchService $jobSearchService)
    {
        $this->jobSearchService = $jobSearchService;
    }

    function index(Request $request) : View {

        $countries = Country::all();
        $jobCategories = JobCategory::withCount(['jobs' => function($query) {
            $query->where('status', 'active')->where('deadline', '>=', date('Y-m-d'));
        }])->get();
        $jobTypes = JobType::all();
        $selectedStates = null;
        $selectedCites = null;

        // Prepare filters array for enhanced search
        $filters = [
            'country' => $request->country,
            'state' => $request->state,
            'city' => $request->city,
            'category' => $request->category,
            'min_salary' => $request->min_salary,
            'jobtype' => $request->jobtype,
        ];

        // Use enhanced search service (BM25 + traditional)
        $jobs = $this->jobSearchService->search($request->search, $filters);

        // Handle state and city loading for dropdowns (existing logic)
        if($request->has('country') && $request->filled('country')) {
            $selectedStates = State::where('country_id', $request->country)->get();
        }
        if($request->has('state') && $request->filled('state')) {
            $selectedCites = City::where('state_id', $request->state)->get();
        }



        return view('frontend.pages.jobs-index', compact('jobs', 'countries', 'jobCategories', 'jobTypes', 'selectedStates', 'selectedCites'));
    }

    function show(string $slug) : View {
        $job = Job::where('slug', $slug)->firstOrFail();
        $openJobs = Job::where('company_id', $job->company->id)->where('status', 'active')->where('deadline', '>=', date('Y-m-d'))->count();
        $alreadyApplied = AppliedJob::where(['job_id' => $job->id, 'candidate_id' => auth()->user()?->id])->exists();
        return view('frontend.pages.job-show', compact('job', 'openJobs', 'alreadyApplied'));
    }

    function applyJob(string $id) {
        if(!auth()->check()) {
            throw ValidationException::withMessages(['Please login for apply to the job.']);
        }
        $alreadyApplied = AppliedJob::where(['job_id' => $id, 'candidate_id' => auth()->user()?->id])->exists();
        if($alreadyApplied) {
            throw ValidationException::withMessages(['You already applied to this job.']);
        }

        $applyJob = new AppliedJob();
        $applyJob->job_id = $id;
        $applyJob->candidate_id = auth()->user()->id;
        $applyJob->save();

        return response(['message' => 'Applied Successfully!'], 200);
    }
}
