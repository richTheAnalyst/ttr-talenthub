<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Cv;
use App\Support\Db;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CvController extends Controller
{
    private const TYPES = ['pdf' => 'PDF', 'doc' => 'Word (.doc)', 'docx' => 'Word (.docx)'];

    private const RANGES = ['7' => 'Last 7 days', '30' => 'Last 30 days', '90' => 'Last 90 days'];

    private const SORTS = ['newest' => 'Newest first', 'oldest' => 'Oldest first', 'largest' => 'Largest first', 'name' => 'Candidate A–Z'];

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q'));
        $type = array_key_exists((string) $request->query('type'), self::TYPES) ? (string) $request->query('type') : '';
        $range = array_key_exists((string) $request->query('range'), self::RANGES) ? (string) $request->query('range') : '';
        $sort = array_key_exists((string) $request->query('sort'), self::SORTS) ? (string) $request->query('sort') : 'newest';
        $review = array_key_exists((string) $request->query('review'), Cv::REVIEW_STATUSES) ? (string) $request->query('review') : '';
        $primary = $request->boolean('primary');
        $used = $request->boolean('used');
        $like = Db::like();

        $query = Cv::query()
            ->with('user:id,name,email')
            ->when($q !== '', function (Builder $query) use ($q, $like) {
                $term = Db::contains($q);
                $query->where(fn (Builder $w) => $w
                    ->where('cvs.original_name', $like, $term)
                    ->orWhereHas('user', fn (Builder $u) => $u
                        ->where('name', $like, $term)
                        ->orWhere('email', $like, $term)));
            })
            ->when($type !== '', fn (Builder $b) => $b->where('cvs.original_name', $like, '%.'.$type))
            ->when($range !== '', fn (Builder $b) => $b->where('cvs.created_at', '>=', now()->subDays((int) $range)))
            ->when($review !== '', fn (Builder $b) => $b->where('cvs.review_status', $review))
            ->when($primary, fn (Builder $b) => $b->where('cvs.is_primary', true))
            ->when($used, fn (Builder $b) => $b->whereIn('cvs.id', Application::query()->select('cv_id')->whereNotNull('cv_id')));

        match ($sort) {
            'oldest' => $query->oldest('cvs.created_at'),
            'largest' => $query->orderByDesc('cvs.size')->latest('cvs.id'),
            'name' => $query->orderBy(
                \App\Models\User::select('name')->whereColumn('users.id', 'cvs.user_id')->limit(1)
            )->latest('cvs.id'),
            default => $query->latest('cvs.created_at'),
        };

        // One cheap aggregate query for the summary tiles.
        $row = Cv::query()->selectRaw('count(*) as total, sum(case when is_primary then 1 else 0 end) as primaries, coalesce(sum(size), 0) as bytes')->first();
        $week = Cv::where('created_at', '>=', now()->subDays(7))->count();

        return view('admin.cvs', [
            'cvs' => $query->simplePaginate(20)->withQueryString(),
            'filters' => compact('q', 'type', 'range', 'sort', 'primary', 'used', 'review'),
            'reviewStatuses' => Cv::REVIEW_STATUSES,
            'types' => self::TYPES,
            'ranges' => self::RANGES,
            'sorts' => self::SORTS,
            'summary' => [
                'total' => (int) $row->total,
                'primaries' => (int) $row->primaries,
                'week' => $week,
                'mb' => round(((int) $row->bytes) / 1048576, 1),
            ],
        ]);
    }

    public function review(Request $request, Cv $cv): RedirectResponse
    {
        $data = $request->validate([
            'review_status' => ['nullable', 'in:'.implode(',', array_keys(Cv::REVIEW_STATUSES))],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $cv->update([
            'review_status' => $data['review_status'] ?: null,
            'admin_notes' => $data['admin_notes'] ?: null,
        ]);

        return back()->with('status', 'CV updated.');
    }
}