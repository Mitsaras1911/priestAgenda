<?php
namespace App\Console\Commands;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Console\Command;
class ArchiveAppointmentHistory extends Command
{
    protected $signature='appointments:archive-history';
    protected $description='Move past appointments and their notifications into history.';
    public function handle(): int
    {
        Appointment::where('status','confirmed')->where('starts_at','<',now())->whereNull('archived_at')->chunkById(100,function($items){foreach($items as $a){$a->forceFill(['status'=>'completed','archived_at'=>now()])->save();}});
        Appointment::whereIn('status',['declined','cancelled','completed'])->whereNull('archived_at')->update(['archived_at'=>now()]);
        Appointment::whereNotNull('archived_at')->select(['id','requester_id','priest_id'])->chunkById(100,function($items){foreach($items as $a){DatabaseNotification::where('notifiable_type',User::class)->whereIn('notifiable_id',[$a->requester_id,$a->priest_id])->where('data->appointment_id',$a->id)->whereNull('archived_at')->update(['archived_at'=>now()]);}});
        return self::SUCCESS;
    }
}
