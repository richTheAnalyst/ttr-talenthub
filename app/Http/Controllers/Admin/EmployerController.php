<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employer;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EmployerController extends Controller
{
    public function index(): View
    {
        return view('admin.employers', [
            'employers' => Employer::with('user')->withCount('jobs')->latest()->simplePaginate(20),
        ]);
    }

    public function verify(Employer $employer): RedirectResponse
    {
        $employer->update(['verified_at' => $employer->isVerified() ? null : now()]);

        return back()->with('status', $employer->isVerified()
            ? "{$employer->company_name} is now verified."
            : "Verification removed for {$employer->company_name}.");
    }
}
