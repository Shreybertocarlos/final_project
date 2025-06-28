<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BM25Service
{
    private $k1 = 1.2;
    private $b = 0.75;

    public function search(array $queryTerms, $totalDocs, $avgDocLength)
    {
        try {
            $results = [];

            foreach ($queryTerms as $term) {
                // Fixed: Changed from 'rank' to 'job_search_index'
                $indexedTerms = DB::table('job_search_index')->where('term', $term)->get();

                foreach ($indexedTerms as $indexedTerm) {
                    $jobId = $indexedTerm->job_id;

                    // Calculate IDF
                    $idf = log(($totalDocs - $indexedTerm->doc_freq + 0.5) / ($indexedTerm->doc_freq + 0.5) + 1);

                    // Calculate TF with BM25 normalization
                    $tf = ($indexedTerm->term_freq * ($this->k1 + 1)) /
                        ($indexedTerm->term_freq + $this->k1 * (1 - $this->b + $this->b * ($indexedTerm->doc_length / $avgDocLength)));

                    $score = $idf * $tf;

                    if (!isset($results[$jobId])) {
                        $results[$jobId] = 0;
                    }

                    $results[$jobId] += $score;
                }
            }

            arsort($results);
            return $results;

        } catch (\Exception $e) {
            Log::error('BM25Service search error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Set BM25 parameters for tuning
     */
    public function setParameters($k1 = null, $b = null)
    {
        if ($k1 !== null) $this->k1 = $k1;
        if ($b !== null) $this->b = $b;
    }
}
