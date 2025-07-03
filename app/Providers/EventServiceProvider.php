<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        // Job indexing events (existing)
        'eloquent.created: App\Models\Job' => [
            'App\Listeners\JobIndexListener@created',
        ],
        'eloquent.updated: App\Models\Job' => [
            'App\Listeners\JobIndexListener@updated',
        ],
        'eloquent.deleted: App\Models\Job' => [
            'App\Listeners\JobIndexListener@deleted',
        ],
        // Candidate indexing events (new)
        'eloquent.created: App\Models\Candidate' => [
            'App\Listeners\CandidateIndexListener@created',
        ],
        'eloquent.updated: App\Models\Candidate' => [
            'App\Listeners\CandidateIndexListener@updated',
        ],
        'eloquent.deleted: App\Models\Candidate' => [
            'App\Listeners\CandidateIndexListener@deleted',
        ],
        // Candidate skill events (trigger reindexing)
        'eloquent.created: App\Models\CandidateSkill' => [
            'App\Listeners\CandidateIndexListener@skillChanged',
        ],
        'eloquent.deleted: App\Models\CandidateSkill' => [
            'App\Listeners\CandidateIndexListener@skillChanged',
        ],
        // Candidate experience events (trigger reindexing)
        'eloquent.created: App\Models\CandidateExperience' => [
            'App\Listeners\CandidateIndexListener@experienceChanged',
        ],
        'eloquent.updated: App\Models\CandidateExperience' => [
            'App\Listeners\CandidateIndexListener@experienceChanged',
        ],
        'eloquent.deleted: App\Models\CandidateExperience' => [
            'App\Listeners\CandidateIndexListener@experienceChanged',
        ],
        // Candidate education events (trigger reindexing)
        'eloquent.created: App\Models\CandidateEducation' => [
            'App\Listeners\CandidateIndexListener@educationChanged',
        ],
        'eloquent.updated: App\Models\CandidateEducation' => [
            'App\Listeners\CandidateIndexListener@educationChanged',
        ],
        'eloquent.deleted: App\Models\CandidateEducation' => [
            'App\Listeners\CandidateIndexListener@educationChanged',
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
