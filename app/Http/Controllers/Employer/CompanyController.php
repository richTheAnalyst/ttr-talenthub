<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function edit(Request $request): View
    {
        return view('employer.company', ['employer' => $request->user()->employer]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:160'],
            'industry' => ['nullable', 'string', 'max:120'],
            'website' => ['nullable', 'url', 'max:190'],
            'location' => ['nullable', 'string', 'max:120'],
            'size' => ['nullable', 'in:1-10,11-50,51-200,201-1000,1000+'],
            'description' => ['nullable', 'string', 'max:3000'],
        ]);

        $request->user()->employer->update($data);

        return back()->with('status', 'Company details saved.');
    }
}
