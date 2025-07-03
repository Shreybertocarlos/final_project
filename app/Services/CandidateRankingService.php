<?php

namespace App\Services;

use App\Models\AppliedJob;
use App\Models\Job;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CandidateRankingService
{
    protected $candidateBM25Service;

    public function __construct(CandidateBM25Service $candidateBM25Service)
    {
        $this->candidateBM25Service = $candidateBM25Service;
    }

    /**
     * Main method to rank applicants for a specific job
     * 
     * @param int $jobId Job ID to rank applicants for
     * @return \Illuminate\Pagination\LengthAwarePaginator Paginated ranked applicants
     */
    public function rankApplicantsForJob($jobId)
    {
        try {
            // Get all applicants for this job
            $applications = AppliedJob::with([
                'candidate.experience', 
                'candidate.profession',
                'candidate.skills.skill',
                'candidate.experiences',
                'candidate.educations'
            ])
            ->where('job_id', $jobId)
            ->get();

            if ($applications->isEmpty()) {
                Log::info("No applications found for job: {$jobId}");
                return $this->createEmptyPaginator();
            }

            // Get BM25 scores for candidates
            $bm25Results = $this->candidateBM25Service->rankCandidatesForJob($jobId);

            if (empty($bm25Results)) {
                Log::info("No BM25 results found for job: {$jobId}, falling back to chronological order");
                return $this->createChronologicalPaginator($applications);
            }

            // Combine BM25 results with application data
            return $this->combineWithApplicationData($bm25Results, $applications);

        } catch (\Exception $e) {
            Log::error('CandidateRankingService rankApplicantsForJob error: ' . $e->getMessage());
            return $this->createEmptyPaginator();
        }
    }

    /**
     * Combine BM25 scores with application data and create paginated results
     * 
     * @param array $bm25Results Array of candidate_id => score
     * @param \Illuminate\Database\Eloquent\Collection $applications Collection of applications
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    protected function combineWithApplicationData($bm25Results, $applications)
    {
        // Add BM25 scores to applications and sort by score
        $applicationsWithScores = $applications->map(function ($application) use ($bm25Results) {
            $candidateId = $application->candidate->id;
            $application->bm25_score = $bm25Results[$candidateId] ?? 0;
            $application->rank_position = 0; // Will be set after sorting
            return $application;
        })->sortByDesc('bm25_score');

        // Add rank positions
        $rankedApplications = $applicationsWithScores->values()->map(function ($application, $index) {
            $application->rank_position = $index + 1;
            return $application;
        });

        // Create manual pagination
        $currentPage = request()->get('page', 1);
        $perPage = 10; // Show 10 candidates per page
        $total = $rankedApplications->count();
        $offset = ($currentPage - 1) * $perPage;
        $items = $rankedApplications->slice($offset, $perPage)->values();

        // Create paginator
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $currentPage,
            [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );

        return $paginator;
    }

    /**
     * Create chronological paginator as fallback
     * 
     * @param \Illuminate\Database\Eloquent\Collection $applications
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    protected function createChronologicalPaginator($applications)
    {
        // Sort by application date (most recent first)
        $sortedApplications = $applications->sortByDesc('created_at')->values();

        // Add default scores and ranks
        $applicationsWithDefaults = $sortedApplications->map(function ($application, $index) {
            $application->bm25_score = 0;
            $application->rank_position = $index + 1;
            return $application;
        });

        // Create manual pagination
        $currentPage = request()->get('page', 1);
        $perPage = 10;
        $total = $applicationsWithDefaults->count();
        $offset = ($currentPage - 1) * $perPage;
        $items = $applicationsWithDefaults->slice($offset, $perPage)->values();

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $currentPage,
            [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );

        return $paginator;
    }

    /**
     * Create empty paginator for no results
     * 
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    protected function createEmptyPaginator()
    {
        return new \Illuminate\Pagination\LengthAwarePaginator(
            collect([]),
            0,
            10,
            1,
            [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );
    }

    /**
     * Get matching skills between candidate and job
     * 
     * @param object $candidate Candidate object with skills loaded
     * @param int $jobId Job ID
     * @return array Array of matching skill names
     */
    public function getMatchingSkills($candidate, $jobId)
    {
        try {
            // Get job skills
            $jobSkills = DB::table('job_skills')
                           ->join('skills', 'job_skills.skill_id', '=', 'skills.id')
                           ->where('job_skills.job_id', $jobId)
                           ->pluck('skills.name')
                           ->toArray();

            // Get candidate skills
            $candidateSkills = $candidate->skills->pluck('skill.name')->toArray();

            // Find intersection
            return array_intersect($candidateSkills, $jobSkills);

        } catch (\Exception $e) {
            Log::error('Error getting matching skills: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Calculate skill match percentage
     * 
     * @param object $candidate Candidate object with skills loaded
     * @param int $jobId Job ID
     * @return float Percentage of job skills that candidate has
     */
    public function calculateSkillMatchPercentage($candidate, $jobId)
    {
        try {
            // Get job skills count
            $jobSkillsCount = DB::table('job_skills')
                                ->where('job_id', $jobId)
                                ->count();

            if ($jobSkillsCount === 0) {
                return 0;
            }

            // Get matching skills count
            $matchingSkills = $this->getMatchingSkills($candidate, $jobId);
            $matchingCount = count($matchingSkills);

            return round(($matchingCount / $jobSkillsCount) * 100, 1);

        } catch (\Exception $e) {
            Log::error('Error calculating skill match percentage: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get ranking statistics for a job
     * 
     * @param int $jobId Job ID
     * @return array Statistics about the ranking
     */
    public function getRankingStatistics($jobId)
    {
        try {
            $applicationsCount = AppliedJob::where('job_id', $jobId)->count();
            $indexedCandidatesCount = DB::table('candidate_search_index')
                                        ->distinct('candidate_id')
                                        ->count('candidate_id');
            
            $bm25Results = $this->candidateBM25Service->rankCandidatesForJob($jobId);
            $rankedCount = count($bm25Results);

            return [
                'total_applications' => $applicationsCount,
                'indexed_candidates' => $indexedCandidatesCount,
                'ranked_candidates' => $rankedCount,
                'ranking_coverage' => $applicationsCount > 0 ? round(($rankedCount / $applicationsCount) * 100, 1) : 0
            ];

        } catch (\Exception $e) {
            Log::error('Error getting ranking statistics: ' . $e->getMessage());
            return [
                'total_applications' => 0,
                'indexed_candidates' => 0,
                'ranked_candidates' => 0,
                'ranking_coverage' => 0
            ];
        }
    }
}
