<?php

namespace App\Http\Controllers\Candidate;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Cv;
use App\Services\CvStorage;
use App\Services\ProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CvController extends Controller
{
    public function index(Request $request): View
    {
        return view('candidate.cvs', [
            'cvs' => $request->user()->cvs()->orderByDesc('is_primary')->latest()->get(),
            'max' => (int) config('talenthub.cv.max_per_candidate'),
        ]);
    }

    public function store(Request $request, CvStorage $storage, ProfileService $profiles): RedirectResponse
    {
        $request->validate(['cv' => CvStorage::rules()], [
            'cv.mimes' => 'Upload a PDF, DOC or DOCX file.',
            'cv.max' => 'The file is too large. The limit is '.(int) (config('talenthub.cv.max_kb') / 1024).' MB.',
        ]);

        $storage->store($request->user(), $request->file('cv'));
        $profiles->refreshCompleteness($request->user());

        return back()->with('status', 'CV uploaded.');
    }

    public function primary(Cv $cv, CvStorage $storage): RedirectResponse
    {
        Gate::authorize('update', $cv);

        $storage->makePrimary($cv);

        return back()->with('status', 'Primary CV updated. It will be used for new applications.');
    }

    public function destroy(Request $request, Cv $cv, CvStorage $storage, ProfileService $profiles): RedirectResponse
    {
        Gate::authorize('delete', $cv);

        $final = [ApplicationStatus::Hired->value, ApplicationStatus::Rejected->value, ApplicationStatus::Withdrawn->value];

        $inUse = Application::where('cv_id', $cv->id)->whereNotIn('status', $final)->exists();
        if ($inUse) {
            throw ValidationException::withMessages([
                'cv' => 'This CV is attached to an application that is still open. Keep it until the employer has finished.',
            ]);
        }

        $storage->delete($cv);
        $profiles->refreshCompleteness($request->user());

        return back()->with('status', 'CV deleted.');
    }
}
