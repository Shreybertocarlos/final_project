# BM25 Search Algorithm Implementation Guide

This guide provides a comprehensive analysis of implementing the BM25 (Best Matching 25) search algorithm in a Laravel application, based on the job portal codebase implementation.

## Table of Contents

1. [Overview of BM25 Algorithm](#overview-of-bm25-algorithm)
2. [Component Analysis](#component-analysis)
   - [BM25Service.php](#bm25servicephp)
   - [IndexJobs.php](#indexjobsphp)
   - [HomeController.php](#homecontrollerphp)
3. [Database Schema Requirements](#database-schema-requirements)
4. [Implementation Process](#implementation-process)
5. [Step-by-Step Implementation Guide](#step-by-step-implementation-guide)
   - [Database Migration](#1-database-migration)
   - [BM25Service Implementation](#2-bm25service-implementation)
   - [Indexing Command Implementation](#3-indexing-command-implementation)
   - [Search Controller Implementation](#4-search-controller-implementation)
6. [Mathematical Formulas](#mathematical-formulas)
7. [Component Relationships](#component-relationships)
8. [Optimization Considerations](#optimization-considerations)

## Overview of BM25 Algorithm

BM25 (Best Matching 25) is a ranking function used by search engines to rank matching documents according to their relevance to a given search query. It's an improvement over the basic TF-IDF (Term Frequency-Inverse Document Frequency) weighting scheme.

Key advantages of BM25:
- Handles term frequency saturation (diminishing returns for repeated terms)
- Normalizes document length to avoid bias toward longer documents
- Provides tunable parameters to adjust for different document collections

## Component Analysis

### BM25Service.php

The `BM25Service` class implements the core BM25 algorithm logic:

```php
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
            $indexedTerms = DB::table('job_search_index')->where('term', $term)->get();

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
```

**Key Components:**

1. **Parameters**:
   - `$k1 = 1.2`: Controls term frequency saturation
   - `$b = 0.75`: Controls document length normalization

2. **BM25 Formula Implementation**:
   - **IDF (Inverse Document Frequency)**: `log((N - n + 0.5) / (n + 0.5) + 1)`
     - N = total documents (`$totalDocs`)
     - n = number of documents containing the term (`$indexedTerm->doc_freq`)
   
   - **TF (Term Frequency)**: `(tf * (k1 + 1)) / (tf + k1 * (1 - b + b * (dl/avgdl)))`
     - tf = frequency of term in document (`$indexedTerm->term_freq`)
     - dl = document length (`$indexedTerm->doc_length`)
     - avgdl = average document length (`$avgDocLength`)

3. **Score Accumulation**:
   - Scores are accumulated per document across all query terms
   - Results are sorted in descending order by score

### IndexJobs.php

The `IndexJobs` command handles document preprocessing, tokenization, and indexing:

```php
<?php

namespace App\Console\Commands;

use App\Models\Job;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class IndexJobs extends Command
{
    protected $signature = 'index:jobs';
    protected $description = 'Command description';

    public function handle()
    {
        $jobs = Job::all();
        $totalDocs = $jobs->count();
        $termsData = [];

        foreach ($jobs as $job) {
            $tokens = $this->tokenize($job->job_description);
            $docLength = count($tokens);

            if ($docLength === 0) {
                $this->warn("Job ID {$job->id} has no valid terms.");
                continue;
            }

            $termFreqs = array_count_values($tokens);
            foreach ($termFreqs as $term => $freq) {

                if (!isset($termsData[$term])) {
                    $termsData[$term] = ['doc_freq' => 0];
                }
                $termsData[$term]['doc_freq']++;

                \DB::table('job_search_index')->insert([
                    'job_id' => $job->id,
                    'term' => $term,
                    'term_freq' => $freq,
                    'doc_length' => $docLength,
                    'doc_freq' => $termsData[$term]['doc_freq'],
                ]);
            }
        }

        $this->info('Indexing complete!');
    }

    private function tokenize($text)
    {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9\s]/', '', $text);
        return array_filter(explode(' ', $text));
    }
}
```

**Key Components:**

1. **Document Collection**:
   - Retrieves all jobs from the database

2. **Tokenization**:
   - Processes job descriptions into tokens (words)
   - Calculates document length as the count of tokens

3. **Term Frequency Calculation**:
   - Uses `array_count_values()` to count occurrences of each term in a document

4. **Document Frequency Tracking**:
   - Tracks how many documents contain each term

5. **Index Storage**:
   - Stores term data in the `job_search_index` table

### HomeController.php

The `HomeController` integrates the BM25 service into the search API endpoint:

```php
public function search(Request $request, BM25Service $bm25Service)
{
    $query = $request->input('query');
    $queryTerms = explode(' ', strtolower($query));

    $totalDocs = Job::count();
    $avgDocLength = DB::table('job_search_index')->avg('doc_length');

    $bm25Results = $bm25Service->search($queryTerms, $totalDocs, $avgDocLength);

    $jobIds = array_keys($bm25Results);
    $jobs = Job::whereIn('id', $jobIds)->latest()->paginate(10)->map(function ($job) use ($bm25Results) {
        $job->bm25_score = $bm25Results[$job->id];
        return $job;
    });
    return JobResource::collection($jobs);
}
```

**Key Components:**

1. **Query Processing**:
   - Extracts search query from request
   - Splits query into terms (tokenization)
   - Converts to lowercase for case-insensitive search

2. **Statistics Calculation**:
   - Calculates total document count
   - Calculates average document length from index

3. **BM25 Search Execution**:
   - Calls BM25Service with query terms and statistics

4. **Result Processing**:
   - Extracts job IDs from BM25 results
   - Retrieves job records from database
   - Attaches BM25 scores to job objects
   - Returns formatted results as API response

## Database Schema Requirements

The implementation relies on a `job_search_index` table with the following structure:

```php
Schema::create('job_search_index', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('job_id');
    $table->string('term');
    $table->integer('term_freq');
    $table->integer('doc_length');
    $table->integer('doc_freq');
    $table->foreign('job_id')->references('id')->on('jobs')->onDelete('cascade');
    $table->timestamps();
});
```

**Fields:**
- `id`: Primary key
- `job_id`: Foreign key to the document being indexed
- `term`: The indexed term/word
- `term_freq`: Number of occurrences of the term in the document
- `doc_length`: Total number of terms in the document
- `doc_freq`: Number of documents containing this term

## Implementation Process

### Step 1: Database Setup

1. Create a search index table with the fields described above
2. Add appropriate foreign key constraints

### Step 2: Create BM25Service Class

1. Create a service class with BM25 parameters:
   - `k1` (typically 1.2-2.0): Controls term frequency saturation
   - `b` (typically 0.75): Controls document length normalization

2. Implement the search method:
   - Accept query terms, total document count, and average document length
   - For each query term, retrieve matching index entries
   - Calculate IDF and TF components using BM25 formula
   - Accumulate scores per document
   - Return sorted results

### Step 3: Create Indexing Command/Process

1. Create a command or process to:
   - Retrieve all documents to be indexed
   - Implement tokenization logic (splitting text into terms)
   - Calculate term frequencies for each document
   - Track document frequencies across the corpus
   - Store index data in the database

2. Tokenization considerations:
   - Convert to lowercase
   - Remove punctuation
   - Consider stemming/lemmatization
   - Remove stop words

### Step 4: Integrate with API Controller

1. Create a search endpoint that:
   - Processes user query into terms
   - Calculates necessary statistics (total docs, avg doc length)
   - Calls BM25Service with query terms and statistics
   - Retrieves and formats search results
   - Returns appropriate response

### Step 5: Create Indexing Workflow

1. Implement a workflow to:
   - Run indexing when new documents are added
   - Update index when documents are modified
   - Remove index entries when documents are deleted

## Step-by-Step Implementation Guide

### 1. Database Migration

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_index', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->string('term');
            $table->integer('term_freq');
            $table->integer('doc_length');
            $table->integer('doc_freq');
            $table->foreign('document_id')->references('id')->on('documents')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_index');
    }
};
```

### 2. BM25Service Implementation

```php
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
            $indexedTerms = DB::table('search_index')->where('term', $term)->get();

            foreach ($indexedTerms as $indexedTerm) {
                $documentId = $indexedTerm->document_id;

                // Calculate IDF (Inverse Document Frequency)
                $idf = log(($totalDocs - $indexedTerm->doc_freq + 0.5) / ($indexedTerm->doc_freq + 0.5) + 1);
                
                // Calculate TF (Term Frequency)
                $tf = ($indexedTerm->term_freq * ($this->k1 + 1)) /
                    ($indexedTerm->term_freq + $this->k1 * (1 - $this->b + $this->b * ($indexedTerm->doc_length / $avgDocLength)));

                // Calculate score for this term
                $score = $idf * $tf;

                // Accumulate score for this document
                if (!isset($results[$documentId])) {
                    $results[$documentId] = 0;
                }
                $results[$documentId] += $score;
            }
        }

        // Sort results by score (descending)
        arsort($results);

        return $results;
    }
}
```

### 3. Indexing Command Implementation

```php
namespace App\Console\Commands;

use App\Models\Document;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class IndexDocuments extends Command
{
    protected $signature = 'index:documents';
    protected $description = 'Index documents for BM25 search';

    public function handle()
    {
        // Clear existing index
        DB::table('search_index')->truncate();
        
        $documents = Document::all();
        $totalDocs = $documents->count();
        $termsData = [];

        foreach ($documents as $document) {
            // Tokenize document content
            $tokens = $this->tokenize($document->content);
            $docLength = count($tokens);

            if ($docLength === 0) {
                $this->warn("Document ID {$document->id} has no valid terms.");
                continue;
            }

            // Calculate term frequencies
            $termFreqs = array_count_values($tokens);
            
            foreach ($termFreqs as $term => $freq) {
                // Track document frequency
                if (!isset($termsData[$term])) {
                    $termsData[$term] = ['doc_freq' => 0];
                }
                $termsData[$term]['doc_freq']++;

                // Store in index
                DB::table('search_index')->insert([
                    'document_id' => $document->id,
                    'term' => $term,
                    'term_freq' => $freq,
                    'doc_length' => $docLength,
                    'doc_freq' => $termsData[$term]['doc_freq'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->info('Indexing complete!');
    }

    private function tokenize($text)
    {
        // Convert to lowercase
        $text = strtolower($text);
        
        // Remove punctuation and special characters
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        
        // Split into words
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        // Remove stop words (optional)
        $stopWords = ['the', 'and', 'a', 'to', 'of', 'in', 'is', 'that', 'for', 'on'];
        $words = array_diff($words, $stopWords);
        
        return $words;
    }
}
```

### 4. Search Controller Implementation

```php
namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\BM25Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    public function search(Request $request, BM25Service $bm25Service)
    {
        // Validate request
        $request->validate([
            'query' => 'required|string|min:2',
        ]);

        // Process query
        $query = $request->input('query');
        $queryTerms = explode(' ', strtolower($query));
        
        // Filter out empty terms and stop words
        $stopWords = ['the', 'and', 'a', 'to', 'of', 'in', 'is', 'that', 'for', 'on'];
        $queryTerms = array_filter($queryTerms, function($term) use ($stopWords) {
            return !empty($term) && !in_array($term, $stopWords);
        });
        
        if (empty($queryTerms)) {
            return response()->json(['message' => 'No valid search terms provided'], 400);
        }

        // Get statistics
        $totalDocs = Document::count();
        $avgDocLength = DB::table('search_index')->avg('doc_length');

        // Perform search
        $bm25Results = $bm25Service->search($queryTerms, $totalDocs, $avgDocLength);

        // Get document IDs from results
        $documentIds = array_keys($bm25Results);
        
        // Retrieve documents
        $documents = Document::whereIn('id', $documentIds)
            ->get()
            ->map(function ($document) use ($bm25Results) {
                // Attach score to document
                $document->score = $bm25Results[$document->id];
                return $document;
            })
            ->sortByDesc('score')
            ->values();

        return response()->json([
            'results' => $documents,
            'count' => count($documents),
        ]);
    }
}
```

## Mathematical Formulas

The BM25 algorithm uses the following mathematical formulas:

### 1. BM25 Score

The BM25 score for a document is the sum of the scores for each query term:

```
Score(D,Q) = ∑(IDF(qi) × TF(qi,D))
```

Where:
- D is the document
- Q is the query containing terms qi
- IDF is the Inverse Document Frequency
- TF is the Term Frequency

### 2. Inverse Document Frequency (IDF)

```
IDF(qi) = log((N - n(qi) + 0.5) / (n(qi) + 0.5) + 1)
```

Where:
- N is the total number of documents in the collection
- n(qi) is the number of documents containing term qi

### 3. Term Frequency (TF)

```
TF(qi,D) = (f(qi,D) × (k1 + 1)) / (f(qi,D) + k1 × (1 - b + b × (|D| / avgdl)))
```

Where:
- f(qi,D) is the frequency of term qi in document D
- |D| is the length of document D (in terms)
- avgdl is the average document length in the collection
- k1 and b are free parameters:
  - k1 controls term frequency saturation (typically 1.2-2.0)
  - b controls document length normalization (typically 0.75)

## Component Relationships

1. **Data Flow**:
   - Documents → Tokenization → Index Storage
   - Query → Tokenization → BM25 Scoring → Results

2. **Component Interactions**:
   - Indexing Command populates the search index table
   - Search Controller processes user queries
   - BM25Service calculates relevance scores
   - Database stores both documents and index data

3. **Mathematical Relationship**:
   - Term Frequency (TF): How often a term appears in a document
   - Document Frequency (DF): How many documents contain a term
   - Inverse Document Frequency (IDF): Measures term importance across all documents
   - Document Length: Normalizes scores to account for document size differences

## Optimization Considerations

### 1. Performance

- **Database Indexing**:
  - Create indexes on the `term` column in the search index table
  - Consider composite indexes for frequently used query patterns

- **Batch Processing**:
  - Use batch inserts for indexing large document collections
  - Consider chunking document retrieval for memory efficiency

- **Caching**:
  - Cache frequently used statistics (total docs, avg doc length)
  - Consider caching common search results

### 2. Accuracy

- **Parameter Tuning**:
  - Experiment with different `k1` values (1.2-2.0) to adjust term frequency saturation
  - Adjust `b` parameter (0.5-0.8) to control document length normalization

- **Tokenization Improvements**:
  - Implement stemming to handle word variations (e.g., "run", "running", "runs")
  - Add lemmatization for more accurate word base forms
  - Create a comprehensive stop word list for your domain

- **Query Expansion**:
  - Consider implementing synonyms for common terms
  - Add support for phrase queries and proximity searches

### 3. Scalability

- **Incremental Indexing**:
  - Implement logic to only index new or modified documents
  - Use database triggers or event listeners to detect changes

- **Partitioning**:
  - For very large collections, consider partitioning the index table
  - Implement sharding strategies for distributed search

- **Asynchronous Processing**:
  - Use queues for background indexing tasks
  - Implement job batching for large indexing operations

- **Monitoring**:
  - Add metrics to track indexing time and search performance
  - Implement logging for failed indexing operations and slow queries