<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ProfileService;
use App\Services\RegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $attempt = [
            'email' => mb_strtolower($credentials['email']),
            'password' => $credentials['password'],
            'is_active' => true,
        ];

        if (! Auth::attempt($attempt, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'The email or password is wrong, or the account is deactivated.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function showRegister(Request $request): View
    {
        return view('auth.register', [
            'role' => $request->query('role') === 'employer' ? 'employer' : 'candidate',
            'interest' => in_array($request->query('interest'), array_keys(config('talenthub.interest_types')), true)
                ? $request->query('interest')
                : 'job',
        ]);
    }

    public function register(Request $request, RegistrationService $registration): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', Rule::in(['candidate', 'employer'])],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:30'],
            'company_name' => ['required_if:role,employer', 'nullable', 'string', 'max:160'],
            'interest_type' => ['nullable', Rule::in(array_keys(config('talenthub.interest_types')))],
            'password' => ['required', 'confirmed', Password::defaults()],
            'terms' => ['accepted'],
        ]);

        $user = $registration->register($data);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route($user->homeRoute())
            ->with('status', $user->isEmployer()
                ? 'Your employer account is ready. Add your company details, then post your first job.'
                : 'Welcome to TalentHub. Upload your CV and finish your profile to start getting matches.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
