<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\CandidateProfile;
use App\Services\ProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();
        $profile = CandidateProfile::with('skills')->firstOrCreate(['user_id' => $user->id]);

        return view('candidate.profile', [
            'user' => $user,
            'profile' => $profile,
            'skillsText' => $profile->skills->pluck('name')->implode(', '),
        ]);
    }

    public function update(Request $request, ProfileService $profiles): RedirectResponse
    {
        // Unticked checkboxes are absent from the request, so make them explicit.
        $request->merge([
            'consent_marketing' => $request->boolean('consent_marketing'),
            'is_searchable' => $request->boolean('is_searchable'),
        ]);

        $data = $request->validate(ProfileService::rules());

        $profiles->save($request->user(), $data);

        return redirect()->route('candidate.profile.edit')->with('status', 'Profile saved.');
    }
}
