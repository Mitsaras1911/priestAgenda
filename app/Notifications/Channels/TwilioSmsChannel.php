<?php
namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;

class TwilioSmsChannel
{
    public function send($notifiable, Notification $notification): void
    {
        $sid=config('services.twilio.sid');$token=config('services.twilio.token');$from=config('services.twilio.from');
        $to=$notifiable->routeNotificationFor('twilio',$notification) ?: $notifiable->phone;
        if(!$sid||!$token||!$from||!$to)return;
        Http::asForm()->withBasicAuth($sid,$token)->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json",['To'=>$to,'From'=>$from,'Body'=>$notification->toSms($notifiable)])->throw();
    }
}
