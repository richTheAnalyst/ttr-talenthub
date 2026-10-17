<?php

namespace App\Services;

use App\Models\Lead;
use Illuminate\Support\Facades\Http;

/**
 * Pushes leads to an external CRM through a signed webhook.
 *
 * The receiving CRM verifies the X-TalentHub-Signature header: an HMAC-SHA256
 * of the raw request body using TALENTHUB_CRM_WEBHOOK_SECRET.
 */
class CrmClient
{
    public function enabled(): bool
    {
        return filled(config('talenthub.crm.webhook_url'));
    }

    /**
     * @return string|null the CRM's reference for the lead, or null when the webhook is disabled
     *
     * @throws \Illuminate\Http\Client\RequestException on a non-2xx response, so the queue retries
     */
    public function push(Lead $lead): ?string
    {
        if (! $this->enabled()) {
            return null;
        }

        $body = json_encode([
            'event' => 'lead.upserted',
            'sent_at' => now()->toIso8601String(),
            'lead' => [
                'id' => $lead->id,
                'type' => $lead->type,
                'name' => $lead->name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'country' => $lead->country,
                'source' => $lead->source,
                'stage' => $lead->stage->value,
                'score' => $lead->score,
                'details' => $lead->details,
                'crm_reference' => $lead->crm_reference,
                'created_at' => $lead->created_at?->toIso8601String(),
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $response = Http::withBody($body, 'application/json')
            ->withHeaders([
                'X-TalentHub-Signature' => hash_hmac('sha256', $body, (string) config('talenthub.crm.secret')),
                'Accept' => 'application/json',
            ])
            ->timeout((int) config('talenthub.crm.timeout', 10))
            ->post(config('talenthub.crm.webhook_url'));

        $response->throw();

        return (string) ($response->json('reference') ?? $response->json('id') ?? $lead->crm_reference ?? 'lead-'.$lead->id);
    }
}
