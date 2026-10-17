<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Services\LeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Public lead capture for partner sites and the mobile app. Throttled.
 */
class LeadController extends Controller
{
    public function store(Request $request, LeadService $leads): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(Lead::TYPES))],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required_without:phone', 'nullable', 'email:rfc', 'max:190'],
            'phone' => ['required_without:email', 'nullable', 'string', 'max:30'],
            'country' => ['nullable', 'string', 'max:80'],
            'source' => ['nullable', 'string', 'max:30'],
            'details' => ['nullable', 'array'],
            'details.*' => ['nullable', 'string', 'max:500'],
        ]);

        $lead = $leads->capture($data['type'], [
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'country' => $data['country'] ?? null,
            'source' => $data['source'] ?? 'api',
            'details' => $data['details'] ?? [],
        ], [
            'destination' => $data['details']['destination'] ?? null,
            'study_level' => $data['details']['level'] ?? null,
        ]);

        return response()->json(['message' => 'Thanks. We will be in touch.', 'reference' => $lead->id], 201);
    }
}
