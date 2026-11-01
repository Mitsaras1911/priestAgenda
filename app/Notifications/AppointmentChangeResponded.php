<?php
namespace App\Notifications;
use App\Models\Appointment;
use App\Notifications\Channels\TwilioSmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
class AppointmentChangeResponded extends Notification
{
    use Queueable;
    public function __construct(public Appointment $appointment) {}
    public function via($notifiable): array {$channels=['database'];if($notifiable->email&&!str_ends_with($notifiable->email,'@sms.priestagenda.invalid'))$channels[]='mail';if($notifiable->phone&&config('services.twilio.sid')&&config('services.twilio.token')&&config('services.twilio.from'))$channels[]=TwilioSmsChannel::class;return $channels;}
    public function toMail($notifiable): MailMessage {$a=$this->appointment;return (new MailMessage)->subject(__('ui.change_response_subject'))->greeting(__('ui.appointment_response_greeting'))->line($a->change_response?:__('ui.change_request_status_'.$a->change_request_status))->line(__('ui.appointment_time_label',['time'=>$a->starts_at->translatedFormat('l, F j, Y g:i A')]))->action(__('ui.view_appointments'),route('dashboard.index'));}
    public function toSms($notifiable): string{return $this->appointment->change_response?:__('ui.change_request_status_'.$this->appointment->change_request_status);}
    public function toArray($notifiable): array {$a=$this->appointment;return ['event'=>'appointment_change_responded','title'=>__('ui.change_response_subject'),'body'=>$a->change_response?:__('ui.change_request_status_'.$a->change_request_status),'appointment_id'=>$a->id,'url'=>route('dashboard.index')];}
}
