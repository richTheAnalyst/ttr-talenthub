<?php

namespace App\Http\Controllers;

use App\Models\CandidateProfile;
use App\Models\Employer;
use App\Models\JobPosting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('welcome', [
            'jobs' => JobPosting::open()->with('employer')->latest('published_at')->limit(4)->get(),
            'stats' => [
                'jobs' => JobPosting::open()->count(),
                'employers' => Employer::count(),
                'candidates' => CandidateProfile::count(),
            ],
        ]);
    }

    /** Send a signed-in user to the dashboard for their role. */
    public function dashboard(Request $request): RedirectResponse
    {
        return redirect()->route($request->user()->homeRoute());
    }
}
