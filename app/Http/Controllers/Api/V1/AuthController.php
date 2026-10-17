<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Presenter;
use App\Models\CandidateProfile;
use App\Models\User;
use App\Services\RegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request, RegistrationService $registration): JsonResponse
    {
        $data = $request->validate([
            'role' => ['required', Rule::in(['candidate', 'employer'])],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:30'],
            'company_name' => ['required_if:role,employer', 'nullable', 'string', 'max:160'],
            'interest_type' => ['nullable', Rule::in(array_keys(config('talenthub.interest_types')))],
            'password' => ['required', 'confirmed', Password::defaults()],
            'device_name' => ['nullable', 'string', 'max:60'],
        ]);

        $user = $registration->register($data);

        return response()->json([
            'token' => $user->createToken($data['device_name'] ?? 'mobile')->plainTextToken,
            'user' => Presenter::user($user),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:60'],
        ]);

        $user = User::where('email', mb_strtolower($data['email']))->first();

        if (! $user || ! Hash::check($data['password'], $user->password) || ! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => 'The email or password is wrong, or the account is deactivated.',
            ]);
        }

        return response()->json([
            'token' => $user->createToken($data['device_name'] ?? 'mobile')->plainTextToken,
            'user' => Presenter::user($user),
        ]);
    }

    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $payload = ['user' => Presenter::user($user)];

        if ($user->isCandidate()) {
            $profile = CandidateProfile::with('skills')->firstOrCreate(['user_id' => $user->id]);
            $payload['profile'] = Presenter::profile($profile);
            $payload['cvs'] = $user->cvs()->latest()->get()->map(fn ($cv) => Presenter::cv($cv))->values();
        }

        return response()->json($payload);
    }
}
