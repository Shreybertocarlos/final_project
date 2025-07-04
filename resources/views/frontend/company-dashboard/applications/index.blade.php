@extends('frontend.layouts.master')

@section('contents')
<section class="section-box mt-75">
    <div class="breacrumb-cover">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-12">
                    <h2 class="mb-20">Applications</h2>
                    <ul class="breadcrumbs">
                        <li><a class="home-icon" href="{{ url('/') }}">Home</a></li>
                        <li>Applications</li>
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
                        <h4>{{ $jobTitle->title }}</h4>
                        <a href="{{ route('company.job.applications.rank', $jobTitle->id) }}"
                           class="btn btn-success">
                            <i class="fas fa-sort-amount-down"></i> Rank Applicants
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <tr>
                                    <th>Details</th>
                                    <th>Experience</th>
                                    <th style="width: 20%">Action</th>
                                </tr>
                            <tbody>
                                @forelse ($applications as $application)
                                    <tr>
                                        <td>
                                            <div class="d-flex">

                                                <img  style="width: 50px; height: 50px;; object-fit:cover;" src="{{ asset($application->candidate?->image) }}" alt="">
                                                <br>
                                                <div style="margin-left: 10px">
                                                    <span >{{ $application->candidate->full_name }}</span>
                                                    <br>
                                                    <span>{{ $application->candidate->profession->name }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            {{ $application->candidate->experience->name }}
                                        </td>
                                        <td>
                                            <a href="{{ route('candidates.show', ['slug' => $application->candidate->slug, 'job_id' => $jobTitle->id]) }}" class="btn btn-primary">View Profile</a>
                                        </td>

                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center">No result found!</td>
                                    </tr>
                                @endforelse

                            </tbody>

                            </table>
                        </div>
                    </div>

                    <div class="paginations">
                        <ul class="pager">
                            @if ($applications->hasPages())
                                {{ $applications->withQueryString()->links() }}
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
