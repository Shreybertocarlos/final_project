<?php

namespace App\Http\Controllers\Fronted;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CandidateDashboardController extends Controller
{
    function index() : View{
        return View('frontend.candidate-dashboard.dashboard');
    }
    //
} 
