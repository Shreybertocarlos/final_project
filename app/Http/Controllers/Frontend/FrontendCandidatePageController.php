<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Experience;
use App\Models\Skill;
use Illuminate\Http\Request;
use Illuminate\View\View;

use function Ramsey\Uuid\v1;

class FrontendCandidatePageController extends Controller
{
    function index(Request $request) : View {

        $skills = Skill::all();
        $experiences = Experience::all();
        $query = Candidate::with(['profession', 'experience']);
        $query->where(['profile_complete' => 1, 'visibility' => 1]);

        if($request->has('skills') && $request->filled('skills')) {
            $ids = Skill::whereIn('slug', $request->skills)->pluck('id')->toArray();
            $query->whereHas('skills', function($subquery) use ($ids) {
                $subquery->whereIn('skill_id', $ids);
            });
        }
        if($request->has('experience') && $request->filled('experience')) {
           $query->where('experience_id', $request->experience);
        }

        $candidates = $query->paginate(24);


        return view('frontend.pages.candidate-index', compact('candidates', 'skills', 'experiences'));


    }

    function show(string $slug, Request $request) : View {
        $candidate = Candidate::with(['profession', 'experience', 'skills.skill', 'languages', 'experiences', 'educations'])
                            ->where(['profile_complete' => 1, 'visibility' => 1, 'slug' => $slug])
                            ->firstOrFail();

        // Get job context if viewing from company dashboard
        $jobId = $request->get('job_id');
        $application = null;
        $isCompanyView = false;

        if ($jobId && auth()->check() && auth()->user()->role === 'company') {
            $application = \App\Models\AppliedJob::where([
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
}
