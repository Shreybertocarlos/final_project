@extends('frontend.layouts.master')

@section('contents')
<section class="section-box mt-75">
    <div class="breacrumb-cover">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-12">
            <h2 class="mb-20">Blog</h2>
            <ul class="breadcrumbs">
              <li><a class="home-icon" href="index.html">Home</a></li>
              <li>Blog</li>
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
          <div class="content-single">
            <h3 class="mt-0 mb-15 color-brand-1">My Account</h3><a class="font-md color-text-paragraph-2"
              href="#">Update your profile</a>
            <div class="mt-35 mb-40 box-info-profie">
              <div class="image-profile"><img src="assets/imgs/page/candidates/candidate-profile.png" alt="joblist">
              </div><a class="btn btn-apply">Upload Avatar</a><a class="btn btn-link">Delete</a>
            </div>
            <div class="row form-contact">
              <div class="col-md-6">
                <div class="form-group">
                  <label class="font-sm color-text-mutted mb-10">Full Name *</label>
                  <input class="form-control" type="text" value="Steven Job">
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label class="font-sm color-text-mutted mb-10">Email *</label>
                  <input class="form-control" type="text" value="stevenjob@gmail.com">
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label class="font-sm color-text-mutted mb-10">Contact number</label>
                  <input class="form-control" type="text" value="01 - 234 567 89">
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group">
                  <label class="font-sm color-text-mutted mb-10">Personal website</label>
                  <input class="form-control" type="url" value="https://alithemes.com">
                </div>
              </div>

              <div class="col-md-12">
                <div class="form-group">
                  <label class="font-sm color-text-mutted mb-10">Bio</label>
                  <textarea class="form-control"
                    rows="4">We are AliThemes , a creative and dedicated group of individuals who love web development almost as much as we love our customers. We are passionate team with the mission for achieving the perfection in web design.</textarea>
                </div>
              </div>

              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="font-sm color-text-mutted mb-10">Country</label>
                    <input class="form-control" type="text" value="United States">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="font-sm color-text-mutted mb-10">State</label>
                    <input class="form-control" type="text" value="New York">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="font-sm color-text-mutted mb-10">City</label>
                    <input class="form-control" type="text" value="Mcallen">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="font-sm color-text-mutted mb-10">Zip code</label>
                    <input class="form-control" type="text" value="82356">
                  </div>
                </div>
              </div>
              <div class="border-bottom pt-10 pb-10 mb-30"></div>
              <h6 class="color-orange mb-20">Change your password</h6>
              <div class="row">
                <div class="col-lg-6">
                  <div class="form-group">
                    <label class="font-sm color-text-mutted mb-10">Password</label>
                    <input class="form-control" type="password" value="123456789">
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="form-group">
                    <label class="font-sm color-text-mutted mb-10">Re-Password *</label>
                    <input class="form-control" type="password" value="123456789">
                  </div>
                </div>
              </div>
              <div class="border-bottom pt-10 pb-10"></div>
              <div class="box-agree mt-30">
                <label class="lbl-agree font-xs color-text-paragraph-2">
                  <input class="lbl-checkbox" type="checkbox" value="1">Available for freelancers
                </label>
              </div>
              <div class="box-button mt-15">
                <button class="btn btn-apply-big font-md font-bold">Save All Changes</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
@endsection
