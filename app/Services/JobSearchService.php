<?php

namespace App\Services;

use App\Models\Job;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class JobSearchService
{
    protected $bm25Service;

    public function __construct(BM25Service $bm25Service)
    {
        $this->bm25Service = $bm25Service;
    }

    /**
     * Main search method that orchestrates between BM25 and traditional search
     */
    public function search($query, array $filters = [])
    {
        // If no search query, use traditional filtering only
        if (empty($query)) {
            return $this->getFilteredJobs($filters);
        }

        // Tokenize the query
        $queryTerms = $this->tokenize($query);

        if (empty($queryTerms)) {
            return $this->getFilteredJobs($filters);
        }

        // Get BM25 statistics
        $totalDocs = Job::where('status', 'active')
                        ->where('deadline', '>=', date('Y-m-d'))
                        ->count();

        $avgDocLength = DB::table('job_search_index')->avg('doc_length') ?: 0;

        if ($totalDocs === 0 || $avgDocLength === 0) {
            Log::info('BM25 fallback: No indexed documents or zero average length');
            return $this->getFilteredJobs($filters);
        }

        // Get BM25 scores
        $bm25Results = $this->bm25Service->search($queryTerms, $totalDocs, $avgDocLength);

        if (empty($bm25Results)) {
            Log::info('BM25 fallback: No BM25 results found');
            return $this->getFilteredJobs($filters);
        }

        // Combine BM25 results with filters
        return $this->combineResults($bm25Results, $filters);
    }

    /**
     * Tokenize search query into terms
     */
    protected function tokenize($query)
    {
        // Convert to lowercase and remove special characters
        $query = strtolower(preg_replace('/[^\w\s]/', ' ', $query));

        // Split into terms
        $terms = array_filter(explode(' ', $query), function($term) {
            return strlen(trim($term)) >= 2;
        });

        // Remove common stop words
        $stopWords = ['the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by'];
        $terms = array_diff($terms, $stopWords);

        return array_values($terms);
    }

    /**
     * Get jobs using traditional filtering (fallback method)
     */
    protected function getFilteredJobs(array $filters)
    {
        $query = Job::query();
        $query->where(['status' => 'active'])
              ->where('deadline', '>=', date('Y-m-d'));

        $this->applyFilters($query, $filters);

        return $query->orderBy('id', 'DESC')->paginate(8);
    }

    /**
     * Combine BM25 results with traditional filters
     */
    protected function combineResults($bm25Results, array $filters)
    {
        $jobIds = array_keys($bm25Results);

        $query = Job::query();
        $query->whereIn('id', $jobIds)
              ->where(['status' => 'active'])
              ->where('deadline', '>=', date('Y-m-d'));

        $this->applyFilters($query, $filters);

        // Get all matching jobs first, then sort by BM25 score
        $allJobs = $query->get();

        // Add BM25 scores and sort
        $jobsWithScores = $allJobs->map(function ($job) use ($bm25Results) {
            $job->bm25_score = $bm25Results[$job->id] ?? 0;
            return $job;
        })->sortByDesc('bm25_score');

        // Manually paginate the sorted results
        $currentPage = request()->get('page', 1);
        $perPage = 8; // Match the original pagination
        $total = $jobsWithScores->count();
        $offset = ($currentPage - 1) * $perPage;
        $items = $jobsWithScores->slice($offset, $perPage)->values();

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
     * Apply traditional filters to query
     */
    protected function applyFilters($query, array $filters)
    {
        if (!empty($filters['country'])) {
            $query->where('country_id', $filters['country']);
        }

        if (!empty($filters['state'])) {
            $query->where('state_id', $filters['state']);
        }

        if (!empty($filters['city'])) {
            $query->where('city_id', $filters['city']);
        }

        if (!empty($filters['category'])) {
            if (is_numeric($filters['category'])) {
                $query->where('job_category_id', $filters['category']);
            } else {
                // Handle category slug
                $query->whereHas('category', function($q) use ($filters) {
                    $q->where('slug', $filters['category']);
                });
            }
        }

        if (!empty($filters['jobtype'])) {
            if (is_array($filters['jobtype'])) {
                $typeIds = \App\Models\JobType::whereIn('slug', $filters['jobtype'])->pluck('id')->toArray();
                $query->whereIn('job_type_id', $typeIds);
            } else {
                $jobType = \App\Models\JobType::where('slug', $filters['jobtype'])->first();
                if ($jobType) {
                    $query->where('job_type_id', $jobType->id);
                }
            }
        }

        if (!empty($filters['experience'])) {
            $query->where('job_experience_id', $filters['experience']);
        }

        if (!empty($filters['min_salary']) && $filters['min_salary'] > 0) {
            $query->where(function($q) use ($filters) {
                $q->where('min_salary', '>=', $filters['min_salary'])
                  ->orWhere('max_salary', '>=', $filters['min_salary']);
            });
        }

        if (!empty($filters['max_salary'])) {
            $query->where('max_salary', '<=', $filters['max_salary']);
        }
    }
}
