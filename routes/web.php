<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Candidate;
use App\Http\Controllers\CvDownloadController;
use App\Http\Controllers\Employer;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PublicJobController;
use Illuminate\Support\Facades\Route;

// ---- Public ---------------------------------------------------------------
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::view('/offline', 'offline')->name('offline');

Route::get('/jobs', [PublicJobController::class, 'index'])->name('jobs.index');
Route::get('/jobs/{job}', [PublicJobController::class, 'show'])->whereNumber('job')->name('jobs.show');

Route::get('/study-abroad', [LeadController::class, 'study'])->name('study.create');
Route::post('/study-abroad', [LeadController::class, 'storeStudy'])->middleware('throttle:forms')->name('study.store');

Route::get('/hire-talent', [LeadController::class, 'hire'])->name('hire.create');
Route::post('/hire-talent', [LeadController::class, 'storeHire'])->middleware('throttle:forms')->name('hire.store');

// ---- Guests ---------------------------------------------------------------
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:forms');

    Route::get('/forgot-password', [PasswordResetController::class, 'showForgot'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])->middleware('throttle:forms')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');
});

// ---- Any signed-in user -----------------------------------------------------
Route::middleware(['auth', 'role:candidate,employer,admin'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [HomeController::class, 'dashboard'])->name('dashboard');

    Route::post('/push/subscribe', [\App\Http\Controllers\PushSubscriptionController::class, 'store'])->middleware('throttle:30,1')->name('push.subscribe');
    Route::post('/push/unsubscribe', [\App\Http\Controllers\PushSubscriptionController::class, 'destroy'])->middleware('throttle:30,1')->name('push.unsubscribe');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::get('/notifications/{id}', [NotificationController::class, 'read'])->name('notifications.read');

    // Authorised per file by CvPolicy
    Route::get('/cv/{cv}', CvDownloadController::class)->whereNumber('cv')->name('cv.download');
});

// Scheduler ping (cron-job.org calls this every minute with the secret token).
Route::get('/cron/{token}', \App\Http\Controllers\CronController::class)->name('cron');

// ---- Candidates ---------------------------------------------------------------
Route::prefix('candidate')->name('candidate.')->middleware(['auth', 'role:candidate'])->group(function () {
    Route::get('/', [Candidate\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/matches', [Candidate\DashboardController::class, 'matches'])->name('matches');

    Route::get('/profile', [Candidate\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [Candidate\ProfileController::class, 'update'])->name('profile.update');

    Route::get('/cvs', [Candidate\CvController::class, 'index'])->name('cvs.index');
    Route::post('/cvs', [Candidate\CvController::class, 'store'])->name('cvs.store');
    Route::post('/cvs/{cv}/primary', [Candidate\CvController::class, 'primary'])->whereNumber('cv')->name('cvs.primary');
    Route::delete('/cvs/{cv}', [Candidate\CvController::class, 'destroy'])->whereNumber('cv')->name('cvs.destroy');

    Route::get('/applications', [Candidate\ApplicationController::class, 'index'])->name('applications.index');
    Route::get('/applications/{application}', [Candidate\ApplicationController::class, 'show'])->whereNumber('application')->name('applications.show');
    Route::post('/jobs/{job}/apply', [Candidate\ApplicationController::class, 'store'])->whereNumber('job')->name('jobs.apply');
    Route::patch('/applications/{application}/withdraw', [Candidate\ApplicationController::class, 'withdraw'])->whereNumber('application')->name('applications.withdraw');
    Route::patch('/interviews/{interview}/respond', [Candidate\InterviewController::class, 'respond'])->whereNumber('interview')->name('interviews.respond');

    Route::get('/saved', [Candidate\SavedJobController::class, 'index'])->name('saved.index');
    Route::post('/jobs/{job}/save', [Candidate\SavedJobController::class, 'toggle'])->whereNumber('job')->name('saved.toggle');

    Route::get('/alerts', [Candidate\JobAlertController::class, 'index'])->name('alerts.index');
    Route::post('/alerts', [Candidate\JobAlertController::class, 'store'])->name('alerts.store');
    Route::patch('/alerts/{alert}', [Candidate\JobAlertController::class, 'toggle'])->whereNumber('alert')->name('alerts.toggle');
    Route::delete('/alerts/{alert}', [Candidate\JobAlertController::class, 'destroy'])->whereNumber('alert')->name('alerts.destroy');
});

// ---- Employers ----------------------------------------------------------------
Route::prefix('employer')->name('employer.')->middleware(['auth', 'role:employer'])->group(function () {
    Route::get('/', [Employer\DashboardController::class, 'index'])->name('dashboard');

    Route::get('/company', [Employer\CompanyController::class, 'edit'])->name('company.edit');
    Route::put('/company', [Employer\CompanyController::class, 'update'])->name('company.update');

    Route::resource('jobs', Employer\JobController::class)->except('show')->whereNumber('job');
    Route::post('/jobs/{job}/close', [Employer\JobController::class, 'close'])->whereNumber('job')->name('jobs.close');

    Route::get('/jobs/{job}/applicants', [Employer\ApplicantController::class, 'index'])->whereNumber('job')->name('jobs.applicants');
    Route::post('/jobs/{job}/invite/{profile}', [Employer\ApplicantController::class, 'invite'])->whereNumber(['job', 'profile'])->name('jobs.invite');
    Route::patch('/applications/{application}/status', [Employer\ApplicantController::class, 'updateStatus'])->whereNumber('application')->name('applications.status');
    Route::post('/applications/{application}/interviews', [Employer\InterviewController::class, 'store'])->whereNumber('application')->name('interviews.store');
    Route::patch('/interviews/{interview}/cancel', [Employer\InterviewController::class, 'cancel'])->whereNumber('interview')->name('interviews.cancel');

    Route::get('/hiring', [Employer\HiringRequestController::class, 'index'])->name('hiring.index');
    Route::get('/hiring/new', [Employer\HiringRequestController::class, 'create'])->name('hiring.create');
});

// ---- Administrators -------------------------------------------------------------
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    Route::get('/leads/export', [Admin\LeadController::class, 'export'])->name('leads.export');
    Route::get('/leads', [Admin\LeadController::class, 'index'])->name('leads.index');
    Route::get('/leads/{lead}', [Admin\LeadController::class, 'show'])->whereNumber('lead')->name('leads.show');
    Route::put('/leads/{lead}', [Admin\LeadController::class, 'update'])->whereNumber('lead')->name('leads.update');

    Route::get('/hiring', [Admin\HiringRequestController::class, 'index'])->name('hiring.index');
    Route::get('/jobs/new', [Admin\JobController::class, 'create'])->name('jobs.create');
    Route::post('/jobs', [Admin\JobController::class, 'store'])->name('jobs.store');
    Route::put('/hiring/{hiringRequest}', [Admin\HiringRequestController::class, 'update'])->whereNumber('hiringRequest')->name('hiring.update');

    Route::get('/users', [Admin\UserController::class, 'index'])->name('users.index');
    Route::patch('/users/{user}/toggle', [Admin\UserController::class, 'toggle'])->whereNumber('user')->name('users.toggle');
    Route::get('/cvs', [Admin\CvController::class, 'index'])->name('cvs.index');

    Route::get('/applications', [Admin\ApplicationController::class, 'index'])->name('applications.index');
    Route::patch('/applications/{application}/status', [Admin\ApplicationController::class, 'update'])->whereNumber('application')->name('applications.update');

    Route::get('/talent', [Admin\TalentController::class, 'index'])->name('talent.index');

    Route::get('/approvals', [Admin\ApprovalController::class, 'index'])->name('approvals.index');
    Route::patch('/approvals/jobs/{job}/approve', [Admin\ApprovalController::class, 'approveJob'])->whereNumber('job')->name('approvals.jobs.approve');
    Route::patch('/approvals/jobs/{job}/reject', [Admin\ApprovalController::class, 'rejectJob'])->whereNumber('job')->name('approvals.jobs.reject');
    Route::patch('/approvals/interviews/{interview}/approve', [Admin\ApprovalController::class, 'approveInterview'])->whereNumber('interview')->name('approvals.interviews.approve');
    Route::patch('/approvals/interviews/{interview}/reject', [Admin\ApprovalController::class, 'rejectInterview'])->whereNumber('interview')->name('approvals.interviews.reject');

    Route::post('/applications/{application}/interviews', [Admin\InterviewController::class, 'store'])->whereNumber('application')->name('interviews.store');
    Route::patch('/interviews/{interview}/cancel', [Admin\InterviewController::class, 'cancel'])->whereNumber('interview')->name('interviews.cancel');

    Route::get('/placements', [Admin\PlacementController::class, 'index'])->name('placements.index');
    Route::get('/earnings', [Admin\EarningsController::class, 'index'])->name('earnings.index');
    Route::patch('/placements/{placement}', [Admin\PlacementController::class, 'update'])->whereNumber('placement')->name('placements.update');
    Route::patch('/cvs/{cv}/review', [Admin\CvController::class, 'review'])->whereNumber('cv')->name('cvs.review');

    Route::get('/settings', [Admin\SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [Admin\SettingsController::class, 'update'])->name('settings.update');


    Route::get('/employers', [Admin\EmployerController::class, 'index'])->name('employers.index');
    Route::patch('/employers/{employer}/verify', [Admin\EmployerController::class, 'verify'])->whereNumber('employer')->name('employers.verify');
});