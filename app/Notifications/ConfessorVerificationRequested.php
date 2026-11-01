<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ConfessorVerificationRequested extends Notification
{
    use Queueable;

    public function __construct(public User $priest) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'event' => 'confessor_pending_verification',
            'priest_id' => $this->priest->id,
            'title' => __('ui.new_confessor_pending_title', ['name' => $this->priest->name]),
            'body' => __('ui.new_confessor_pending_body'),
            'url' => route('dashboard.notifications.index'),
        ];
    }
}
