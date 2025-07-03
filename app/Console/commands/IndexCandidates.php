<?php

namespace App\Console\Commands;

use App\Models\Candidate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class IndexCandidates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'index:candidates {--fresh : Clear existing index before rebuilding}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Index candidate profiles for BM25 search';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('fresh')) {
            $this->info('Clearing existing candidate index...');
            DB::table('candidate_search_index')->truncate();
        }

        $this->info('Starting candidate indexing...');

        // Load candidates with all needed relationships
        $candidates = Candidate::with(['skills.skill', 'experiences', 'educations', 'profession'])
                               ->where('profile_complete', 1)
                               ->where('visibility', 1)
                               ->get();

        if ($candidates->isEmpty()) {
            $this->warn('No candidates found for indexing. Make sure candidates have profile_complete=1 and visibility=1.');
            return;
        }

        $bar = $this->output->createProgressBar($candidates->count());
        $bar->start();

        $indexed = 0;
        $skipped = 0;

        foreach ($candidates as $candidate) {
            try {
                $this->indexCandidate($candidate);
                $indexed++;
            } catch (\Exception $e) {
                $this->error("\nFailed to index candidate {$candidate->id}: " . $e->getMessage());
                $skipped++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->info("\nIndexing completed!");
        $this->info("Successfully indexed: {$indexed} candidates");
        if ($skipped > 0) {
            $this->warn("Skipped: {$skipped} candidates due to errors");
        }

        // Update document frequencies
        $this->info('Updating document frequencies...');
        $this->updateDocumentFrequencies();

        $this->info('Document frequencies updated!');

        // Show statistics
        $this->showIndexStatistics();
    }

    private function indexCandidate($candidate)
    {
        // Clear existing index for this candidate
        DB::table('candidate_search_index')->where('candidate_id', $candidate->id)->delete();

        // Collect all text content with weighting strategy
        $content = [];

        // Skills (weight: 3x) - Highest priority for job matching
        foreach ($candidate->skills as $candidateSkill) {
            if ($candidateSkill->skill) {
                $content[] = str_repeat($candidateSkill->skill->name . ' ', 3);
            }
        }

        // Current title/designation (weight: 2.5x) - Second priority
        if ($candidate->title) {
            $content[] = str_repeat($candidate->title . ' ', 2) . substr($candidate->title, 0, (int)(strlen($candidate->title)/2));
        }

        // Experience responsibilities and designations (weight: 2x) - Third priority
        foreach ($candidate->experiences as $experience) {
            if ($experience->responsibilities) {
                $content[] = str_repeat(strip_tags($experience->responsibilities) . ' ', 2);
            }
            if ($experience->designation) {
                $content[] = str_repeat($experience->designation . ' ', 2);
            }
        }

        // Bio/summary (weight: 1.5x) - Fourth priority
        if ($candidate->bio) {
            $content[] = $candidate->bio . ' ' . substr($candidate->bio, 0, (int)(strlen($candidate->bio)/2));
        }

        // Education degrees (weight: 1x) - Base weight
        foreach ($candidate->educations as $education) {
            if ($education->degree) {
                $content[] = $education->degree;
            }
        }

        // Profession (weight: 1x) - Base weight
        if ($candidate->profession) {
            $content[] = $candidate->profession->name;
        }

        // Combine all content
        $fullText = implode(' ', $content);

        // Tokenize
        $terms = $this->tokenize($fullText);
        $termFreqs = array_count_values($terms);
        $docLength = count($terms);

        if ($docLength === 0) {
            throw new \Exception("No indexable content found for candidate {$candidate->id}");
        }

        // Insert into index
        foreach ($termFreqs as $term => $freq) {
            DB::table('candidate_search_index')->insert([
                'candidate_id' => $candidate->id,
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

        // Split into terms and filter by length
        $terms = array_filter(explode(' ', $text), function($term) {
            return strlen(trim($term)) >= 2;
        });

        // Remove common stop words
        $stopWords = ['the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'is', 'are', 'was', 'were', 'be', 'been', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should'];

        return array_values(array_diff($terms, $stopWords));
    }

    private function updateDocumentFrequencies()
    {
        $terms = DB::table('candidate_search_index')
                   ->select('term')
                   ->distinct()
                   ->pluck('term');

        foreach ($terms as $term) {
            $docFreq = DB::table('candidate_search_index')
                         ->where('term', $term)
                         ->distinct('candidate_id')
                         ->count('candidate_id');

            DB::table('candidate_search_index')
              ->where('term', $term)
              ->update(['doc_freq' => $docFreq]);
        }
    }

    private function showIndexStatistics()
    {
        $totalEntries = DB::table('candidate_search_index')->count();
        $uniqueTerms = DB::table('candidate_search_index')->distinct('term')->count();
        $indexedCandidates = DB::table('candidate_search_index')->distinct('candidate_id')->count();
        $avgDocLength = DB::table('candidate_search_index')->avg('doc_length');

        $this->info('=== Index Statistics ===');
        $this->info("Total index entries: {$totalEntries}");
        $this->info("Unique terms: {$uniqueTerms}");
        $this->info("Indexed candidates: {$indexedCandidates}");
        $this->info("Average document length: " . round($avgDocLength, 2));
    }
}
