<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\User;
use App\Support\AppointmentNotifier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppointmentController extends Controller
{
    public function store(Request $request,User $priest)
    {
        abort_unless($priest->isPriest()&&$priest->is_priest_verified,404);$data=$request->validate(['service_type'=>'required|in:confession,christening,marriage','starts_at'=>'required|date|after:now','outside_ceremony'=>'nullable|boolean','message'=>'nullable|required_if:outside_ceremony,1|string|max:2000']);
        $appointment=DB::transaction(function()use($request,$priest,$data){
            $locked=User::whereKey($priest->id)->lockForUpdate()->firstOrFail();$type=$data['service_type'];abort_if($type==='confession'&&(!$locked->is_confessor||!$locked->is_confessor_verified),422,'This priest is not verified to accept confession appointments.');
            $start=Carbon::parse($data['starts_at']);$duration=null;$rule=$locked->availabilities()->where('service_type',$type)->where('weekday',$start->dayOfWeek)->get()->first(function($rule)use($start,$type,&$duration){$from=Carbon::parse($start->toDateString().' '.$rule->starts_at);$until=Carbon::parse($start->toDateString().' '.$rule->ends_at);$step=$type==='confession'?(int)$rule->slot_minutes:60;if($start->second!==0||$start->lt($from)||$start->gte($until)||$from->diffInMinutes($start)%$step!==0||$start->copy()->addMinutes($step)->gt($until))return false;$duration=$step;return true;});
            abort_unless($rule,422,'That time is not an available slot.');abort_if($locked->blockedDates()->containingDate($start->toDateString())->whereIn('service_type',['all',$type])->exists(),422,'That date is unavailable.');$occupied=$locked->appointments()->whereIn('status',['pending','confirmed'])->where('starts_at','<',$start->copy()->addMinutes($duration))->get()->contains(fn($a)=>$a->starts_at->copy()->addMinutes($a->duration_minutes)->gt($start));abort_if($occupied,409,'That time is no longer available.');
            return $locked->appointments()->create(['requester_id'=>$request->user()->id,'service_type'=>$type,'starts_at'=>$start,'duration_minutes'=>$duration,'status'=>'pending','message'=>$data['message']??null]);
        });
        AppointmentNotifier::requested($appointment);
        return response()->json(['data'=>$this->serialize($appointment->load(['priest','requester']))],201);
    }
    public function mine(Request $request) { return response()->json(['data'=>$request->user()->appointmentRequests()->with('priest')->orderByDesc('starts_at')->get()->map(fn($a)=>$this->serialize($a))]); }
    public function priestIndex(Request $request) { abort_unless($request->user()->isPriest(),403);return response()->json(['data'=>$request->user()->appointments()->with('requester')->orderByDesc('starts_at')->get()->map(fn($a)=>$this->serialize($a,true))]); }
    public function respond(Request $request,Appointment $appointment)
    {
        abort_unless($request->user()->isPriest()&&$appointment->priest_id===$request->user()->id,403);
        abort_unless($request->user()->is_priest_verified && $request->user()->approval_status==='approved',403,'Your priest account must be approved by an administrator before responding to appointment requests.');
        $data=$request->validate(['decision'=>'required|in:confirmed,declined','suggested_at'=>'nullable|date|after:now','priest_response'=>'nullable|string|max:2000']);
        if($data['decision']==='confirmed')$appointment->update(['status'=>'confirmed','priest_response'=>$data['priest_response']??null,'suggested_at'=>null]);else $appointment->update(['status'=>'declined','suggested_at'=>$data['suggested_at'],'priest_response'=>$data['priest_response']??null]);
        $appointment->load(['priest','requester']);
        AppointmentNotifier::responded($appointment);
        return response()->json(['data'=>$this->serialize($appointment,true)]);
    }
    private function serialize(Appointment $a,bool $includeRequester=false): array
    {
        $out=['id'=>$a->id,'service_type'=>$a->service_type,'starts_at'=>$a->starts_at->toIso8601String(),'duration_minutes'=>$a->duration_minutes,'status'=>$a->status,'message'=>$a->message,'suggested_at'=>$a->suggested_at?->toIso8601String(),'priest_response'=>$a->priest_response,'priest'=>$a->priest?['id'=>$a->priest->id,'name'=>$a->priest->name,'slug'=>$a->priest->slug]:null];
        if($includeRequester&&$a->requester)$out['requester']=['id'=>$a->requester->id,'name'=>$a->requester->name,'email'=>str_ends_with((string)$a->requester->email,'@sms.priestagenda.invalid')?null:$a->requester->email,'phone'=>$a->requester->phone];
        return $out;
    }
}
