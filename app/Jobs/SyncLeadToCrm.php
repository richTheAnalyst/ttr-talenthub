<?php

namespace App\Jobs;

use App\Models\Lead;
use App\Services\CrmClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Pushes one lead to the CRM webhook. Retries with a growing delay so a CRM
 * outage never loses a lead or blocks the candidate's request.
 */
class SyncLeadToCrm implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public function __construct(public int $leadId)
    {
    }

    /** @return list<int> seconds between attempts */
    public function backoff(): array
    {
        return [30, 120, 600, 1800];
    }

    public function handle(CrmClient $crm): void
    {
        $lead = Lead::find($this->leadId);

        if (! $lead || ! $crm->enabled()) {
            return;
        }

        $reference = $crm->push($lead);

        $lead->forceFill([
            'crm_synced_at' => now(),
            'crm_reference' => $reference,
        ])->saveQuietly();
    }
}
