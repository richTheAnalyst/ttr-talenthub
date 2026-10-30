<?php

namespace App\Console\Commands;

use App\Models\Interview;
use App\Notifications\InterviewNotice;
use Illuminate\Console\Command;

class SendInterviewReminders extends Command
{
    protected $signature = 'talenthub:interview-reminders';

    protected $description = 'Remind candidates and employers about confirmed interviews in the next 24 hours';

    public function handle(): int
    {
        $sent = 0;

        Interview::query()
            ->where('status', Interview::CONFIRMED)
            ->whereNull('reminder_sent_at')
            ->whereBetween('scheduled_at', [now(), now()->addDay()])
            ->with('application.candidate', 'application.job.employer.user')
            ->get()
            ->each(function (Interview $interview) use (&$sent) {
                $application = $interview->application;

                $application->candidate?->notify(new InterviewNotice($interview, 'reminder', 'candidate'));
                $application->job->employer->user?->notify(new InterviewNotice($interview, 'reminder', 'employer'));

                $interview->forceFill(['reminder_sent_at' => now()])->save();
                $sent++;
            });

        $this->info("Sent reminders for {$sent} interview(s).");

        return self::SUCCESS;
    }
}