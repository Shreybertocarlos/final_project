<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\CandidateAccountInfoUpdateRequest;
use App\Http\Requests\Frontend\CandidateBasicProfileUpdateRequest;
use App\Http\Requests\Frontend\CandidateProfileInfoUpdateRequest;
use App\Models\Candidate;
use App\Models\CandidateEducation;
use App\Models\CandidateExperience;
use App\Models\CandidateLanguage;
use App\Models\CandidateSkill;
use App\Models\City;
use App\Models\Country;
use App\Models\Experience;
use App\Models\Language;
use App\Models\Profession;
use App\Models\Skill;
use App\Models\State;
use App\Services\Notify;
use App\Traits\FileUploadTrait;
use Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rules;

class CandidateProfileController extends Controller
{
    use FileUploadTrait;

    function index() : View {
        // $candidate = Candidate::with(['skills'])->where('user_id', auth()->user()->id)->first();
        $candidate = Candidate::where('user_id', auth()->user()->id)->first();
        // $candidateExperiences = CandidateExperience::where('candidate_id', $candidate?->id)->orderBy('id', 'DESC')->get();
        // $candidateEducation = CandidateEducation::where('candidate_id', $candidate?->id)->orderBy('id', 'DESC')->get();

        $experiences = Experience::all();
        // $professions = Profession::all();
        // $skills = Skill::all();
        // $languages = Language::all();
        // $countries = Country::all();
        // $states = State::where('country_id', $candidate?->country)->get();
        // $cities = City::where('state_id', $candidate?->state)->get();

        // return view('frontend.candidate-dashboard.profile.index', compact('candidate', 'experiences', 'professions', 'skills', 'languages', 'candidateExperiences', 'candidateEducation', 'countries', 'states', 'cities'));
        return view('frontend.candidate-dashboard.profile.index',compact('candidate','experiences'));
    }
     /** update basic info of candidate profile */
     function basicInfoUpdate(CandidateBasicProfileUpdateRequest $request) : RedirectResponse {
        // handle files
        $imagePath = $this->uploadFile($request, 'profile_picture');
        $cvPath = $this->uploadFile($request, 'cv');

        $data = [];
        if(!empty($imagePath)) $data['image'] = $imagePath;
        if(!empty($cvPath)) $data['cv'] = $cvPath;

        $data['full_name'] = $request->full_name;
        $data['title'] = $request->title;
        $data['experience_id'] = $request->experience_level;
        $data['website'] = $request->website;
        $data['birth_date'] = $request->date_of_birth;

        // updating data
        Candidate::updateOrCreate(
            ['user_id' => auth()->user()->id],
            $data

        );

        // $this->updateProfileStatus();

        Notify::updatedNotification();

        return redirect()->back();
    }



}
