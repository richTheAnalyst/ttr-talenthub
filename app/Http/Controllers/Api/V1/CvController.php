<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Presenter;
use App\Models\Application;
use App\Models\Cv;
use App\Services\CvStorage;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CvController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user()->cvs()->latest()->get()->map(fn (Cv $cv) => Presenter::cv($cv))->values(),
        ]);
    }

    public function store(Request $request, CvStorage $storage, ProfileService $profiles): JsonResponse
    {
        $request->validate(['cv' => CvStorage::rules()]);

        $cv = $storage->store($request->user(), $request->file('cv'));
        $profiles->refreshCompleteness($request->user());

        return response()->json(['data' => Presenter::cv($cv)], 201);
    }

    public function destroy(Request $request, Cv $cv, CvStorage $storage, ProfileService $profiles): Response
    {
        Gate::authorize('delete', $cv);

        $open = Application::where('cv_id', $cv->id)->whereNotIn('status', ['hired', 'rejected', 'withdrawn'])->exists();
        if ($open) {
            throw ValidationException::withMessages(['cv' => 'This CV is attached to an application that is still open.']);
        }

        $storage->delete($cv);
        $profiles->refreshCompleteness($request->user());

        return response()->noContent();
    }
}
