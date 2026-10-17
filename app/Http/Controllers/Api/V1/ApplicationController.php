<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Presenter;
use App\Models\Application;
use App\Services\ApplicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ApplicationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $applications = $request->user()->applications()
            ->with('job.employer')
            ->latest('status_changed_at')
            ->simplePaginate(20)
            ->through(fn (Application $a) => Presenter::application($a));

        return response()->json($applications);
    }

    public function show(Application $application): JsonResponse
    {
        Gate::authorize('view', $application);

        $application->load(['job.employer', 'histories']);

        return response()->json([
            'data' => Presenter::application($application) + [
                'timeline' => $application->histories->map(fn ($h) => [
                    'from' => $h->from_status?->value,
                    'to' => $h->to_status->value,
                    'label' => $h->to_status->label(),
                    'note' => $h->note,
                    'at' => $h->created_at?->toIso8601String(),
                ])->values(),
            ],
        ]);
    }

    public function withdraw(Request $request, Application $application, ApplicationService $applications): JsonResponse
    {
        Gate::authorize('withdraw', $application);

        $application = $applications->withdraw($application, $request->user());
        $application->load('job.employer');

        return response()->json(['data' => Presenter::application($application)]);
    }
}
