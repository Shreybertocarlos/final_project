<?php

namespace Database\Seeders;

use App\Models\Job;
use App\Models\Candidate;
use App\Models\AppliedJob;
use Illuminate\Database\Seeder;

class NepalDemoApplicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all demo jobs (excluding existing ones)
        $jobs = Job::where('id', '>', 7)->get(); // Assuming existing jobs have IDs 1-7
        
        // Get all demo candidates (excluding existing ones)
        $candidates = Candidate::where('id', '>', 3)->get(); // Assuming existing candidates have IDs 1-3
        
        if ($jobs->isEmpty() || $candidates->isEmpty()) {
            $this->command->error('No demo jobs or candidates found. Please run job and candidate seeders first.');
            return;
        }

        $totalApplications = 0;

        foreach ($jobs as $job) {
            $applicationCount = $this->getApplicationCount($job);
            $applicants = $this->selectApplicants($candidates, $job, $applicationCount);
            
            foreach ($applicants as $candidate) {
                $this->createApplication($job, $candidate);
                $totalApplications++;
            }
            
            $this->command->info("Created {$applicationCount} applications for job: {$job->title}");
        }

        $this->command->info("Created {$totalApplications} demo applications successfully!");
    }

    private function getApplicationCount($job)
    {
        // Determine application count based on job characteristics
        $baseCount = 5; // Minimum applications
        
        // Popular job titles get more applications
        $popularTitles = ['Laravel Developer', 'React Frontend Developer', 'Data Scientist'];
        if (in_array($job->title, $popularTitles) || str_contains($job->title, 'Developer')) {
            $baseCount += rand(5, 15);
        }
        
        // Featured jobs get more applications
        if ($job->featured) {
            $baseCount += rand(3, 8);
        }
        
        // Jobs in major cities get more applications
        $majorCities = [27, 29, 23]; // Kathmandu, Lalitpur, Bhaktapur
        if (in_array($job->city_id, $majorCities)) {
            $baseCount += rand(2, 6);
        }
        
        // Expired jobs might have fewer applications
        if ($job->status === 'expired') {
            $baseCount = max(3, $baseCount - rand(2, 5));
        }
        
        // Cap the maximum applications
        return min($baseCount, 25);
    }

    private function selectApplicants($candidates, $job, $count)
    {
        // Filter candidates based on job requirements
        $suitableCandidates = $this->filterSuitableCandidates($candidates, $job);
        
        // If not enough suitable candidates, include others
        if ($suitableCandidates->count() < $count) {
            $suitableCandidates = $candidates;
        }
        
        // Randomly select candidates
        $selectedCandidates = $suitableCandidates->random(min($count, $suitableCandidates->count()));
        
        return $selectedCandidates;
    }

    private function filterSuitableCandidates($candidates, $job)
    {
        return $candidates->filter(function ($candidate) use ($job) {
            // Match by profession/title similarity
            $jobTitle = strtolower($job->title);
            $candidateTitle = strtolower($candidate->title);
            
            // Check for keyword matches
            $jobKeywords = $this->extractKeywords($jobTitle);
            $candidateKeywords = $this->extractKeywords($candidateTitle);
            
            $hasMatch = !empty(array_intersect($jobKeywords, $candidateKeywords));
            
            // Experience level compatibility
            $hasCompatibleExperience = $this->isExperienceCompatible($candidate->experience_id, $job->job_experience_id);
            
            // Geographic preference (candidates from same state more likely to apply)
            $sameState = $candidate->state === $job->state_id;
            
            // Return true if any criteria match
            return $hasMatch || $hasCompatibleExperience || $sameState;
        });
    }

    private function extractKeywords($title)
    {
        $keywords = [
            'developer', 'development', 'software', 'web', 'mobile', 'app',
            'data', 'analyst', 'science', 'scientist', 'analytics',
            'designer', 'design', 'ui', 'ux', 'graphic',
            'marketing', 'digital', 'manager', 'management',
            'devops', 'engineer', 'engineering', 'qa', 'quality',
            'frontend', 'backend', 'fullstack', 'full-stack',
            'react', 'laravel', 'php', 'javascript', 'python'
        ];
        
        $foundKeywords = [];
        foreach ($keywords as $keyword) {
            if (str_contains($title, $keyword)) {
                $foundKeywords[] = $keyword;
            }
        }
        
        return $foundKeywords;
    }

    private function isExperienceCompatible($candidateExpId, $jobExpId)
    {
        // Experience ID mapping: 10=Fresher, 11=1Year, 12=2Year, 13=3+Year, 14=5+Year, 15=8+Year
        
        // Candidates can apply to jobs requiring same or lower experience
        if ($candidateExpId >= $jobExpId) {
            return true;
        }
        
        // Freshers can apply to 1-year positions
        if ($candidateExpId == 10 && $jobExpId == 11) {
            return true;
        }
        
        // 1-year experience can apply to 2-year positions
        if ($candidateExpId == 11 && $jobExpId == 12) {
            return true;
        }
        
        return false;
    }

    private function createApplication($job, $candidate)
    {
        // Check if application already exists
        $existingApplication = AppliedJob::where([
            'job_id' => $job->id,
            'candidate_id' => $candidate->user_id
        ])->first();
        
        if ($existingApplication) {
            return; // Skip if already applied
        }
        
        // Determine application date
        $applicationDate = $this->getApplicationDate($job);
        
        AppliedJob::create([
            'job_id' => $job->id,
            'candidate_id' => $candidate->user_id,
            'created_at' => $applicationDate,
            'updated_at' => $applicationDate,
        ]);
    }

    private function getApplicationDate($job)
    {
        $jobCreated = $job->created_at;
        $jobDeadline = $job->deadline;
        
        // Applications should be between job creation and deadline (or now if not expired)
        $maxDate = $job->status === 'expired' ? $jobDeadline : now();
        
        // Random date between job creation and max date
        $daysDiff = $jobCreated->diffInDays($maxDate);
        if ($daysDiff <= 0) {
            return $jobCreated->copy()->addHours(rand(1, 24));
        }
        
        $randomDays = rand(0, $daysDiff);
        return $jobCreated->copy()->addDays($randomDays)->addHours(rand(0, 23));
    }
}
