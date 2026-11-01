<?php
namespace App\Notifications;

use App\Models\Appointment;
use App\Notifications\Channels\TwilioSmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentRequested extends Notification
{
    use Queueable;

    public function __construct(public Appointment $appointment) {}

    public function via($notifiable): array
    {
        $channels=['database'];
        if($this->hasEmail($notifiable))$channels[]='mail';
        if($notifiable->phone&&config('services.twilio.sid')&&config('services.twilio.token')&&config('services.twilio.from'))$channels[]=TwilioSmsChannel::class;
        return $channels;
    }

    public function toMail($notifiable): MailMessage
    {
        $appointment=$this->appointment;
        $requester=$appointment->requester;
        $mail=(new MailMessage)
            ->subject('New appointment request')
            ->greeting('A new appointment request')
            ->line(ucfirst($appointment->service_type).' requested for '.$appointment->starts_at->format('l, F j, Y \\a\\t g:i A').'.')
            ->line('Requested by '.$requester->name.'.');

        if($requester->email&&!str_ends_with($requester->email,'@sms.priestagenda.invalid'))$mail->line('Email: '.$requester->email);
        if($requester->phone)$mail->line('Phone: '.$requester->phone);

        return $mail->action('Review request',route('dashboard.notifications.index'));
    }

    public function toSms($notifiable): string
    {
        $appointment=$this->appointment;
        $requester=$appointment->requester;
        $contact=$requester->email&&!str_ends_with($requester->email,'@sms.priestagenda.invalid')?$requester->email:$requester->phone;
        return 'New '.ucfirst($appointment->service_type).' request for '.$appointment->starts_at->format('M j, Y g:i A').'. From '.$requester->name.($contact?' ('.$contact.')':'').'. Sign in to PriestAgenda to review.';
    }

    public function toArray($notifiable): array
    {
        $appointment=$this->appointment;
        $requester=$appointment->requester;
        return [
            'event'=>'appointment_requested',
            'title'=>'New '.ucfirst($appointment->service_type).' request',
            'body'=>$requester->name.' requested '.$appointment->service_type.' for '.$appointment->starts_at->format('M j, Y g:i A').'.',
            'appointment_id'=>$appointment->id,
            'service_type'=>$appointment->service_type,
            'starts_at'=>$appointment->starts_at->toIso8601String(),
            'requester_name'=>$requester->name,
            'requester_email'=>$requester->email&&!str_ends_with($requester->email,'@sms.priestagenda.invalid')?$requester->email:null,
            'requester_phone'=>$requester->phone,
            'url'=>route('dashboard.notifications.index'),
        ];
    }

    private function hasEmail($notifiable): bool { return $notifiable->email&&!str_ends_with($notifiable->email,'@sms.priestagenda.invalid'); }
}
