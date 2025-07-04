<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CandidateBM25Service
{
    private $k1 = 1.2;
    private $b = 0.75;

    /**
     * Search candidates using BM25 algorithm
     * 
     * @param array $queryTerms Terms extracted from job requirements
     * @param int $totalDocs Total number of indexed candidates
     * @param float $avgDocLength Average document length across all candidates
     * @return array Array of candidate_id => bm25_score
     */
    public function search(array $queryTerms, $totalDocs, $avgDocLength)
    {
        try {
            $results = [];

            foreach ($queryTerms as $term) {
                // Get all candidates that have this term in their profile
                $indexedTerms = DB::table('candidate_search_index')->where('term', $term)->get();

                foreach ($indexedTerms as $indexedTerm) {
                    $candidateId = $indexedTerm->candidate_id;

                    // Calculate IDF (Inverse Document Frequency)
                    $idf = log(($totalDocs - $indexedTerm->doc_freq + 0.5) / ($indexedTerm->doc_freq + 0.5) + 1);

                    // Calculate TF with BM25 normalization
                    $tf = ($indexedTerm->term_freq * ($this->k1 + 1)) /
                        ($indexedTerm->term_freq + $this->k1 * (1 - $this->b + $this->b * ($indexedTerm->doc_length / $avgDocLength)));

                    $score = $idf * $tf;

                    if (!isset($results[$candidateId])) {
                        $results[$candidateId] = 0;
                    }

                    $results[$candidateId] += $score;
                }
            }

            // Sort by score descending
            arsort($results);
            return $results;

        } catch (\Exception $e) {
            Log::error('CandidateBM25Service search error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Rank candidates for a specific job
     * 
     * @param int $jobId Job ID to rank candidates for
     * @return array Array of candidate_id => bm25_score
     */
    public function rankCandidatesForJob($jobId)
    {
        try {
            // Get job requirements
            $job = DB::table('jobs')
                     ->where('id', $jobId)
                     ->first();

            if (!$job) {
                Log::warning("Job not found for ranking: {$jobId}");
                return [];
            }

            // Extract job requirements as query terms
            $queryTerms = $this->extractJobRequirements($job);

            if (empty($queryTerms)) {
                Log::info("No query terms extracted for job: {$jobId}");
                return [];
            }

            // Get BM25 statistics for candidates
            $totalDocs = DB::table('candidate_search_index')
                           ->distinct('candidate_id')
                           ->count('candidate_id');

            $avgDocLength = DB::table('candidate_search_index')->avg('doc_length') ?: 0;

            if ($totalDocs === 0 || $avgDocLength === 0) {
                Log::info('No indexed candidates found for ranking');
                return [];
            }

            // Perform BM25 search
            return $this->search($queryTerms, $totalDocs, $avgDocLength);

        } catch (\Exception $e) {
            Log::error('CandidateBM25Service rankCandidatesForJob error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Extract searchable terms from job requirements
     * 
     * @param object $job Job object
     * @return array Array of search terms
     */
    private function extractJobRequirements($job)
    {
        $content = [];

        // Job Title (weight: 3.0x) - Highest priority for job matching
        $content[] = str_repeat($job->title . ' ', 3);

        // Job description (weight: 2.0x) - Profile Summary content matching
        $content[] = str_repeat(strip_tags($job->description) . ' ', 2);

        // Job skills (weight: 1.5x) - Skills Match criteria
        $jobSkills = DB::table('job_skills')
                       ->join('skills', 'job_skills.skill_id', '=', 'skills.id')
                       ->where('job_skills.job_id', $job->id)
                       ->pluck('skills.name');

        foreach ($jobSkills as $skillName) {
            $content[] = $skillName . ' ' . substr($skillName, 0, (int)(strlen($skillName)/2));
        }

        // Job category and role (weight: 1.5x) - Experience matching
        $jobCategory = DB::table('job_categories')
                         ->where('id', $job->job_category_id)
                         ->first();

        if ($jobCategory) {
            $content[] = $jobCategory->name . ' ' . substr($jobCategory->name, 0, (int)(strlen($jobCategory->name)/2));
        }

        // Job role for experience matching (weight: 1.5x)
        $jobRole = DB::table('job_roles')
                     ->where('id', $job->job_role_id)
                     ->first();

        if ($jobRole) {
            $content[] = $jobRole->name . ' ' . substr($jobRole->name, 0, (int)(strlen($jobRole->name)/2));
        }

        // Combine all content and tokenize
        $fullText = implode(' ', $content);
        return $this->tokenize($fullText);
    }

    /**
     * Tokenize text into search terms
     * 
     * @param string $text Text to tokenize
     * @return array Array of terms
     */
    private function tokenize($text)
    {
        // Convert to lowercase and remove special characters
        $text = strtolower(preg_replace('/[^\w\s]/', ' ', $text));

        // Split into terms
        $terms = array_filter(explode(' ', $text), function($term) {
            return strlen(trim($term)) >= 2;
        });

        // Remove common stop words
        $stopWords = ['the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by'];
        $terms = array_diff($terms, $stopWords);

        return array_values($terms);
    }

    /**
     * Set BM25 parameters for tuning
     * 
     * @param float|null $k1 Term frequency saturation parameter
     * @param float|null $b Length normalization parameter
     */
    public function setParameters($k1 = null, $b = null)
    {
        if ($k1 !== null) $this->k1 = $k1;
        if ($b !== null) $this->b = $b;
    }

    /**
     * Get current BM25 parameters
     * 
     * @return array Current parameters
     */
    public function getParameters()
    {
        return [
            'k1' => $this->k1,
            'b' => $this->b
        ];
    }
}
