<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DailyJobDigest extends Notification implements ShouldQueue
{
    use Queueable;

    /** @param  list<array{id:int,title:string,company:string,location:?string,score:int}>  $jobs */
    public function __construct(public array $jobs)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $count = count($this->jobs);

        $mail = (new MailMessage)
            ->subject($count === 1 ? '1 new job matches your alert' : "{$count} new jobs match your alerts")
            ->greeting("Hello {$notifiable->name},");

        foreach (array_slice($this->jobs, 0, 10) as $job) {
            $where = $job['location'] ? ", {$job['location']}" : '';
            $mail->line("{$job['title']} at {$job['company']}{$where} ({$job['score']}% match)");
        }

        return $mail->action('Browse matching jobs', route('candidate.matches'));
    }

    public function toArray(object $notifiable): array
    {
        $count = count($this->jobs);

        return [
            'title' => 'Your daily job digest',
            'body' => $count === 1
                ? "{$this->jobs[0]['title']} at {$this->jobs[0]['company']} matches your alert."
                : "{$count} new jobs match your alerts. Best: {$this->jobs[0]['title']}.",
            'url' => route('candidate.matches', [], false),
        ];
    }
}
