<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class BM25Service
{
    private $k1 = 1.2;
    private $b = 0.75;

    public function search(array $queryTerms, $totalDocs, $avgDocLength)
    {
        $results = [];

        foreach ($queryTerms as $term) {
            $indexedTerms = DB::table('rank')->where('term', $term)->get();

            foreach ($indexedTerms as $indexedTerm) {
                $jobId = $indexedTerm->job_id;

                $idf = log(($totalDocs - $indexedTerm->doc_freq + 0.5) / ($indexedTerm->doc_freq + 0.5) + 1);
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
    }
}
