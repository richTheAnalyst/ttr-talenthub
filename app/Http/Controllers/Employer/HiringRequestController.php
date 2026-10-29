<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\HiringRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HiringRequestController extends Controller
{
    public function index(Request $request): View
    {
        $requests = HiringRequest::where('employer_id', $request->user()->employer->id)
            ->latest()
            ->simplePaginate(15);

        return view('employer.hiring.index', compact('requests'));
    }

    /** The form itself is shared with the public page and posts to hire.store. */
    public function create(Request $request): View
    {
        return view('employer.hiring.create', ['employer' => $request->user()->employer]);
    }
}