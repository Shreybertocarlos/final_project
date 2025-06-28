<?php

namespace App\Console\Commands;

use App\Models\Job;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class IndexJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'index:jobs {--fresh : Clear existing index before rebuilding}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Index jobs for BM25 search';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('fresh')) {
            $this->info('Clearing existing index...');
            DB::table('job_search_index')->truncate();
        }

        $this->info('Starting job indexing...');

        // Updated: Only load needed relationships (removed tags and company)
        $jobs = Job::with(['category', 'skills.skill'])
                   ->where('status', 'active')
                   ->get();

        $bar = $this->output->createProgressBar($jobs->count());
        $bar->start();

        foreach ($jobs as $job) {
            $this->indexJob($job);
            $bar->advance();
        }

        $bar->finish();
        $this->info("\nIndexing completed successfully!");

        // Update document frequencies
        $this->updateDocumentFrequencies();

        $this->info('Document frequencies updated!');
    }

    private function indexJob($job)
    {
        // Clear existing index for this job
        DB::table('job_search_index')->where('job_id', $job->id)->delete();

        // Collect all text content with updated weighting strategy
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

        // Combine all content
        $fullText = implode(' ', $content);

        // Tokenize
        $terms = $this->tokenize($fullText);
        $termFreqs = array_count_values($terms);
        $docLength = count($terms);

        // Insert into index
        foreach ($termFreqs as $term => $freq) {
            DB::table('job_search_index')->insert([
                'job_id' => $job->id,
                'term' => $term,
                'term_freq' => $freq,
                'doc_length' => $docLength,
                'doc_freq' => 0, // Will be updated later
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function tokenize($text)
    {
        // Convert to lowercase and remove special characters
        $text = strtolower(preg_replace('/[^\w\s]/', ' ', $text));

        // Split into terms
        $terms = array_filter(explode(' ', $text), function($term) {
            return strlen(trim($term)) >= 2;
        });

        // Remove stop words
        $stopWords = ['the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by'];
        $terms = array_diff($terms, $stopWords);

        return array_values($terms);
    }

    private function updateDocumentFrequencies()
    {
        $this->info('Updating document frequencies...');

        $terms = DB::table('job_search_index')
                   ->select('term')
                   ->distinct()
                   ->pluck('term');

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
