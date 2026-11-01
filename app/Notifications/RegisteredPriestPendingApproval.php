<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RegisteredPriestPendingApproval extends Notification
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
            'event' => 'priest_pending_approval',
            'priest_id' => $this->priest->id,
            'title' => __('ui.new_priest_pending_title', ['name' => $this->priest->name]),
            'body' => __('ui.new_priest_pending_body'),
            'url' => route('dashboard.notifications.index'),
        ];
    }
}
