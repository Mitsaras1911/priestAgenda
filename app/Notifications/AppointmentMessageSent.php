<?php
namespace App\Notifications;

use App\Models\AppointmentMessage;
use App\Notifications\Channels\TwilioSmsChannel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentMessageSent extends Notification
{
    public function __construct(public AppointmentMessage $message) {}
    public function via($notifiable): array
    {
        $channels=['database'];
        if($notifiable->email&&!str_ends_with($notifiable->email,'@sms.priestagenda.invalid'))$channels[]='mail';
        if($notifiable->phone&&config('services.twilio.sid')&&config('services.twilio.token')&&config('services.twilio.from'))$channels[]=TwilioSmsChannel::class;
        return $channels;
    }
    public function toMail($notifiable): MailMessage
    {
        $message=$this->message;$appointment=$message->appointment;
        return (new MailMessage)->subject('New message about your '.$appointment->service_type.' request')->greeting('You have a new message from '.$message->sender->name)->line($message->body)->action('Open conversation',route('dashboard.appointments.messages',$appointment));
    }
    public function toSms($notifiable): string
    {
        $message=$this->message;return 'New message from '.$message->sender->name.' about your '.$message->appointment->service_type.' request. Sign in to PriestAgenda to reply.';
    }
    public function toArray($notifiable): array
    {
        $message=$this->message;$appointment=$message->appointment;
        return ['event'=>'appointment_message','title'=>'New message about '.ucfirst($appointment->service_type),'body'=>$message->sender->name.': '.$message->body,'appointment_id'=>$appointment->id,'message_id'=>$message->id,'url'=>route('dashboard.appointments.messages',$appointment)];
    }
}
