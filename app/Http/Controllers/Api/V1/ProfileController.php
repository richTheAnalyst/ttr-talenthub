<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Presenter;
use App\Models\Application;
use App\Models\CandidateProfile;
use App\Models\JobPosting;
use App\Services\MatchingService;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function update(Request $request, ProfileService $profiles): JsonResponse
    {
        $data = $request->validate(ProfileService::rules(partial: true));

        $profile = $profiles->save($request->user(), $data);

        return response()->json(['profile' => Presenter::profile($profile)]);
    }

    public function matches(Request $request, MatchingService $matching): JsonResponse
    {
        $user = $request->user();
        $profile = CandidateProfile::with('skills')->firstOrCreate(['user_id' => $user->id]);

        $applied = Application::where('user_id', $user->id)->pluck('job_posting_id')->all();

        $jobs = JobPosting::open()
            ->whereNotIn('id', $applied)
            ->with(['skills', 'employer'])
            ->latest('published_at')
            ->limit(200)
            ->get();

        $rows = $matching->rankJobs($profile, $jobs, (int) config('talenthub.matching.recommend_min_score', 30), 30);

        return response()->json([
            'data' => $rows->map(fn (array $row) => Presenter::job($row['job'], false, $row['score']) + [
                'matched_skills' => $row['matched_skills'],
                'missing_skills' => $row['missing_skills'],
            ])->values(),
        ]);
    }
}
