<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentReminder extends Notification
{
    use Queueable;
    public function __construct(public Appointment $appointment) {}
    public function via($notifiable): array { $channels=['database']; if($notifiable->email&&!str_ends_with($notifiable->email,'@sms.priestagenda.invalid'))$channels[]='mail'; return $channels; }
    public function toMail($notifiable): MailMessage
    {
        $a=$this->appointment;
        return (new MailMessage)->subject(__('ui.appointment_reminder_subject',['name'=>$a->priest->name]))->greeting(__('ui.appointment_reminder_title'))->line(__('ui.appointment_reminder_body',['service'=>__('ui.'.$a->service_type),'time'=>$a->starts_at->translatedFormat('l, F j, Y g:i A'),'parish'=>$a->priest->parish ?: $a->priest->city ?: '']))->action(__('ui.view_appointments'),route('dashboard.index'));
    }
    public function toArray($notifiable): array
    {
        $a=$this->appointment;
        return ['event'=>'appointment_reminder','title'=>__('ui.appointment_reminder_title'),'body'=>__('ui.appointment_reminder_body',['service'=>__('ui.'.$a->service_type),'time'=>$a->starts_at->translatedFormat('l, F j, Y g:i A'),'parish'=>$a->priest->parish ?: $a->priest->city ?: '']),'appointment_id'=>$a->id,'url'=>route('dashboard.index')];
    }
}
