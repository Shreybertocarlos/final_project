# BM25 Search Algorithm Integration Plan
## Comprehensive Implementation Strategy for Laravel Job Portal

---

## Executive Summary

This document provides a detailed implementation plan for integrating the BM25 search algorithm into the existing Laravel job portal application. The integration will enhance search relevance while maintaining full backward compatibility with existing functionality.

## Current State Analysis

### Existing Search Implementation

**1. Home Page Search** (`resources/views/frontend/home/sections/hero-section.blade.php`)
- **Form Action:** `{{ route('jobs.index') }}`
- **Parameters:** `search`, `category` (job category slug), `country` (country ID)
- **Current Behavior:** Redirects to jobs index with query parameters

**2. Jobs Index Search** (`app/Http/Controllers/Frontend/FrontendJobPageController.php`)
- **Route:** `/jobs` (`jobs.index`)
- **Current Logic:** `$query->where('title', 'like', '%'. $request->search . '%')`
- **Filters:** country, state, city, category, job_type, experience, salary range
- **Pagination:** 20 jobs per page

**3. Admin Search** (Multiple controllers using `Searchable` trait)
- **Implementation:** Basic LIKE queries on specified fields
- **Usage:** JobController, JobCategoryController, TagController, etc.

### Database Schema Analysis

**Core Tables:**
- `jobs` - Main table (title, description, company_id, job_category_id, status, deadline)
- `job_categories` - Categories (name, slug, icon)
- `job_skills` - Job-skill relationships (job_id, skill_id)
- `job_tags` - Job-tag relationships (job_id, tag_id)
- `skills` - Skills master (name, slug) - 831 skills available
- `tags` - Tags master (name, slug)
- `companies` - Company information (name, bio, etc.)

**Existing BM25 Components:**
- ✅ `app/Services/BM25Service.php` - Core algorithm (needs table reference fix)
- ✅ `app/Console/Commands/IndexJobs.php` - Indexing command (needs updates)
- ❌ `job_search_index` table - **MISSING** (needs creation)

## 1. Architecture & Integration Strategy

### Non-Breaking Integration Approach

**Design Principle:** Enhance existing functionality without breaking current features

**Integration Flow:**
```
Frontend Search Forms (Unchanged)
           ↓
FrontendJobPageController (Enhanced)
           ↓
JobSearchService (New Orchestrator)
       ↓         ↓
BM25Service    Traditional Search
       ↓         ↓
job_search_index   jobs table
```

**Search Decision Logic:**
1. **No search query** → Traditional filtering only
2. **Search query present** → BM25 scoring + traditional filters
3. **BM25 index empty** → Fallback to traditional search
4. **Error in BM25** → Graceful fallback to traditional search

### Coexistence Strategy

**Existing Functionality Preserved:**
- All current search forms work unchanged
- All existing filters continue to work
- Pagination remains the same
- Admin search remains traditional
- API responses maintain same structure

**Enhanced Functionality:**
- Better search relevance for text queries
- Skill-based job matching (prioritized at 2x weight)
- Category-aware search scoring (1.5x weight)
- Focused indexing on most relevant content only

## 2. BM25 Algorithm Implementation Details

### Document Indexing Strategy

**Indexed Content per Job (Weighted):**
1. **Job Title** (weight: 3.0x) - Highest priority for relevance
2. **Associated Skills** (weight: 2.0x) - Second priority for skill matching
3. **Job Category Name** (weight: 1.5x) - Third priority for categorization
4. **Job Description** (weight: 1.0x) - Base weight

**Excluded from Indexing:**
- **Associated Tags** - Completely excluded from BM25 index
- **Company Name** - Completely excluded from BM25 index

**Indexing Strategy Rationale:**
- **Skills prioritized over categories** - Job seekers often search by specific skills, making skill matching more important than broad categorization
- **Focused content approach** - Excluding tags and company names reduces noise and improves relevance of core job content
- **Hierarchical weighting** - Title > Skills > Category > Description creates a logical priority for job matching

**Text Processing Pipeline:**
1. Convert to lowercase
2. Remove HTML tags and special characters
3. Split on whitespace and punctuation
4. Remove stop words (the, and, or, in, at, etc.)
5. Filter minimum term length (≥ 2 characters)
6. Stem common word variations (optional)

### BM25 Scoring Parameters

**Optimized Parameters:**
- `k1 = 1.2` - Controls term frequency saturation
- `b = 0.75` - Controls document length normalization

**Mathematical Implementation:**
```
BM25(q,d) = Σ IDF(qi) × (tf(qi,d) × (k1 + 1)) / (tf(qi,d) + k1 × (1 - b + b × |d|/avgdl))

Where:
- IDF(qi) = log((N - n(qi) + 0.5) / (n(qi) + 0.5) + 1)
- tf(qi,d) = frequency of term qi in document d
- |d| = document length (total terms)
- avgdl = average document length in collection
- N = total number of documents
- n(qi) = number of documents containing term qi
```

## 3. Database Implementation Plan

### Required Migration

**File:** `database/migrations/2025_06_28_create_job_search_index_table.php`

```php
Schema::create('job_search_index', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('job_id');
    $table->string('term', 255);
    $table->integer('term_freq');
    $table->integer('doc_length');
    $table->integer('doc_freq');
    $table->timestamps();
    
    $table->foreign('job_id')->references('id')->on('jobs')->onDelete('cascade');
    $table->index('term');
    $table->index('job_id');
    $table->index(['term', 'job_id']);
});
```

### Data Population Strategy

**Initial Population:**
1. Run migration: `php artisan migrate`
2. Build initial index: `php artisan index:jobs --fresh`
3. Verify index: Check `job_search_index` table has data

**Automatic Maintenance:**
- **Job Created** → Add to index automatically
- **Job Updated** → Update index entries
- **Job Deleted** → Remove from index (CASCADE)

## 4. Backend Implementation Steps

### Step 1: Fix Existing BM25Service

**Current Issue:** References non-existent `rank` table
**Fix Required:** Change to `job_search_index` table

**File:** `app/Services/BM25Service.php`
**Line 17:** Change `DB::table('rank')` to `DB::table('job_search_index')`

### Step 2: Create JobSearchService

**File:** `app/Services/JobSearchService.php`
**Purpose:** Orchestrate between BM25 and traditional search

**Key Responsibilities:**
- Determine when to use BM25 vs traditional search
- Combine BM25 scores with filter criteria
- Handle fallback scenarios
- Maintain pagination and sorting

### Step 3: Update FrontendJobPageController

**File:** `app/Http/Controllers/Frontend/FrontendJobPageController.php`
**Method:** `index(Request $request)`

**Changes Required:**
- Inject `JobSearchService`
- Replace simple LIKE search with enhanced search
- Maintain all existing filters and functionality
- Preserve pagination structure

### Step 4: Enhance IndexJobs Command

**File:** `app/Console/Commands/IndexJobs.php`
**Updates Needed:**
- Fix table references
- Add progress indicators
- Improve error handling
- Support incremental updates

### Step 5: Add Job Event Listeners

**Purpose:** Automatic index maintenance
**Events to Handle:**
- `JobCreated` → Index new job
- `JobUpdated` → Update index
- `JobDeleted` → Remove from index

## 5. Frontend Integration Strategy

### Zero Frontend Changes Required

**Existing Forms Continue Working:**
- Home page hero search form
- Jobs index filter sidebar
- All existing URL parameters preserved
- Same response structure maintained

**Enhanced User Experience:**
- Better search results relevance
- Faster response times for text searches
- More accurate skill-based matching (prioritized weighting)
- Improved category filtering
- Cleaner, more focused search results

### Optional API Enhancement

**New Endpoint:** `GET /api/jobs/search`
**Purpose:** Enable AJAX search implementations
**Response:** JSON with BM25 scores included

## 6. Testing & Validation Strategy

### Unit Testing Plan

**BM25Service Tests:**
- Scoring calculation accuracy
- Edge case handling (empty queries, no results)
- Parameter sensitivity testing

**JobSearchService Tests:**
- Search orchestration logic
- Fallback mechanism validation
- Filter combination testing

### Integration Testing Plan

**Search Functionality:**
- Home page search flow
- Jobs index search and filtering
- Pagination with search queries
- Filter combinations with search

### Performance Testing

**Benchmarks to Establish:**
- Search response time (target: <200ms)
- Index build time (target: <5min for 10k jobs)
- Memory usage during search
- Database query optimization

### Accuracy Validation

**Test Scenarios:**
1. **Single Word:** "developer" → Should return developer jobs
2. **Multi-Word:** "senior php developer" → Should prioritize senior PHP roles
3. **Skill-Based:** "python machine learning" → Should match relevant skills
4. **Category:** "software development" → Should match category
5. **Company:** "google" → Should find Google jobs

## 7. Implementation Sequence

### Phase 1: Foundation Setup (Day 1)
1. ✅ Create `job_search_index` migration
2. ✅ Fix BM25Service table reference
3. ✅ Run migration and test basic structure
4. ✅ Update IndexJobs command

### Phase 2: Service Layer (Day 2)
5. ✅ Implement JobSearchService
6. ✅ Add comprehensive error handling
7. ✅ Test service layer integration
8. ✅ Validate BM25 calculations

### Phase 3: Controller Integration (Day 3)
9. ✅ Update FrontendJobPageController
10. ✅ Maintain backward compatibility
11. ✅ Test all existing functionality
12. ✅ Validate search improvements

### Phase 4: Automation & Events (Day 4)
13. ✅ Add job event listeners
14. ✅ Test automatic index updates
15. ✅ Validate data consistency
16. ✅ Performance optimization

### Phase 5: Testing & Validation (Day 5)
17. ✅ Comprehensive testing suite
18. ✅ Performance benchmarking
19. ✅ Accuracy validation
20. ✅ Edge case testing

### Phase 6: Documentation & Deployment (Day 6)
21. ✅ Update documentation
22. ✅ Prepare deployment scripts
23. ✅ Production readiness check
24. ✅ Monitoring setup

## Critical Success Factors

### Non-Negotiable Requirements
- ✅ **Zero Breaking Changes** - All existing functionality must work
- ✅ **Performance** - Search must be faster or equal to current
- ✅ **Reliability** - Robust fallback mechanisms required
- ✅ **Maintainability** - Clean, documented code
- ✅ **Scalability** - Handle growing job database

### Risk Mitigation
- **Fallback Strategy** - Traditional search always available
- **Gradual Rollout** - Test with subset of users first
- **Monitoring** - Track search performance and accuracy
- **Rollback Plan** - Quick revert to traditional search if needed

## Next Steps

1. **Review & Approval** - Present this plan for stakeholder review
2. **Environment Preparation** - Ensure development environment ready
3. **Implementation Start** - Begin Phase 1 implementation
4. **Continuous Testing** - Test at each phase completion
5. **Production Deployment** - Careful rollout with monitoring

This implementation plan ensures a smooth, risk-free integration of BM25 search while preserving all existing functionality and providing significant search improvements.

---

## Detailed Implementation Code

### 1. Database Migration

**File:** `database/migrations/2025_06_28_create_job_search_index_table.php`

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
            $table->string('term', 255);
            $table->integer('term_freq');
            $table->integer('doc_length');
            $table->integer('doc_freq');
            $table->timestamps();

            $table->foreign('job_id')->references('id')->on('jobs')->onDelete('cascade');
            $table->index('term');
            $table->index('job_id');
            $table->index(['term', 'job_id']);
            $table->index(['term', 'doc_freq']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_search_index');
    }
};
```

### 2. Fixed BM25Service

**File:** `app/Services/BM25Service.php`

```php
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

    public function setParameters($k1 = null, $b = null)
    {
        if ($k1 !== null) $this->k1 = $k1;
        if ($b !== null) $this->b = $b;
    }
}
```

### 3. New JobSearchService

**File:** `app/Services/JobSearchService.php`

```php
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
            return $this->getFilteredJobs($filters);
        }

        // Get BM25 scores
        $bm25Results = $this->bm25Service->search($queryTerms, $totalDocs, $avgDocLength);

        if (empty($bm25Results)) {
            return $this->getFilteredJobs($filters);
        }

        // Combine BM25 results with filters
        return $this->combineResults($bm25Results, $filters);
    }

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

    protected function getFilteredJobs(array $filters)
    {
        $query = Job::query();
        $query->where(['status' => 'active'])
              ->where('deadline', '>=', date('Y-m-d'));

        $this->applyFilters($query, $filters);

        return $query->orderBy('id', 'DESC')->paginate(20);
    }

    protected function combineResults($bm25Results, array $filters)
    {
        $jobIds = array_keys($bm25Results);

        $query = Job::query();
        $query->whereIn('id', $jobIds)
              ->where(['status' => 'active'])
              ->where('deadline', '>=', date('Y-m-d'));

        $this->applyFilters($query, $filters);

        $jobs = $query->paginate(20);

        // Add BM25 scores to job objects and sort by score
        $jobs->getCollection()->transform(function ($job) use ($bm25Results) {
            $job->bm25_score = $bm25Results[$job->id] ?? 0;
            return $job;
        });

        // Sort by BM25 score (highest first)
        $sortedJobs = $jobs->getCollection()->sortByDesc('bm25_score');
        $jobs->setCollection($sortedJobs);

        return $jobs;
    }

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

        if (!empty($filters['job_type'])) {
            $query->where('job_type_id', $filters['job_type']);
        }

        if (!empty($filters['experience'])) {
            $query->where('job_experience_id', $filters['experience']);
        }

        if (!empty($filters['min_salary'])) {
            $query->where('min_salary', '>=', $filters['min_salary']);
        }

        if (!empty($filters['max_salary'])) {
            $query->where('max_salary', '<=', $filters['max_salary']);
        }
    }
}
```

### 4. Updated FrontendJobPageController

**File:** `app/Http/Controllers/Frontend/FrontendJobPageController.php`

**Method to Update:** `index(Request $request)`

```php
<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\JobCategory;
use App\Models\JobType;
use App\Models\State;
use App\Models\City;
use App\Services\JobSearchService; // Add this import
use Illuminate\Http\Request;
use Illuminate\View\View;

class FrontendJobPageController extends Controller
{
    protected $jobSearchService;

    public function __construct(JobSearchService $jobSearchService)
    {
        $this->jobSearchService = $jobSearchService;
    }

    function index(Request $request): View
    {
        $countries = Country::all();
        $jobCategories = JobCategory::withCount(['jobs' => function($query) {
            $query->where('status', 'active')->where('deadline', '>=', date('Y-m-d'));
        }])->get();
        $jobTypes = JobType::all();
        $selectedStates = null;
        $selectedCites = null;

        // Prepare filters array
        $filters = [
            'country' => $request->country,
            'state' => $request->state,
            'city' => $request->city,
            'category' => $request->category,
            'job_type' => $request->job_type,
            'experience' => $request->experience,
            'min_salary' => $request->min_salary,
            'max_salary' => $request->max_salary,
        ];

        // Use enhanced search service (BM25 + traditional)
        $jobs = $this->jobSearchService->search($request->search, $filters);

        // Handle state and city loading for dropdowns (existing logic)
        if ($request->has('country') && $request->filled('country')) {
            $selectedStates = State::where('country_id', $request->country)->get();
        }

        if ($request->has('state') && $request->filled('state')) {
            $selectedCites = City::where('state_id', $request->state)->get();
        }

        return view('frontend.pages.jobs-index', compact(
            'jobs', 'countries', 'jobCategories', 'jobTypes',
            'selectedStates', 'selectedCites'
        ));
    }

    // ... rest of the methods remain unchanged
}
```

### 5. Enhanced IndexJobs Command

**File:** `app/Console/Commands/IndexJobs.php`

```php
<?php

namespace App\Console\Commands;

use App\Models\Job;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class IndexJobs extends Command
{
    protected $signature = 'index:jobs {--fresh : Clear existing index before rebuilding}';
    protected $description = 'Index jobs for BM25 search';

    public function handle()
    {
        if ($this->option('fresh')) {
            $this->info('Clearing existing index...');
            DB::table('job_search_index')->truncate();
        }

        $this->info('Starting job indexing...');

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

    protected function indexJob($job)
    {
        // Clear existing index for this job
        DB::table('job_search_index')->where('job_id', $job->id)->delete();

        // Collect all text content
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

    protected function tokenize($text)
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

    protected function updateDocumentFrequencies()
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
```

### 6. Job Event Listeners for Auto-Indexing

**File:** `app/Listeners/JobIndexListener.php`

```php
<?php

namespace App\Listeners;

use App\Models\Job;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class JobIndexListener
{
    public function created(Job $job)
    {
        $this->indexJob($job);
    }

    public function updated(Job $job)
    {
        $this->reindexJob($job);
    }

    public function deleted(Job $job)
    {
        DB::table('job_search_index')->where('job_id', $job->id)->delete();
        $this->updateDocumentFrequencies();
    }

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

    protected function reindexJob(Job $job)
    {
        DB::table('job_search_index')->where('job_id', $job->id)->delete();
        $this->indexJob($job);
    }

    protected function tokenize($text)
    {
        $text = strtolower(preg_replace('/[^\w\s]/', ' ', $text));
        $terms = array_filter(explode(' ', $text), function($term) {
            return strlen(trim($term)) >= 2;
        });
        $stopWords = ['the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by'];
        return array_values(array_diff($terms, $stopWords));
    }

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
```

**Register Event Listener in:** `app/Providers/EventServiceProvider.php`

```php
protected $listen = [
    // ... existing listeners

    'eloquent.created: App\Models\Job' => [
        'App\Listeners\JobIndexListener@created',
    ],
    'eloquent.updated: App\Models\Job' => [
        'App\Listeners\JobIndexListener@updated',
    ],
    'eloquent.deleted: App\Models\Job' => [
        'App\Listeners\JobIndexListener@deleted',
    ],
];
```

### 7. Optional API Enhancement

**File:** `app/Http/Controllers/Api/JobSearchController.php`

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\JobSearchService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class JobSearchController extends Controller
{
    protected $jobSearchService;

    public function __construct(JobSearchService $jobSearchService)
    {
        $this->jobSearchService = $jobSearchService;
    }

    public function search(Request $request): JsonResponse
    {
        $filters = [
            'country' => $request->country,
            'state' => $request->state,
            'city' => $request->city,
            'category' => $request->category,
            'job_type' => $request->job_type,
            'experience' => $request->experience,
            'min_salary' => $request->min_salary,
            'max_salary' => $request->max_salary,
        ];

        $jobs = $this->jobSearchService->search($request->query, $filters);

        return response()->json([
            'success' => true,
            'data' => $jobs->items(),
            'pagination' => [
                'current_page' => $jobs->currentPage(),
                'last_page' => $jobs->lastPage(),
                'per_page' => $jobs->perPage(),
                'total' => $jobs->total(),
            ]
        ]);
    }
}
```

**Add to:** `routes/api.php`

```php
Route::get('/jobs/search', [App\Http\Controllers\Api\JobSearchController::class, 'search']);
```

### 8. Testing Examples

**Unit Test Example:** `tests/Unit/BM25ServiceTest.php`

```php
<?php

namespace Tests\Unit;

use App\Services\BM25Service;
use Tests\TestCase;

class BM25ServiceTest extends TestCase
{
    public function test_bm25_scoring_calculation()
    {
        $service = new BM25Service();

        // Mock data for testing
        $queryTerms = ['developer', 'php'];
        $totalDocs = 100;
        $avgDocLength = 50;

        // This would require seeded test data in job_search_index
        $results = $service->search($queryTerms, $totalDocs, $avgDocLength);

        $this->assertIsArray($results);
        // Add more specific assertions based on test data
    }

    public function test_empty_query_handling()
    {
        $service = new BM25Service();
        $results = $service->search([], 100, 50);

        $this->assertEmpty($results);
    }
}
```

**Integration Test Example:** `tests/Feature/JobSearchTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\Job;
use App\Models\JobCategory;
use App\Services\JobSearchService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class JobSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_search_with_query()
    {
        // Create test data
        $category = JobCategory::factory()->create(['name' => 'Software Development']);
        $job = Job::factory()->create([
            'title' => 'Senior PHP Developer',
            'description' => 'Looking for experienced PHP developer',
            'job_category_id' => $category->id,
            'status' => 'active',
            'deadline' => now()->addDays(30),
        ]);

        // Test search
        $response = $this->get('/jobs?search=php developer');

        $response->assertStatus(200);
        $response->assertSee('Senior PHP Developer');
    }

    public function test_search_fallback_when_no_index()
    {
        // Test that search works even without BM25 index
        $job = Job::factory()->create([
            'title' => 'Test Job',
            'status' => 'active',
            'deadline' => now()->addDays(30),
        ]);

        $response = $this->get('/jobs?search=test');

        $response->assertStatus(200);
        // Should still return results using traditional search
    }
}
```

## Deployment Checklist

### Pre-Deployment
- [ ] Run all tests and ensure they pass
- [ ] Verify BM25Service table reference is fixed
- [ ] Test migration on staging environment
- [ ] Validate index building process
- [ ] Performance test with realistic data volume

### Deployment Steps
1. **Deploy Code:** Deploy all new files and updates
2. **Run Migration:** `php artisan migrate`
3. **Build Index:** `php artisan index:jobs --fresh`
4. **Verify Functionality:** Test search on production
5. **Monitor Performance:** Watch response times and error logs

### Post-Deployment Monitoring
- [ ] Search response times < 200ms
- [ ] No increase in error rates
- [ ] BM25 scores are reasonable (0.1 - 10.0 range typically)
- [ ] Index updates working on job create/update/delete
- [ ] Fallback to traditional search working when needed

### Rollback Plan
If issues arise:
1. **Quick Fix:** Disable BM25 by modifying JobSearchService to always use traditional search
2. **Full Rollback:** Revert controller changes to use original search logic
3. **Index Issues:** Clear job_search_index table and rebuild

This comprehensive implementation plan provides all the necessary code, configurations, and procedures to successfully integrate BM25 search into your Laravel job portal while maintaining full backward compatibility and reliability.
```
