<?php

namespace App\Services;

use App\Jobs\SyncLeadToCrm;
use App\Models\Lead;
use App\Models\User;

/**
 * The single entry point for creating or refreshing a lead. Web forms, the API
 * and registration all go through here so that every lead is scored,
 * de-duplicated and queued for CRM sync the same way.
 */
class LeadService
{
    public function __construct(private LeadScorer $scorer)
    {
    }

    /**
     * @param  array{name:string,email?:?string,phone?:?string,country?:?string,source?:?string,details?:array}  $data
     * @param  array<string,mixed>  $signals  scoring inputs (see LeadScorer)
     */
    public function capture(string $type, array $data, array $signals = [], ?User $user = null): Lead
    {
        $email = ! empty($data['email']) ? mb_strtolower(trim($data['email'])) : null;

        $signals += [
            'has_email' => $email !== null,
            'has_phone' => ! empty($data['phone']),
            'registered' => $user !== null,
            'source' => $data['source'] ?? 'web',
        ];
        $score = $this->scorer->score($type, $signals);

        $existing = null;
        if ($email) {
            $existing = Lead::where('type', $type)
                ->where('email', $email)
                ->whereNotIn('stage', ['converted', 'lost'])
                ->where('created_at', '>=', now()->subDays((int) config('talenthub.leads.dedupe_days', 30)))
                ->latest()
                ->first();
        }

        if ($existing) {
            $existing->fill(array_filter([
                'name' => $data['name'] ?? null,
                'phone' => $data['phone'] ?? null,
                'country' => $data['country'] ?? null,
                'user_id' => $user?->id,
            ], fn ($v) => $v !== null && $v !== ''));
            $existing->details = array_merge($existing->details ?? [], $data['details'] ?? []);
            $existing->score = max($existing->score, $score);
            $existing->save();
            $lead = $existing;
        } else {
            $lead = Lead::create([
                'type' => $type,
                'name' => $data['name'],
                'email' => $email,
                'phone' => $data['phone'] ?? null,
                'country' => $data['country'] ?? null,
                'source' => $data['source'] ?? 'web',
                'score' => $score,
                'details' => $data['details'] ?? null,
                'user_id' => $user?->id,
            ]);
        }

        SyncLeadToCrm::dispatch($lead->id)->afterCommit();

        return $lead;
    }
}
