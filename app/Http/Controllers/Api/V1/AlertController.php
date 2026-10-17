<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\JobType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Presenter;
use App\Models\JobAlert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AlertController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user()->jobAlerts()->latest()->get()->map(fn (JobAlert $a) => Presenter::alert($a))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'keywords' => ['nullable', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:120'],
            'job_type' => ['nullable', Rule::in(array_keys(JobType::options()))],
            'min_match' => ['nullable', 'integer', 'min:0', 'max:100'],
            'frequency' => ['nullable', Rule::in(['instant', 'daily'])],
        ]);

        if ($request->user()->jobAlerts()->count() >= 10) {
            throw ValidationException::withMessages(['keywords' => 'You can have up to 10 alerts.']);
        }

        $alert = $request->user()->jobAlerts()->create($data + [
            'min_match' => 50,
            'frequency' => 'instant',
            'is_active' => true,
        ]);

        return response()->json(['data' => Presenter::alert($alert)], 201);
    }

    public function destroy(Request $request, JobAlert $alert): Response
    {
        abort_unless($alert->user_id === $request->user()->id, 403);

        $alert->delete();

        return response()->noContent();
    }
}
