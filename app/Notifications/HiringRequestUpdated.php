<?php

namespace App\Notifications;

use App\Models\HiringRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Tells an employer that TTR changed the status of their hiring request. */
class HiringRequestUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public string $role;
    public int $headcount;
    public string $status;
    public string $statusLabel;

    public function __construct(HiringRequest $request)
    {
        $this->role = (string) $request->role_title;
        $this->headcount = (int) $request->headcount;
        $this->status = (string) $request->status;
        $this->statusLabel = $request->statusLabel();
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    private function headline(): string
    {
        return match ($this->status) {
            'accepted' => 'Your hiring request was accepted',
            'in_progress' => 'We are working on your hiring request',
            'fulfilled' => 'Your hiring request is fulfilled',
            'cancelled' => 'Your hiring request was closed',
            default => 'Your hiring request was updated',
        };
    }

    private function message(): string
    {
        $what = "{$this->role} (x{$this->headcount})";

        return match ($this->status) {
            'accepted' => "TTR has accepted your request for {$what}. An adviser will be in touch shortly.",
            'in_progress' => "TTR is now sourcing candidates for {$what}.",
            'fulfilled' => "Your request for {$what} has been fulfilled.",
            'cancelled' => "Your request for {$what} has been closed.",
            default => "Your request for {$what} is now: {$this->statusLabel}.",
        };
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->headline())
            ->greeting("Hello {$notifiable->name},")
            ->line($this->message())
            ->action('View your hiring requests', route('employer.hiring.index'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->headline(),
            'body' => $this->message(),
            'url' => route('employer.hiring.index', [], false),
        ];
    }
}