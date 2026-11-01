<?php
namespace App\Notifications;
use App\Models\Appointment;
use App\Notifications\Channels\TwilioSmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
class AppointmentChangeRequested extends Notification
{
    use Queueable;
    public function __construct(public Appointment $appointment) {}
    public function via($notifiable): array {$channels=['database'];if($notifiable->email&&!str_ends_with($notifiable->email,'@sms.priestagenda.invalid'))$channels[]='mail';if($notifiable->phone&&config('services.twilio.sid')&&config('services.twilio.token')&&config('services.twilio.from'))$channels[]=TwilioSmsChannel::class;return $channels;}
    private function message(): string {$a=$this->appointment;$kind=$a->change_request_type==='cancel'?__('ui.cancel'):__('ui.reschedule');$when=$a->change_requested_at_target?\Carbon\Carbon::parse($a->change_requested_at_target)->translatedFormat('l, F j, Y g:i A'):'';return __('ui.change_request_body',['name'=>$a->priest->name,'type'=>$kind]).($when?' '.__('ui.proposed_time').': '.$when.'.':'').($a->change_request_message?' '.$a->change_request_message:'');}
    public function toMail($notifiable): MailMessage {$a=$this->appointment;return (new MailMessage)->subject(__('ui.change_request_subject',['name'=>$a->priest->name]))->greeting(__('ui.change_request_title'))->line($this->message())->action(__('ui.review_change_request'),route('dashboard.notifications.index'));}
    public function toSms($notifiable): string{return $this->message();}
    public function toArray($notifiable): array {$a=$this->appointment;return ['event'=>'appointment_change_requested','title'=>__('ui.change_request_title'),'body'=>$this->message(),'appointment_id'=>$a->id,'url'=>route('dashboard.notifications.index')];}
}
