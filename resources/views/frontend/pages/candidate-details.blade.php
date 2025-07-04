@extends('frontend.layouts.master')

@section('contents')
<section class="section-box mt-75">
    <div class="breacrumb-cover">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-12">
            <h2 class="mb-20">Candidate Profile</h2>
            <ul class="breadcrumbs">
              <li><a class="home-icon" href="{{ url('/') }}">Home</a></li>
              <li>Candidate Profile</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="section-box-2">
    <div class="container">
      <div class="banner-hero banner-image-single"><img style="width: 150px;height: 150px;object-fit: cover;border-radius: 50%;" src="{{ asset($candidate->image) }}" alt="joblist"></div>
      <div class="box-company-profile">
        <div class="row mt-10">
          <div class="col-lg-8 col-md-12">
            <h5 class="f-18">{{ $candidate->full_name }} <span class="card-location font-regular ml-20">{{ $candidate->candidateCountry->name }}</span></h5>
            <p class="mt-0 font-md color-text-paragraph-2 mb-15">{{ $candidate->title }}</p>

          </div>
          @if ($candidate->cv)
          <div class="col-lg-4 col-md-12 text-lg-end"><a class="btn btn-download-icon btn-apply btn-apply-big"
              href="{{ asset($candidate->cv) }}">Download CV</a></div>
          @endif
        </div>
      </div>

      <div class="border-bottom pt-10 pb-10"></div>
    </div>
  </section>

  <section class="section-box mt-30">
    <div class="container">
      <div class="row">
        <div class="col-lg-8 col-md-12 col-sm-12 col-12">
          <div class="content-single">
            <div class="tab-content">
              <div class="tab-pane fade show active" id="tab-short-bio" role="tabpanel"
                aria-labelledby="tab-short-bio">
                <h4>Biography</h4>
                {!! $candidate->bio !!}
                <p></p>
              </div>

            </div>
          </div>
          <div class="box-related-job content-page   cadidate_details_list">
            <div class="mt-5 mb-5">
                <div class="row">
                    <div class="col-md-12">
                        <h4>Experience</h4>
                        <ul class="timeline">
                            @foreach ($candidate->experiences as $experience)
                            <li>
                                <a href="#" class="float-right">{{ formatDate($experience->start) }} - {{ $experience->currently_working ? 'Current' :  formatDate($experience->end)}}</a>
                                <a href="javascript:;">{{ $experience->designation }}</a> | <span>{{ $experience->department }}</span>

                                <p>{{ $experience->company }}</p>
                                <p>{{ $experience->responsibilities }}</p>
                            </li>
                            @endforeach

                        </ul>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 mt-4">
                        <h4>Education</h4>
                        <ul class="timeline">
                            @foreach ($candidate->educations as $education)
                            <li>
                                <a href="#" class="float-right">{{ formatDate($education->year) }}</a>
                                <a href="javascript:;">{{ $education->level }}</a>

                                <p>{{ $education->degree }}</p>
                                <p>{{ $education->note }}</p>
                            </li>
                            @endforeach

                        </ul>
                    </div>
                </div>
            </div>

          </div>
        </div>
        <div class="col-lg-4 col-md-12 col-sm-12 col-12 pl-40 pl-lg-15 mt-lg-30">
          <div class="sidebar-border">
            <h5 class="f-18">Overview</h5>
            <div class="sidebar-list-job">
              <ul>
                <li>
                  <div class="sidebar-icon-item"><i class="fi-rr-briefcase"></i></div>
                  <div class="sidebar-text-info"><span class="text-description">Experience</span><strong
                      class="small-heading">{{ $candidate->experience->name }}</strong></div>
                </li>
                <li>
                    <div class="sidebar-icon-item"><i class="fi fi-rr-settings-sliders"></i></div>
                    <div class="sidebar-text-info"><span class="text-description">Skills</span>
                    <strong>
                        @foreach ($candidate->skills as $candidateSkill)
                            <p class="badge bg-info text-light">{{ $candidateSkill->skill->name }}</p>
                        @endforeach
                    </strong>
                    </div>
                  </li>
                <li>
                  <div class="sidebar-icon-item"><i class="fi fi-rr-settings-sliders"></i></div>
                  <div class="sidebar-text-info"><span class="text-description">Languages</span><strong
                      class="small-heading">
                        @foreach ($candidate->languages as $candidateLanguage)
                            <p class="badge bg-info text-light">{{ $candidateLanguage->language->name }}</p>
                        @endforeach
                    </strong></div>
                </li>

                <li>
                    <div class="sidebar-icon-item"><i class="fi-rr-marker"></i></div>
                    <div class="sidebar-text-info"><span class="text-description">Profession</span><strong
                        class="small-heading">{{ $candidate->profession->name }}</strong></div>
                </li>

                <li>
                  <div class="sidebar-icon-item"><i class="fi-rr-marker"></i></div>
                  <div class="sidebar-text-info"><span class="text-description">Date of Birth</span><strong
                      class="small-heading">{{ formatDate($candidate->birth_date) }}</strong></div>
                </li>
                <li>
                  <div class="sidebar-icon-item"><i class="fi-rr-time-fast"></i></div>
                  <div class="sidebar-text-info"><span class="text-description">Gender</span><strong
                      class="small-heading">{{ $candidate->gender }}</strong></div>
                </li>
                <li>
                    <div class="sidebar-icon-item"><i class="fi-rr-time-fast"></i></div>
                    <div class="sidebar-text-info"><span class="text-description">Marital Status </span><strong
                        class="small-heading">{{ $candidate->marital_status }}</strong></div>
                  </li>
                  <li>
                    <div class="sidebar-icon-item"><i class="fi-rr-time-fast"></i></div>
                    <div class="sidebar-text-info"><span class="text-description">Website </span><strong
                        class="small-heading"><a href="{{ $candidate->website }}">{{ $candidate->website }}</a></strong></div>
                  </li>
              </ul>
            </div>
            <div class="sidebar-list-job">
              <ul class="ul-disc">
                <li>{{ $candidate->address }} {{ $candidate->candidateCity?->name ? ', '.$candidate->candidateCity?->name : '' }} {{ $candidate->candidateState?->name ? ', '.$candidate->candidateState?->name : ''}} {{ $candidate->candidateCountry?->name ? ', '.$candidate->candidateCountry?->name : ''}}</li>
                <li>Phone: {{ $candidate->phone_one }}</li>
                <li>Phone: {{ $candidate->phone_two }}</li>

                <li>Email: {{ $candidate->email }}</li>
              </ul>
              <div class="mt-30"><a class="btn btn-send-message" href="tomail:{{ $candidate->email }}">Send Message</a></div>

              @if($isCompanyView && $application)
              <div class="mt-30 border-top pt-30">
                  <h6 class="mb-20">Application Management</h6>
                  <div class="application-status-section">
                      <div class="row">
                          <div class="col-md-12">
                              <div class="status-info mb-3">
                                  <small class="text-muted">Current Status:</small><br>
                                  <span class="badge {{ $application->status_badge_class }} status-badge" id="current-status-badge">
                                      {{ $application->status_label }}
                                  </span>
                              </div>
                              <div class="application-date mb-3">
                                  <small class="text-muted">Applied on:</small><br>
                                  <strong>{{ $application->created_at->format('M d, Y') }}</strong>
                              </div>
                          </div>
                      </div>

                      <div class="action-buttons">
                          @if($application->application_status !== 'shortlisted')
                          <button class="btn btn-success btn-sm mb-2 status-action-btn"
                                  data-action="shortlist"
                                  data-status="shortlisted"
                                  data-application-id="{{ $application->id }}">
                              <i class="fas fa-star"></i> Shortlist Candidate
                          </button>
                          @endif

                          @if($application->application_status !== 'called_for_interview')
                          <button class="btn btn-warning btn-sm mb-2 status-action-btn"
                                  data-action="interview"
                                  data-status="called_for_interview"
                                  data-application-id="{{ $application->id }}">
                              <i class="fas fa-phone"></i> Call for Interview
                          </button>
                          @endif

                          @if($application->application_status !== 'rejected')
                          <button class="btn btn-danger btn-sm mb-2 status-action-btn"
                                  data-action="reject"
                                  data-status="rejected"
                                  data-application-id="{{ $application->id }}">
                              <i class="fas fa-times"></i> Reject Application
                          </button>
                          @endif
                      </div>


                  </div>
              </div>
              @endif
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  @if($isCompanyView && $application)
  {{-- Status Update Modal --}}
  <div class="modal fade" id="statusUpdateModal" tabindex="-1">
      <div class="modal-dialog">
          <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title">Update Application Status</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                  <form id="statusUpdateForm">
                      @csrf
                      <input type="hidden" id="applicationId" name="applied_job_id">
                      <input type="hidden" id="newStatus" name="status">

                      <div class="mb-3">
                          <label class="form-label">Action:</label>
                          <p class="fw-bold" id="actionDescription"></p>
                      </div>
                  </form>
              </div>
              <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                  <button type="button" class="btn btn-primary" id="confirmStatusUpdate">Confirm</button>
              </div>
          </div>
      </div>
  </div>
  @endif
@endsection

@if($isCompanyView && $application)
@push('scripts')
<script>
$(document).ready(function() {
    // Prevent form submission
    $('#statusUpdateForm').on('submit', function(e) {
        e.preventDefault();
        return false;
    });

    // Status action button handlers
    $('.status-action-btn').on('click', function(e) {
        e.preventDefault();

        const action = $(this).data('action');
        const status = $(this).data('status');
        const applicationId = $(this).data('application-id');

        // Set modal data
        $('#applicationId').val(applicationId);
        $('#newStatus').val(status);

        // Set action description
        const actionDescriptions = {
            'shortlist': 'Shortlist this candidate for further consideration',
            'interview': 'Call this candidate for an interview',
            'reject': 'Reject this application'
        };

        $('#actionDescription').text(actionDescriptions[action] || 'Update application status');

        // Show modal
        $('#statusUpdateModal').modal('show');
    });

    // Confirm status update
    $('#confirmStatusUpdate').on('click', function() {
        const formData = {
            applied_job_id: $('#applicationId').val(),
            status: $('#newStatus').val(),
            _token: $('meta[name="csrf-token"]').attr('content')
        };

        $.ajax({
            url: '{{ route("company.application.status.update") }}',
            method: 'POST',
            data: formData,
            beforeSend: function() {
                $('#confirmStatusUpdate').prop('disabled', true).text('Updating...');
            },
            success: function(response) {
                if (response.success) {
                    // Update status badge
                    const badgeClasses = {
                        'under_review': 'bg-info',
                        'shortlisted': 'bg-success',
                        'called_for_interview': 'bg-warning',
                        'rejected': 'bg-danger'
                    };

                    $('#current-status-badge')
                        .removeClass('bg-info bg-success bg-warning bg-danger')
                        .addClass(badgeClasses[response.status])
                        .text(response.status_label);

                    // Hide/show appropriate buttons
                    $('.status-action-btn').show();
                    $(`.status-action-btn[data-status="${response.status}"]`).hide();

                    // Close modal and show success message
                    $('#statusUpdateModal').modal('hide');
                    notyf.success(response.message);

                    // Reset form
                    $('#statusUpdateForm')[0].reset();
                } else {
                    notyf.error(response.message);
                }
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors || {};
                Object.values(errors).forEach(errorArray => {
                    errorArray.forEach(error => notyf.error(error));
                });
            },
            complete: function() {
                $('#confirmStatusUpdate').prop('disabled', false).text('Confirm');
            }
        });
    });
});
</script>
@endpush
@endif
