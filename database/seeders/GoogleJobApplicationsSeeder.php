<?php

namespace Database\Seeders;

use App\Models\Job;
use App\Models\User;
use App\Models\AppliedJob;
use Illuminate\Database\Seeder;

class GoogleJobApplicationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating strategic applications for Google jobs...');

        // Get Google's job postings (original jobs with IDs 2, 3, 7)
        $googleJobs = Job::whereIn('id', [2, 3, 7])->get();

        if ($googleJobs->isEmpty()) {
            $this->command->error('No Google jobs found with IDs 2, 3, 7');
            return;
        }

        $this->command->info('Found Google jobs:');
        foreach ($googleJobs as $job) {
            $this->command->info("  ID: {$job->id} - {$job->title}");
        }

        // Get all Nepal demo candidates (user_id > 14, excluding original candidates)
        $demoCandidates = User::where('role', 'candidate')->where('id', '>', 14)->get();
        $this->command->info("Found {$demoCandidates->count()} demo candidates");

        $applicationsCreated = 0;

        foreach ($googleJobs as $job) {
            $jobApplications = 0;

            foreach ($demoCandidates as $candidate) {
                // Check if application already exists
                $existingApp = AppliedJob::where([
                    'job_id' => $job->id,
                    'candidate_id' => $candidate->id
                ])->first();

                if (!$existingApp) {
                    AppliedJob::create([
                        'job_id' => $job->id,
                        'candidate_id' => $candidate->id,
                        'created_at' => now()->subDays(rand(1, 30)),
                        'updated_at' => now()->subDays(rand(1, 30)),
                    ]);
                    $applicationsCreated++;
                    $jobApplications++;
                }
            }

            // Count total applications for this job
            $totalApps = AppliedJob::where('job_id', $job->id)->count();
            $this->command->info("Job: {$job->title} - New Applications: {$jobApplications}, Total Applications: {$totalApps}");
        }

        $this->command->info("Created {$applicationsCreated} new applications for Google jobs");
        $this->command->info('Strategic applications seeding completed successfully!');
    }
}
