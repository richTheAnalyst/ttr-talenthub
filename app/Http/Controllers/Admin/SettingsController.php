<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Notifications\CvSubmitted;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings', [
            'enabled' => Setting::read('cv_ack_enabled', '1') === '1',
            'subject' => Setting::read('cv_ack_subject', CvSubmitted::DEFAULT_SUBJECT),
            'message' => Setting::read('cv_ack_message', CvSubmitted::DEFAULT_MESSAGE),
            'interviewApproval' => Setting::read('interview_approval', '1') === '1',
            'activityAlerts' => Setting::read('admin_activity_alerts', '1') === '1',
            'commissionRate' => Setting::read('commission_rate', '15'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'cv_ack_subject' => ['required', 'string', 'max:150'],
            'cv_ack_message' => ['required', 'string', 'max:2000'],
            'commission_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        Setting::write('cv_ack_enabled', $request->boolean('cv_ack_enabled') ? '1' : '0');
        Setting::write('cv_ack_subject', $data['cv_ack_subject']);
        Setting::write('cv_ack_message', $data['cv_ack_message']);
        Setting::write('interview_approval', $request->boolean('interview_approval') ? '1' : '0');
        Setting::write('admin_activity_alerts', $request->boolean('admin_activity_alerts') ? '1' : '0');
        Setting::write('commission_rate', (string) $data['commission_rate']);

        return back()->with('status', 'Settings saved.');
    }
}