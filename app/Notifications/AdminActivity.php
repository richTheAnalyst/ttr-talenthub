<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/** A plain in-app alert that keeps admins aware of what happens between employers and candidates. */
class AdminActivity extends Notification
{
    public function __construct(public string $title, public string $body, public ?string $url = null)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url ?? route('admin.dashboard', [], false),
        ];
    }
}