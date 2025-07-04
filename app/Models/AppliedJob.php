<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppliedJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id', 'candidate_id', 'application_status',
        'status_updated_at', 'status_updated_by', 'notes'
    ];

    protected $casts = [
        'status_updated_at' => 'datetime',
    ];

    // Status constants
    const STATUS_UNDER_REVIEW = 'under_review';
    const STATUS_SHORTLISTED = 'shortlisted';
    const STATUS_CALLED_FOR_INTERVIEW = 'called_for_interview';
    const STATUS_REJECTED = 'rejected';

    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_UNDER_REVIEW => 'Under Review',
            self::STATUS_SHORTLISTED => 'Shortlisted',
            self::STATUS_CALLED_FOR_INTERVIEW => 'Called for Interview',
            self::STATUS_REJECTED => 'Rejected',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return self::getStatusOptions()[$this->application_status] ?? 'Unknown';
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->application_status) {
            self::STATUS_UNDER_REVIEW => 'bg-info',
            self::STATUS_SHORTLISTED => 'bg-success',
            self::STATUS_CALLED_FOR_INTERVIEW => 'bg-warning',
            self::STATUS_REJECTED => 'bg-danger',
            default => 'bg-secondary'
        };
    }

    function job() : BelongsTo {
        return $this->belongsTo(Job::class, 'job_id', 'id');
    }

    function candidate() : BelongsTo {
        return $this->belongsTo(Candidate::class, 'candidate_id', 'user_id');
    }

    function candidateUser() : BelongsTo {
        return $this->belongsTo(User::class, 'candidate_id', 'id');
    }

    function statusUpdatedBy() : BelongsTo {
        return $this->belongsTo(User::class, 'status_updated_by');
    }

    function statusHistory() : HasMany {
        return $this->hasMany(ApplicationStatusHistory::class, 'applied_job_id');
    }
}
