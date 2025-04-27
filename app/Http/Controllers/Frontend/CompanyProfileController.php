<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\CompanyInfoUpdateRequest;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Traits\FileUploadTrait;
use Illuminate\Http\RedirectResponse;

class CompanyProfileController extends Controller
{
    use FileUploadTrait;
    function index() : View{
        $companyInfo = Company::where('user_id', auth()->user()->id)->first();
        return view('frontend.company-dashboard.profile.index',compact('companyInfo'));
    }
    //

    function updateCompanyInfo(CompanyInfoUpdateRequest $request) : RedirectResponse
    {
        $logoPath = $this->uploadFile($request, 'logo');
        $bannerPath = $this->uploadFile($request, 'banner');

        $data=[];
        if(!empty($logoPath)) $data['logo'] = $logoPath;
        if(!empty($bannerPath)) $data['banner'] = $bannerPath;;
        $data['name'] = $request->name;
        $data['bio'] = $request->bio;
        $data['vision'] = $request->vision;



        Company::updateOrCreate(
            ['user_id' => auth()->user()->id],
            $data
        );
        notify()->success('Updated Successfully', 'Success!');
        return redirect()->back();

    }

    function updateFoundingInfo(Request $request) :RedirectResponse {
        dd($request->all());

        return redirect()->back();

    }
}
