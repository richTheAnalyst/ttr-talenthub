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
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'cv_ack_subject' => ['required', 'string', 'max:150'],
            'cv_ack_message' => ['required', 'string', 'max:2000'],
        ]);

        Setting::write('cv_ack_enabled', $request->boolean('cv_ack_enabled') ? '1' : '0');
        Setting::write('cv_ack_subject', $data['cv_ack_subject']);
        Setting::write('cv_ack_message', $data['cv_ack_message']);

        return back()->with('status', 'Automated response saved.');
    }
}