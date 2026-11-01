<?php
namespace App\Support;

use App\Models\Appointment;
use App\Notifications\AppointmentRequested;
use App\Notifications\AppointmentResponded;
use App\Notifications\AppointmentReminder;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

class AppointmentNotifier
{
    public static function requested(Appointment $appointment): void { self::send($appointment->priest,new AppointmentRequested($appointment->loadMissing('requester'))); }
    public static function responded(Appointment $appointment): void { self::send($appointment->requester,new AppointmentResponded($appointment->loadMissing(['priest','requester']))); }
    public static function reminder(Appointment $appointment): void { $appointment->loadMissing(['priest','requester']); self::send($appointment->requester,new AppointmentReminder($appointment)); self::send($appointment->priest,new AppointmentReminder($appointment)); }
    private static function send(User $user,$notification): void
    {
        try { $user->notify($notification); }
        catch(Throwable $exception) { Log::warning('External appointment notification delivery failed.', ['user_id'=>$user->id,'notification'=>get_class($notification),'error'=>$exception->getMessage()]); }
    }
}
