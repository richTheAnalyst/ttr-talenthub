<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Services\ApplicationService;
use App\Support\Db;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Admins review applications and accept them. The candidate is notified on every change. */
class ApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $status = ApplicationStatus::tryFrom((string) $request->query('status'))?->value;
        $q = trim((string) $request->query('q'));
        $like = Db::like();

        $applications = Application::query()
            ->with(['candidate:id,name,email,phone', 'job.employer', 'cv', 'interviews'])
            ->when($status, fn (Builder $b) => $b->where('status', $status))
            ->when($q !== '', function (Builder $b) use ($q, $like) {
                $term = Db::contains($q);
                $b->where(fn (Builder $w) => $w
                    ->whereHas('candidate', fn (Builder $u) => $u->where('name', $like, $term)->orWhere('email', $like, $term))
                    ->orWhereHas('job', fn (Builder $j) => $j->where('title', $like, $term)));
            })
            ->latest()
            ->simplePaginate(15)
            ->withQueryString();

        return view('admin.applications', [
            'applications' => $applications,
            'status' => $status,
            'q' => $q,
            'choices' => ApplicationStatus::adminChoices(),
            'counts' => Application::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function update(Request $request, Application $application, ApplicationService $applications): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(ApplicationStatus::adminChoices()))],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $applications->changeStatus($application, ApplicationStatus::from($data['status']), $data['note'] ?? null, $request->user());

        return back()->with('status', 'Application updated. The candidate has been notified.');
    }
}