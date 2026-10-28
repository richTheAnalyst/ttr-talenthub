<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Monday-morning recap for admins. */
class WeeklySummaryReport extends Notification
{
    /** @param  list<array{label:string,now:int,prev:int,delta:int}>  $rows */
    public function __construct(public array $rows, public int $staleLeads)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    private function line(array $r): string
    {
        $change = $r['delta'] === 0 ? 'no change' : ($r['delta'] > 0 ? '+' : '').$r['delta'].' vs last week';

        return "{$r['label']}: {$r['now']} ({$change})";
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('TalentHub weekly summary')
            ->greeting("Hello {$notifiable->name},")
            ->line('Here is what happened on TalentHub in the last 7 days:');

        foreach ($this->rows as $r) {
            $mail->line($this->line($r));
        }

        if ($this->staleLeads > 0) {
            $mail->line("{$this->staleLeads} lead(s) are still waiting for a first contact.");
        }

        return $mail->action('Open the dashboard', route('admin.dashboard'));
    }

    public function toArray(object $notifiable): array
    {
        $apps = collect($this->rows)->firstWhere('label', 'Applications')['now'] ?? 0;
        $cvs = collect($this->rows)->firstWhere('label', 'CVs uploaded')['now'] ?? 0;

        return [
            'title' => 'Weekly summary',
            'body' => "Last 7 days: {$cvs} new CVs, {$apps} applications.",
            'url' => route('admin.dashboard', [], false),
        ];
    }
}