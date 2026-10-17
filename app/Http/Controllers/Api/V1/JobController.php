<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Presenter;
use App\Models\Cv;
use App\Models\JobPosting;
use App\Services\ApplicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JobController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'type' => ['nullable', Rule::in(array_keys(\App\Enums\JobType::options()))],
            'location' => ['nullable', 'string', 'max:120'],
        ]);

        $jobs = JobPosting::open()
            ->with(['employer', 'skills'])
            ->filter($request->only('q', 'type', 'location'))
            ->latest('published_at')
            ->simplePaginate(15)
            ->withQueryString()
            ->through(fn (JobPosting $job) => Presenter::job($job));

        return response()->json($jobs);
    }

    public function show(JobPosting $job): JsonResponse
    {
        abort_unless($job->isOpen(), 404);

        $job->load(['employer', 'skills']);

        return response()->json(['data' => Presenter::job($job, detailed: true)]);
    }

    public function apply(Request $request, JobPosting $job, ApplicationService $applications): JsonResponse
    {
        $data = $request->validate([
            'cv_id' => ['nullable', 'integer', Rule::exists('cvs', 'id')->where('user_id', $request->user()->id)],
            'cover_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $cv = ! empty($data['cv_id']) ? Cv::find($data['cv_id']) : null;

        $application = $applications->apply($request->user(), $job, $cv, $data['cover_note'] ?? null);
        $application->load('job.employer');

        return response()->json(['data' => Presenter::application($application)], 201);
    }
}
