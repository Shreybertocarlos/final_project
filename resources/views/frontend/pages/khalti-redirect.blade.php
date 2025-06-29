@extends('frontend.layouts.master')

@section('contents')
<section class="section-box mt-50 mb-50">
    <div class="container text-center">
        <div class="row">
            <div class="col-lg-12 text-center mt-40">
                <div class="content-page">
                    <div class="box-redirect">
                        <div class="khalti-logo mb-4">
                            <img src="https://khalti.s3.ap-south-1.amazonaws.com/website/khalti-logo.png"
                                 alt="Khalti" style="height: 60px;">
                        </div>
                        <h3>Redirecting to Khalti Payment</h3>
                        <p>Please wait while we redirect you to Khalti's secure payment page.</p>
                        <div class="payment-info mb-4">
                            <div class="plan-details">
                                <strong>Plan:</strong> {{ session('selected_plan')['label'] ?? 'Premium' }} Plan<br>
                                <strong>Amount:</strong> NPR {{ number_format(session('selected_plan')['price'] ?? 0, 2) }}
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="spinner-border text-success" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                        <div class="mt-4">
                            <a href="{{ route('company.khalti.payment') }}" class="btn btn-primary">
                                <i class="fas fa-credit-card me-2"></i>
                                Continue to Khalti Payment
                            </a>
                        </div>
                        <div class="mt-3">
                            <small class="text-muted">
                                Secure payment powered by Khalti
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Auto-redirect to Khalti payment after 2 seconds
        setTimeout(function() {
            window.location.href = "{{ route('company.khalti.payment') }}";
        }, 2000);
    });
</script>

<style>
    .box-redirect {
        background: #fff;
        border-radius: 15px;
        padding: 50px 40px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        max-width: 600px;
        margin: 0 auto;
        border: 1px solid #f0f0f0;
    }

    .box-redirect h3 {
        color: #1ca774;
        margin-bottom: 20px;
        font-weight: 600;
    }

    .box-redirect p {
        color: #666;
        margin-bottom: 30px;
        font-size: 16px;
    }

    .payment-info {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
        border-left: 4px solid #1ca774;
    }

    .plan-details {
        color: #333;
        font-size: 16px;
        line-height: 1.8;
    }

    .spinner-border {
        width: 3rem;
        height: 3rem;
        border-width: 4px;
    }

    .khalti-logo img {
        filter: drop-shadow(0 2px 10px rgba(0,0,0,0.1));
    }

    .text-muted {
        color: #999 !important;
        font-size: 14px;
    }

    .btn-primary {
        background-color: #1ca774;
        border-color: #1ca774;
        padding: 12px 30px;
        font-weight: 500;
        border-radius: 5px;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        background-color: #158a63;
        border-color: #158a63;
        transform: translateY(-2px);
    }
</style>
@endsection
