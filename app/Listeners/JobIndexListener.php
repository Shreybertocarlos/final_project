<?php

namespace App\Listeners;

use App\Models\Job;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class JobIndexListener
{
    /**
     * Handle job created event
     */
    public function created(Job $job)
    {
        $this->indexJob($job);
    }

    /**
     * Handle job updated event
     */
    public function updated(Job $job)
    {
        $this->reindexJob($job);
    }

    /**
     * Handle job deleted event
     */
    public function deleted(Job $job)
    {
        DB::table('job_search_index')->where('job_id', $job->id)->delete();
        $this->updateDocumentFrequencies();
    }

    /**
     * Index a job for BM25 search
     */
    protected function indexJob(Job $job)
    {
        try {
            // Load relationships (only needed ones for indexing)
            $job->load(['category', 'skills.skill']);

            // Collect content with updated weighting strategy
            $content = [];
            
            // Job title (weight: 3x) - Highest priority
            $content[] = str_repeat($job->title . ' ', 3);
            
            // Job description (weight: 1x) - Base weight
            $content[] = strip_tags($job->description);
            
            // Skills (weight: 2x) - Second priority for skill matching
            foreach ($job->skills as $jobSkill) {
                if ($jobSkill->skill) {
                    $content[] = str_repeat($jobSkill->skill->name . ' ', 2);
                }
            }
            
            // Job category (weight: 1.5x) - Third priority for categorization
            if ($job->category) {
                $content[] = $job->category->name . ' ' . substr($job->category->name, 0, strlen($job->category->name)/2);
            }
            
            // EXCLUDED: Tags and Company Name are not indexed for BM25 search

            $fullText = implode(' ', $content);
            $terms = $this->tokenize($fullText);
            $termFreqs = array_count_values($terms);
            $docLength = count($terms);

            foreach ($termFreqs as $term => $freq) {
                DB::table('job_search_index')->insert([
                    'job_id' => $job->id,
                    'term' => $term,
                    'term_freq' => $freq,
                    'doc_length' => $docLength,
                    'doc_freq' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->updateDocumentFrequencies();
            
        } catch (\Exception $e) {
            Log::error('Job indexing failed: ' . $e->getMessage());
        }
    }

    /**
     * Re-index a job (delete old index and create new)
     */
    protected function reindexJob(Job $job)
    {
        DB::table('job_search_index')->where('job_id', $job->id)->delete();
        $this->indexJob($job);
    }

    /**
     * Tokenize text into search terms
     */
    protected function tokenize($text)
    {
        $text = strtolower(preg_replace('/[^\w\s]/', ' ', $text));
        $terms = array_filter(explode(' ', $text), function($term) {
            return strlen(trim($term)) >= 2;
        });
        $stopWords = ['the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by'];
        return array_values(array_diff($terms, $stopWords));
    }

    /**
     * Update document frequencies for all terms
     */
    protected function updateDocumentFrequencies()
    {
        $terms = DB::table('job_search_index')->select('term')->distinct()->pluck('term');
        
        foreach ($terms as $term) {
            $docFreq = DB::table('job_search_index')
                         ->where('term', $term)
                         ->distinct('job_id')
                         ->count('job_id');

            DB::table('job_search_index')
              ->where('term', $term)
              ->update(['doc_freq' => $docFreq]);
        }
    }
}
