<?php
namespace App\Support;

use App\Models\AppointmentMessage;
use App\Models\User;
use App\Notifications\AppointmentMessageSent;
use Illuminate\Support\Facades\Log;
use Throwable;

class AppointmentMessenger
{
    public static function notify(AppointmentMessage $message,User $recipient): void
    {
        try{$recipient->notify(new AppointmentMessageSent($message->loadMissing(['sender','appointment.priest','appointment.requester'])));}
        catch(Throwable $exception){Log::warning('Appointment message notification delivery failed.',['user_id'=>$recipient->id,'message_id'=>$message->id,'error'=>$exception->getMessage()]);}
    }
}
