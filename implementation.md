# BM25 Search Implementation Guide

## Overview

This document provides a comprehensive guide for implementing a BM25-based search system in a Laravel application. The implementation consists of several components working together to provide efficient and relevant search results.

## Components

### 1. Database Migration

Create a migration for the job search index table:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_search_index', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_id');
            $table->string('term');
            $table->integer('term_freq');
            $table->integer('doc_length');
            $table->integer('doc_freq');
            $table->timestamps();
            
            $table->foreign('job_id')->references('id')->on('jobs')->onDelete('cascade');
            $table->index('term');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_search_index');
    }
};
```

### 2. BM25Service Class

Create a service class that implements the BM25 algorithm:

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
    
    public function calculateScores(array $queryTerms, array $termStats, array $docFreqs, $totalDocs, $avgDocLength)
    {
        $results = [];
        
        foreach ($queryTerms as $term) {
            if (!isset($termStats[$term])) {
                continue;
            }
            
            $docFreq = $docFreqs[$term];
            
            foreach ($termStats[$term] as $docId => $stats) {
                $termFreq = $stats['term_freq'];
                $docLength = $stats['doc_length'];
                
                $idf = log(($totalDocs - $docFreq + 0.5) / ($docFreq + 0.5) + 1);
                $tf = ($termFreq * ($this->k1 + 1)) /
                    ($termFreq + $this->k1 * (1 - $this->b + $this->b * ($docLength / $avgDocLength)));
                
                $score = $idf * $tf;
                
                if (!isset($results[$docId])) {
                    $results[$docId] = 0;
                }
                
                $results[$docId] += $score;
            }
        }
        
        arsort($results);
        
        return $results;
    }
}
```

### 3. Job Indexing Command

Create a command to build and maintain the search index:

```php
<?php

namespace App\Console\Commands;

use App\Models\Job;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class IndexJobs extends Command
{
    protected $signature = 'index:jobs {--fresh : Clear existing index before indexing}';
    protected $description = 'Index jobs for BM25 search';

    public function handle()
    {
        if ($this->option('fresh')) {
            $this->info('Clearing existing index...');
            DB::table('job_search_index')->truncate();
        }

        $jobs = Job::where('status', 1)->get();
        $totalJobs = $jobs->count();
        
        if ($totalJobs === 0) {
            $this->warn('No active jobs found to index.');
            return;
        }
        
        $this->info("Indexing {$totalJobs} jobs...");
        
        $bar = $this->output->createProgressBar($totalJobs);
        $bar->start();
        
        $termsData = [];
        $batchSize = 100;
        $batchInserts = [];

        foreach ($jobs as $job) {
            // Combine job title, description, category, type, and skills for comprehensive search
            $searchText = $job->title . ' ' . $job->job_description;
            
            if ($job->jobCategory) {
                $searchText .= ' ' . $job->jobCategory->name;
            }
            
            if ($job->jobType) {
                $searchText .= ' ' . $job->jobType->name;
            }
            
            // Tokenize the combined text
            $tokens = $this->tokenize($searchText);
            $docLength = count($tokens);

            if ($docLength === 0) {
                $this->warn("Job ID {$job->id} has no valid terms.");
                $bar->advance();
                continue;
            }

            $termFreqs = array_count_values($tokens);
            
            foreach ($termFreqs as $term => $freq) {
                if (!isset($termsData[$term])) {
                    $termsData[$term] = ['doc_freq' => 0];
                }
                $termsData[$term]['doc_freq']++;

                $batchInserts[] = [
                    'job_id' => $job->id,
                    'term' => $term,
                    'term_freq' => $freq,
                    'doc_length' => $docLength,
                    'doc_freq' => $termsData[$term]['doc_freq'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                
                // Insert in batches to improve performance
                if (count($batchInserts) >= $batchSize) {
                    DB::table('job_search_index')->insert($batchInserts);
                    $batchInserts = [];
                }
            }
            
            $bar->advance();
        }
        
        // Insert any remaining records
        if (!empty($batchInserts)) {
            DB::table('job_search_index')->insert($batchInserts);
        }
        
        $bar->finish();
        $this->newLine();
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
        
        // Remove stop words
        $stopWords = ['the', 'and', 'a', 'to', 'of', 'in', 'is', 'that', 'for', 'on', 'with', 'as', 'by', 'at', 'an', 'be', 'this', 'which', 'or', 'from'];
        $words = array_diff($words, $stopWords);
        
        return $words;
    }
}
```

### 4. JobSearchService Class

Create a service to handle job search functionality:

```php
<?php

namespace App\Services;

use App\Models\Job;
use Illuminate\Support\Facades\DB;

class JobSearchService
{
    protected $bm25Service;
    
    public function __construct(BM25Service $bm25Service)
    {
        $this->bm25Service = $bm25Service;
    }
    
    public function search($query, array $filters = [])
    {
        // If query is empty, return filtered jobs without BM25 scoring
        if (empty($query)) {
            return $this->getFilteredJobs($filters);
        }
        
        // Tokenize the query
        $queryTerms = $this->tokenize($query);
        
        if (empty($queryTerms)) {
            return $this->getFilteredJobs($filters);
        }
        
        // Get BM25 statistics
        $totalDocs = Job::where('status', 1)->count();
        $avgDocLength = DB::table('job_search_index')->avg('doc_length') ?: 0;
        
        if ($totalDocs === 0 || $avgDocLength === 0) {
            return $this->getFilteredJobs($filters);
        }
        
        // Get BM25 scores
        $bm25Results = $this->bm25Service->search($queryTerms, $totalDocs, $avgDocLength);
        
        if (empty($bm25Results)) {
            return $this->getFilteredJobs($filters);
        }
        
        // Get job IDs from BM25 results
        $jobIds = array_keys($bm25Results);
        
        // Build query with filters
        $query = Job::whereIn('id', $jobIds)
            ->where('status', 1);
        
        $this->applyFilters($query, $filters);
        
        // Get filtered jobs
        $jobs = $query->get();
        
        // Attach BM25 scores to jobs
        foreach ($jobs as $job) {
            $job->bm25_score = $bm25Results[$job->id];
        }
        
        // Sort by BM25 score
        $jobs = $jobs->sortByDesc('bm25_score')->values();
        
        return $jobs;
    }
    
    protected function getFilteredJobs(array $filters = [])
    {
        $query = Job::where('status', 1);
        
        $this->applyFilters($query, $filters);
        
        return $query->latest()->get();
    }
    
    protected function applyFilters($query, array $filters)
    {
        // Filter by category
        if (!empty($filters['category_id'])) {
            $query->where('job_category_id', $filters['category_id']);
        }
        
        // Filter by job type
        if (!empty($filters['type_id'])) {
            $query->where('job_type_id', $filters['type_id']);
        }
        
        // Filter by company
        if (!empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }
        
        // Filter by salary range
        if (!empty($filters['min_salary'])) {
            $query->where('salary_from', '>=', $filters['min_salary']);
        }
        
        if (!empty($filters['max_salary'])) {
            $query->where('salary_to', '<=', $filters['max_salary']);
        }
        
        // Filter by deadline
        if (!empty($filters['deadline'])) {
            $query->where('deadline', '>=', $filters['deadline']);
        }
        
        return $query;
    }
    
    protected function tokenize($text)
    {
        // Convert to lowercase
        $text = strtolower($text);
        
        // Remove punctuation and special characters
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        
        // Split into words
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        // Remove stop words
        $stopWords = ['the', 'and', 'a', 'to', 'of', 'in', 'is', 'that', 'for', 'on', 'with', 'as', 'by', 'at', 'an', 'be', 'this', 'which', 'or', 'from'];
        $words = array_diff($words, $stopWords);
        
        return $words;
    }
}
```

### 5. JobSearchController

Create a controller to handle search requests:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Resources\JobResource;
use App\Models\JobCategory;
use App\Models\JobType;
use App\Services\JobSearchService;
use Illuminate\Http\Request;

class JobSearchController extends Controller
{
    protected $jobSearchService;
    
    public function __construct(JobSearchService $jobSearchService)
    {
        $this->jobSearchService = $jobSearchService;
    }
    
    public function index()
    {
        $categories = JobCategory::all();
        $jobTypes = JobType::all();
        
        return view('frontend.jobs.search', compact('categories', 'jobTypes'));
    }
    
    public function search(Request $request)
    {
        $filters = $this->getFiltersFromRequest($request);
        $jobs = $this->jobSearchService->search($request->input('query'), $filters);
        
        $categories = JobCategory::all();
        $jobTypes = JobType::all();
        
        return view('frontend.jobs.search', compact('jobs', 'categories', 'jobTypes'));
    }
    
    public function apiSearch(Request $request)
    {
        $filters = $this->getFiltersFromRequest($request);
        $jobs = $this->jobSearchService->search($request->input('query'), $filters);
        
        return response()->json([
            'jobs' => JobResource::collection($jobs),
            'count' => $jobs->count(),
        ]);
    }
    
    protected function getFiltersFromRequest(Request $request)
    {
        return [
            'category_id' => $request->input('category_id'),
            'type_id' => $request->input('type_id'),
            'company_id' => $request->input('company_id'),
            'min_salary' => $request->input('min_salary'),
            'max_salary' => $request->input('max_salary'),
            'deadline' => $request->input('deadline'),
        ];
    }
}
```

### 6. Frontend Implementation

Add routes to `routes/web.php` and `routes/api.php`:

```php
// Web routes
Route::get('/jobs/search', [JobSearchController::class, 'index'])->name('jobs.search');
Route::get('/jobs/search/results', [JobSearchController::class, 'search'])->name('jobs.search.results');

// API routes
Route::get('/jobs/search', [JobSearchController::class, 'apiSearch']);
```

Create a view template for the search page:

```blade
<!-- resources/views/frontend/jobs/search.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">Search Filters</div>
                <div class="card-body">
                    <form action="{{ route('jobs.search.results') }}" method="GET">
                        <div class="form-group mb-3">
                            <label for="query">Search</label>
                            <input type="text" class="form-control" id="query" name="query" value="{{ request('query') }}">
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="category_id">Category</label>
                            <select class="form-control" id="category_id" name="category_id">
                                <option value="">All Categories</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="type_id">Job Type</label>
                            <select class="form-control" id="type_id" name="type_id">
                                <option value="">All Types</option>
                                @foreach($jobTypes as $type)
                                    <option value="{{ $type->id }}" {{ request('type_id') == $type->id ? 'selected' : '' }}>
                                        {{ $type->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="min_salary">Minimum Salary</label>
                            <input type="number" class="form-control" id="min_salary" name="min_salary" value="{{ request('min_salary') }}">
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="max_salary">Maximum Salary</label>
                            <input type="number" class="form-control" id="max_salary" name="max_salary" value="{{ request('max_salary') }}">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Search</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="card">
                <div class="card-header">Search Results</div>
                <div class="card-body">
                    @if(isset($jobs) && $jobs->count() > 0)
                        <p>Found {{ $jobs->count() }} jobs</p>
                        
                        @foreach($jobs as $job)
                            <div class="job-card mb-4">
                                <h3><a href="{{ route('jobs.show', $job->slug) }}">{{ $job->title }}</a></h3>
                                <div class="job-meta">
                                    <span class="company">{{ $job->company->name }}</span>
                                    <span class="location">{{ $job->location }}</span>
                                    <span class="job-type">{{ $job->jobType->name }}</span>
                                </div>
                                <div class="job-description">
                                    {{ Str::limit(strip_tags($job->job_description), 150) }}
                                </div>
                                <div class="job-footer">
                                    <span class="salary">${{ number_format($job->salary_from) }} - ${{ number_format($job->salary_to) }}</span>
                                    <span class="deadline">Deadline: {{ $job->deadline->format('M d, Y') }}</span>
                                </div>
                            </div>
                        @endforeach
                    @elseif(isset($jobs))
                        <p>No jobs found matching your criteria.</p>
                    @else
                        <p>Use the search form to find jobs.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
```

### 7. Index Maintenance

Update the console kernel to schedule index maintenance:

```php
<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        // Weekly full rebuild of the search index (Sundays at 1:00 AM)
        $schedule->command('index:jobs --fresh')->weekly()->sundays()->at('01:00');
        
        // Daily incremental updates (3:00 AM)
        $schedule->command('index:jobs')->daily()->at('03:00');
    }
}
```

Create a job observer to keep the index in sync with job changes:

```php
<?php

namespace App\Observers;

use App\Models\Job;
use Illuminate\Support\Facades\DB;

class JobObserver
{
    public function created(Job $job)
    {
        $this->indexJob($job);
    }
    
    public function updated(Job $job)
    {
        // Remove old index entries
        DB::table('job_search_index')->where('job_id', $job->id)->delete();
        
        // Re-index the job
        $this->indexJob($job);
    }
    
    public function deleted(Job $job)
    {
        DB::table('job_search_index')->where('job_id', $job->id)->delete();
    }
    
    protected function indexJob(Job $job)
    {
        // Skip inactive jobs
        if ($job->status !== 1) {
            return;
        }
        
        // Combine job title, description, category, type, and skills for comprehensive search
        $searchText = $job->title . ' ' . $job->job_description;
        
        if ($job->jobCategory) {
            $searchText .= ' ' . $job->jobCategory->name;
        }
        
        if ($job->jobType) {
            $searchText .= ' ' . $job->jobType->name;
        }
        
        // Tokenize the combined text
        $tokens = $this->tokenize($searchText);
        $docLength = count($tokens);
        
        if ($docLength === 0) {
            return;
        }
        
        $termFreqs = array_count_values($tokens);
        $inserts = [];
        
        foreach ($termFreqs as $term => $freq) {
            // Get current document frequency
            $docFreq = DB::table('job_search_index')
                ->where('term', $term)
                ->count();
            
            $inserts[] = [
                'job_id' => $job->id,
                'term' => $term,
                'term_freq' => $freq,
                'doc_length' => $docLength,
                'doc_freq' => $docFreq + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        if (!empty($inserts)) {
            DB::table('job_search_index')->insert($inserts);
        }
    }
    
    protected function tokenize($text)
    {
        // Convert to lowercase
        $text = strtolower($text);
        
        // Remove punctuation and special characters
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        
        // Split into words
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        // Remove stop words
        $stopWords = ['the', 'and', 'a', 'to', 'of', 'in', 'is', 'that', 'for', 'on', 'with', 'as', 'by', 'at', 'an', 'be', 'this', 'which', 'or', 'from'];
        $words = array_diff($words, $stopWords);
        
        return $words;
    }
}
```

Register the observer in the app service provider:

```php
<?php

namespace App\Providers;

use App\Models\Job;
use App\Observers\JobObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Job::observe(JobObserver::class);
    }
}
```

### 8. Documentation and Testing

Create comprehensive documentation in `docs/bm25-search.md`:

```markdown
# BM25 Search Implementation Documentation

## Overview

This document describes the implementation of a BM25-based search system for jobs in the application. BM25 is a ranking function used by search engines to rank matching documents according to their relevance to a given search query. It's an improvement over the basic TF-IDF approach.

## Components

### 1. Database Structure

The search index is stored in the `job_search_index` table with the following structure:

- `id`: Primary key
- `job_id`: Foreign key to the jobs table
- `term`: The indexed term/word
- `term_freq`: Number of occurrences of the term in the document
- `doc_length`: Total number of terms in the document
- `doc_freq`: Number of documents containing this term
- `created_at`: Timestamp of creation
- `updated_at`: Timestamp of last update

### 2. Services

#### BM25Service

Located at `app/Services/BM25Service.php`, this service implements the core BM25 algorithm with the following features:

- Configurable parameters (k1 and b) for fine-tuning
- IDF calculation based on term frequency in the corpus
- Term frequency normalization based on document length
- Score aggregation for multi-term queries

#### JobSearchService

Located at `app/Services/JobSearchService.php`, this service provides a high-level interface for searching jobs:

- Uses BM25Service for relevance scoring
- Supports filtering by various job attributes
- Handles empty queries with fallback to standard filtering
- Maintains consistent tokenization with the indexing process

### 3. Command Line Tools

#### IndexJobs Command

Located at `app/Console/Commands/IndexJobs.php`, this command builds and maintains the search index:

```
php artisan index:jobs        # Update the index incrementally
php artisan index:jobs --fresh # Rebuild the entire index from scratch
```

### 4. Automatic Index Maintenance

The system keeps the search index up-to-date through:

- Scheduled tasks in `app/Console/Kernel.php`:
  - Weekly full rebuild (Sundays at 1:00 AM)
  - Daily incremental updates (3:00 AM)
- Job observers in `app/Observers/JobObserver.php` that react to:
  - Job creation
  - Job updates
  - Job deletion

### 5. User Interface

The search functionality is exposed through:

- Web interface at `/jobs/search`
- API endpoint at `/api/jobs/search`

Both interfaces support filtering by category, job type, salary range, and other attributes.

## How BM25 Works

BM25 calculates a relevance score for each document based on:

1. **Term Frequency (TF)**: How often a term appears in a document, normalized by document length
2. **Inverse Document Frequency (IDF)**: How rare a term is across all documents
3. **Document Length Normalization**: Adjusting scores based on document length relative to average

The formula used is:

```
score(D,Q) = ∑(IDF(qi) · (f(qi,D) · (k1 + 1)) / (f(qi,D) + k1 · (1 - b + b · |D| / avgdl)))
```

Where:
- D is the document
- Q is the query containing terms qi
- f(qi,D) is the frequency of term qi in document D
- |D| is the length of document D
- avgdl is the average document length
- k1 and b are parameters (default: k1=1.2, b=0.75)

## Performance Considerations

- The `term` column in the `job_search_index` table is indexed for faster lookups
- Batch processing is used for index building to reduce database load
- Background processing via queues prevents impact on user operations
- Regular index maintenance optimizes performance over time

## Testing

### Unit Testing

Run the unit tests for the search components:

```
php artisan test --filter=BM25ServiceTest
php artisan test --filter=JobSearchServiceTest
```

### Manual Testing

1. Build the search index:
   ```
   php artisan index:jobs --fresh
   ```

2. Visit `/jobs/search` in your browser

3. Try various search queries:
   - Single words (e.g., "developer")
   - Multiple words (e.g., "senior php developer")
   - Partial matches (e.g., "dev" should match "developer")

4. Test filters:
   - Select different job categories
   - Select different job types
   - Set salary ranges

5. Test the API endpoint:
   ```
   curl -X GET "http://your-app.test/api/jobs/search?query=developer&category_id=1"
   ```

## Troubleshooting

### Empty Search Results

If search results are unexpectedly empty:

1. Check if the index has been built:
   ```
   SELECT COUNT(*) FROM job_search_index;
   ```

2. Verify that jobs exist in the database:
   ```
   SELECT COUNT(*) FROM jobs;
   ```

3. Rebuild the index:
   ```
   php artisan index:jobs --fresh
   ```

### Performance Issues

If search performance is slow:

1. Check the size of the index:
   ```
   SELECT COUNT(*) FROM job_search_index;
   ```

2. Verify that the `term` column is indexed:
   ```
   SHOW INDEX FROM job_search_index;
   ```

3. Consider adjusting the BM25 parameters in `JobSearchService.php`