<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LeadStage;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\User;
use App\Support\Db;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeadController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->only('stage', 'type', 'q');

        $leads = $this->query($filters)
            ->with('assignee')
            ->orderByDesc('score')
            ->latest()
            ->simplePaginate(20)
            ->withQueryString();

        return view('admin.leads.index', compact('leads', 'filters'));
    }

    public function show(Lead $lead): View
    {
        return view('admin.leads.show', [
            'lead' => $lead->load(['user', 'assignee']),
            'admins' => User::where('role', Role::Admin->value)->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Lead $lead): RedirectResponse
    {
        $data = $request->validate([
            'stage' => ['required', Rule::in(array_keys(LeadStage::options()))],
            'assigned_to' => ['nullable', Rule::exists('users', 'id')->where('role', Role::Admin->value)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $lead->fill($data);

        if ($lead->isDirty('stage') && $lead->stage === LeadStage::Contacted) {
            $lead->last_contacted_at = now();
        }

        $lead->save();

        return back()->with('status', 'Lead updated.');
    }

    /** CSV download of whatever the current filters show. */
    public function export(Request $request): StreamedResponse
    {
        $filters = $request->only('stage', 'type', 'q');
        $query = $this->query($filters)->with('assignee')->orderByDesc('score');

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'Type', 'Name', 'Email', 'Phone', 'Country', 'Source', 'Stage', 'Score', 'Assigned to', 'Created']);

            $query->chunk(500, function ($leads) use ($out) {
                foreach ($leads as $lead) {
                    fputcsv($out, array_map([self::class, 'safeCell'], [
                        $lead->id, $lead->typeLabel(), $lead->name, $lead->email, $lead->phone, $lead->country,
                        $lead->source, $lead->stage->label(), $lead->score, $lead->assignee?->name,
                        $lead->created_at?->toDateTimeString(),
                    ]));
                }
            });

            fclose($out);
        }, 'talenthub-leads-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Neutralise spreadsheet formula injection in exported text. */
    public static function safeCell(mixed $value): mixed
    {
        if (is_string($value) && $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }

    private function query(array $filters): Builder
    {
        $like = Db::like();

        return Lead::query()
            ->when(! empty($filters['stage']) && LeadStage::tryFrom($filters['stage']), fn (Builder $q) => $q->where('stage', $filters['stage']))
            ->when(! empty($filters['type']) && isset(Lead::TYPES[$filters['type']]), fn (Builder $q) => $q->where('type', $filters['type']))
            ->when(! empty($filters['q']), function (Builder $q) use ($filters, $like) {
                $term = Db::contains($filters['q']);
                $q->where(fn (Builder $w) => $w->where('name', $like, $term)->orWhere('email', $like, $term)->orWhere('phone', $like, $term));
            });
    }
}
