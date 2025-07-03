<?php

namespace App\Listeners;

use App\Models\Candidate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CandidateIndexListener
{
    /**
     * Handle candidate created event
     */
    public function created(Candidate $candidate)
    {
        $this->indexCandidate($candidate);
    }

    /**
     * Handle candidate updated event
     */
    public function updated(Candidate $candidate)
    {
        $this->reindexCandidate($candidate);
    }

    /**
     * Handle candidate deleted event
     */
    public function deleted(Candidate $candidate)
    {
        DB::table('candidate_search_index')->where('candidate_id', $candidate->id)->delete();
        $this->updateDocumentFrequencies();
    }

    /**
     * Index a candidate profile for BM25 search
     */
    protected function indexCandidate(Candidate $candidate)
    {
        try {
            // Load relationships needed for indexing
            $candidate->load(['skills.skill', 'experiences', 'educations', 'profession']);

            // Only index candidates with complete, visible profiles
            if (!$candidate->profile_complete || !$candidate->visibility) {
                Log::info("Skipping indexing for incomplete/invisible candidate: {$candidate->id}");
                return;
            }

            // Collect content with weighting strategy
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

            // EXCLUDED: Personal information like address, phone, email are not indexed

            $fullText = implode(' ', $content);
            $terms = $this->tokenize($fullText);
            $termFreqs = array_count_values($terms);
            $docLength = count($terms);

            // Skip if no meaningful content
            if ($docLength === 0) {
                Log::info("No indexable content found for candidate: {$candidate->id}");
                return;
            }

            foreach ($termFreqs as $term => $freq) {
                DB::table('candidate_search_index')->insert([
                    'candidate_id' => $candidate->id,
                    'term' => $term,
                    'term_freq' => $freq,
                    'doc_length' => $docLength,
                    'doc_freq' => 0, // Will be updated by updateDocumentFrequencies
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->updateDocumentFrequencies();

            Log::info("Successfully indexed candidate: {$candidate->id} with {$docLength} terms");

        } catch (\Exception $e) {
            Log::error("Candidate indexing failed for candidate {$candidate->id}: " . $e->getMessage());
        }
    }

    /**
     * Re-index a candidate (delete old index and create new)
     */
    protected function reindexCandidate(Candidate $candidate)
    {
        DB::table('candidate_search_index')->where('candidate_id', $candidate->id)->delete();
        $this->indexCandidate($candidate);
    }

    /**
     * Tokenize text into search terms
     */
    protected function tokenize($text)
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

    /**
     * Update document frequencies for all terms
     */
    protected function updateDocumentFrequencies()
    {
        $terms = DB::table('candidate_search_index')->select('term')->distinct()->pluck('term');

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

    /**
     * Handle candidate skill changes (created/deleted)
     */
    public function skillChanged($candidateSkill)
    {
        try {
            $candidate = Candidate::find($candidateSkill->candidate_id);
            if ($candidate) {
                $this->reindexCandidate($candidate);
                Log::info("Reindexed candidate {$candidate->id} due to skill change");
            }
        } catch (\Exception $e) {
            Log::error("Failed to reindex candidate after skill change: " . $e->getMessage());
        }
    }

    /**
     * Handle candidate experience changes (created/updated/deleted)
     */
    public function experienceChanged($candidateExperience)
    {
        try {
            $candidate = Candidate::find($candidateExperience->candidate_id);
            if ($candidate) {
                $this->reindexCandidate($candidate);
                Log::info("Reindexed candidate {$candidate->id} due to experience change");
            }
        } catch (\Exception $e) {
            Log::error("Failed to reindex candidate after experience change: " . $e->getMessage());
        }
    }

    /**
     * Handle candidate education changes (created/updated/deleted)
     */
    public function educationChanged($candidateEducation)
    {
        try {
            $candidate = Candidate::find($candidateEducation->candidate_id);
            if ($candidate) {
                $this->reindexCandidate($candidate);
                Log::info("Reindexed candidate {$candidate->id} due to education change");
            }
        } catch (\Exception $e) {
            Log::error("Failed to reindex candidate after education change: " . $e->getMessage());
        }
    }
}
