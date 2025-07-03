@extends('frontend.layouts.master')

@section('contents')
<section class="section-box mt-75">
    <div class="breacrumb-cover">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-12">
                    <h2 class="mb-20">Ranked Applicants</h2>
                    <ul class="breadcrumbs">
                        <li><a class="home-icon" href="{{ url('/') }}">Home</a></li>
                        <li><a href="{{ route('company.job.applications', $job->id) }}">Applications</a></li>
                        <li>Ranked</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section-box mt-120">
    <div class="container">
        <div class="row">
            @include('frontend.company-dashboard.sidebar')
            <div class="col-lg-9 col-md-8 col-sm-12 col-12 mb-50">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h4>{{ $job->title }} - Ranked by Relevance</h4>
                            <small class="text-muted">Candidates ranked using BM25 algorithm based on job requirements</small>
                        </div>
                        <div>
                            <span class="badge bg-info me-2">
                                <i class="fas fa-users"></i> {{ $rankingStats['total_applications'] }} Applications
                            </span>
                            <a href="{{ route('company.job.applications', $job->id) }}"
                               class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-clock"></i> View Chronological
                            </a>
                        </div>
                    </div>



                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 60px;">Rank</th>
                                        <th>Candidate Details</th>
                                        <th style="width: 150px;">Relevance Score</th>
                                        <th style="width: 120px;">Experience</th>
                                        <th style="width: 120px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($rankedApplications as $application)
                                        @php
                                            $isTopCandidate = $application->rank_position <= 3;
                                        @endphp
                                        <tr class="{{ $isTopCandidate ? 'table-success' : '' }}">
                                            <td>
                                                <div class="text-center">
                                                    @if($application->rank_position == 1)
                                                        <span class="badge bg-warning text-dark fs-6">
                                                            <i class="fas fa-trophy"></i> #{{ $application->rank_position }}
                                                        </span>
                                                    @elseif($application->rank_position <= 3)
                                                        <span class="badge bg-success fs-6">
                                                            <i class="fas fa-medal"></i> #{{ $application->rank_position }}
                                                        </span>
                                                    @else
                                                        <span class="badge bg-secondary">
                                                            #{{ $application->rank_position }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img style="width: 50px; height: 50px; object-fit: cover;" 
                                                         src="{{ asset($application->candidate?->image) }}" 
                                                         alt="{{ $application->candidate->full_name }}"
                                                         class="rounded-circle me-3">
                                                    <div>
                                                        <h6 class="mb-1">{{ $application->candidate->full_name }}</h6>
                                                        <small class="text-muted">{{ $application->candidate->profession->name ?? 'N/A' }}</small>
                                                        @if($application->candidate->title)
                                                            <br><small class="text-primary">{{ $application->candidate->title }}</small>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="text-center">
                                                    <div class="progress mb-2" style="height: 8px;">
                                                        @php
                                                            $maxScore = $rankedApplications->first()->bm25_score ?? 1;
                                                            $percentage = $maxScore > 0 ? min(100, ($application->bm25_score / $maxScore) * 100) : 0;
                                                        @endphp
                                                        <div class="progress-bar {{ $isTopCandidate ? 'bg-success' : 'bg-primary' }}" 
                                                             style="width: {{ $percentage }}%"></div>
                                                    </div>
                                                    <small class="fw-bold">{{ number_format($application->bm25_score, 2) }}</small>
                                                    <br><small class="text-muted">{{ number_format($percentage, 1) }}%</small>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-info">
                                                    {{ $application->candidate->experience->name ?? 'N/A' }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <a href="{{ route('candidates.show', $application->candidate->slug) }}" 
                                                   class="btn btn-primary btn-sm">
                                                    <i class="fas fa-eye"></i> View Profile
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4">
                                                <div class="text-muted">
                                                    <i class="fas fa-users fa-3x mb-3"></i>
                                                    <h5>No Applications Found</h5>
                                                    <p>There are no applications for this job yet.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pagination -->
                    @if($rankedApplications->hasPages())
                        <div class="card-footer">
                            <div class="paginations">
                                <ul class="pager">
                                    {{ $rankedApplications->withQueryString()->links() }}
                                </ul>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Help Section -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6><i class="fas fa-info-circle"></i> How Ranking Works</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Ranking Criteria:</h6>
                                <ul class="list-unstyled">
                                    <li><strong>Skills Match (3x weight):</strong> Candidate skills matching job requirements</li>
                                    <li><strong>Job Title (2.5x weight):</strong> Relevance of current/past job titles</li>
                                    <li><strong>Experience (2x weight):</strong> Work experience and responsibilities</li>
                                    <li><strong>Profile Summary (1.5x weight):</strong> Bio and professional summary</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>Understanding Scores:</h6>
                                <ul class="list-unstyled">
                                    <li><strong>Top 3:</strong> Highly relevant candidates</li>
                                    <li><strong>Score:</strong> Higher = better match to job requirements</li>
                                    <li><strong>Progress Bar:</strong> Relative ranking compared to top candidate</li>
                                    <li><strong>Algorithm:</strong> BM25 considers skills, experience, and profile content</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.table th {
    font-weight: 600;
    font-size: 0.9rem;
}
.progress {
    background-color: #e9ecef;
}
.badge {
    font-size: 0.75rem;
}
</style>
@endsection
