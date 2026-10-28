<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Enums\LeadStage;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\CandidateProfile;
use App\Models\Cv;
use App\Models\Employer;
use App\Models\HiringRequest;
use App\Models\JobPosting;
use App\Models\Lead;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(\App\Services\WeeklySummary $weekly): View
    {
        $leadsByStage = Lead::selectRaw('stage, count(*) as total')->groupBy('stage')->pluck('total', 'stage');
        $leadsByType = Lead::selectRaw('type, count(*) as total')->groupBy('type')->pluck('total', 'type');
        $appsByStatus = Application::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
           
        $stageRows = [];
        foreach (LeadStage::cases() as $stage) {
            $stageRows[] = ['label' => $stage->label(), 'badge' => $stage->badge(), 'total' => (int) ($leadsByStage[$stage->value] ?? 0), 'value' => $stage->value];
        }

        $statusRows = [];
        foreach (ApplicationStatus::cases() as $status) {
            $statusRows[] = ['label' => $status->label(), 'badge' => $status->badge(), 'total' => (int) ($appsByStatus[$status->value] ?? 0)];
        }

        $typeRows = [];
        foreach (Lead::TYPES as $key => $label) {
            $typeRows[] = ['label' => $label, 'total' => (int) ($leadsByType[$key] ?? 0)];
        }

        return view('admin.dashboard', [
            'stats' => [
                'candidates' => User::where('role', Role::Candidate->value)->count(),
                'employers' => Employer::count(),
                'cvs' => Cv::count(),
                'open_jobs' => JobPosting::open()->count(),
                'applications' => Application::count(),
                'new_leads' => Lead::where('stage', LeadStage::New->value)->count(),
                'new_requests' => HiringRequest::where('status', 'new')->count(),
                'avg_completeness' => (int) round((float) CandidateProfile::avg('completeness')),
            ],
             'week' => $weekly->build(),
            'stageRows' => $stageRows,
            'statusRows' => $statusRows,
            'typeRows' => $typeRows,
            'hotLeads' => Lead::where('stage', LeadStage::New->value)->orderByDesc('score')->limit(5)->get(),
        ]);
    }
}
