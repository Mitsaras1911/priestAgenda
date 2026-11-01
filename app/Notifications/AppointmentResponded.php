<?php
namespace App\Notifications;

use App\Models\Appointment;
use App\Notifications\Channels\TwilioSmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use App\Support\GoogleCalendarLink;
use Illuminate\Notifications\Notification;

class AppointmentResponded extends Notification
{
    use Queueable;

    public function __construct(public Appointment $appointment) {}

    public function via($notifiable): array
    {
        $channels=['database'];
        if($notifiable->email&&!str_ends_with($notifiable->email,'@sms.priestagenda.invalid'))$channels[]='mail';
        if($notifiable->phone&&config('services.twilio.sid')&&config('services.twilio.token')&&config('services.twilio.from'))$channels[]=TwilioSmsChannel::class;
        return $channels;
    }

    public function toMail($notifiable): MailMessage
    {
        $appointment=$this->appointment;
        $mail=(new MailMessage)->subject(__('ui.appointment_response_subject',['name'=>$appointment->priest->name]))->greeting(__('ui.appointment_response_greeting'));
        $mail->line($this->responseText($appointment));
        if($appointment->status==='confirmed') {
            $mail->line(__('ui.add_google_calendar_email'));
            return $mail->action(__('ui.add_google_calendar'),GoogleCalendarLink::forAppointment($appointment));
        }
        return $mail->action(__('ui.view_appointments'),route('dashboard.index'));
    }

    public function toSms($notifiable): string
    {
        $appointment=$this->appointment;
        return $this->responseText($appointment);
    }

    public function toArray($notifiable): array
    {
        $appointment=$this->appointment;
        $confirmed=$appointment->status==='confirmed';
        $body=$this->responseText($appointment);
        return ['event'=>'appointment_responded','title'=>$confirmed?__('ui.appointment_approved_title'):__('ui.appointment_not_approved_title'),'body'=>$body,'appointment_id'=>$appointment->id,'status'=>$appointment->status,'suggested_at'=>$appointment->suggested_at?->toIso8601String(),'priest_response'=>$appointment->priest_response,'url'=>route('dashboard.index')];
    }

    private function responseText(Appointment $appointment): string
    {
        $service=__('ui.'.$appointment->service_type);
        $message=$appointment->priest_response ?: ($appointment->status==='confirmed'
            ? __('ui.appointment_approved_message',['service'=>$service,'time'=>$appointment->starts_at->translatedFormat('l, F j, Y g:i A')])
            : __('ui.appointment_not_approved_message',['service'=>$service]));
        if($appointment->suggested_at)$message.=' '.__('ui.suggested_time_message',['time'=>$appointment->suggested_at->translatedFormat('l, F j, Y g:i A')]);
        return $message;
    }
}
