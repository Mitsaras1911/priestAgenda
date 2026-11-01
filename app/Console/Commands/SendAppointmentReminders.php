<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Support\AppointmentNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendAppointmentReminders extends Command
{
    protected $signature = 'appointments:send-reminders';
    protected $description = 'Send reminders for confirmed appointments approximately 24 hours ahead.';

    public function handle(): int
    {
        $from=now()->addHours(23);
        $to=now()->addHours(25);
        Appointment::with(['priest','requester'])
            ->where('status','confirmed')->whereNull('reminder_sent_at')
            ->whereBetween('starts_at',[$from,$to])->orderBy('id')->chunkById(100,function($appointments){
                foreach($appointments as $appointment) {
                    try {
                        AppointmentNotifier::reminder($appointment);
                        $appointment->forceFill(['reminder_sent_at'=>now()])->save();
                    } catch(Throwable $exception) {
                        Log::warning('Appointment reminder could not be sent.',['appointment_id'=>$appointment->id,'error'=>$exception->getMessage()]);
                    }
                }
            });
        return self::SUCCESS;
    }
}
