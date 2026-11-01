<?php
namespace App\Http\Controllers;
use App\Models\Appointment;
use App\Notifications\AppointmentChangeRequested;
use App\Notifications\AppointmentChangeResponded;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class AppointmentChangeController extends Controller
{
    private function assertAvailable(Appointment $appointment, \Carbon\Carbon $start): void
    {
        $priest=$appointment->priest;
        abort_unless($start->isFuture(),422,__('ui.change_only_future'));
        if($appointment->service_type==='confession'&&(!$priest->is_confessor||!$priest->is_confessor_verified)) abort(422,__('ui.priest_verification_pending'));
        if($priest->blockedDates()->containingDate($start->toDateString())->whereIn('service_type',['all',$appointment->service_type])->exists()) abort(422,__('ui.change_slot_unavailable'));
        $rules=$priest->availabilities()->where('service_type',$appointment->service_type)->where('weekday',$start->dayOfWeek)->get();
        $valid=$rules->contains(function($rule)use($start,$appointment){$from=\Carbon\Carbon::parse($start->toDateString().' '.$rule->starts_at);$until=\Carbon\Carbon::parse($start->toDateString().' '.$rule->ends_at);$step=$appointment->service_type==='confession'?(int)$rule->slot_minutes:60;return $start->second===0&&$start->gte($from)&&$start->copy()->addMinutes($appointment->duration_minutes)->lte($until)&&$from->diffInMinutes($start)%$step===0;});
        abort_unless($valid,422,__('ui.change_slot_unavailable'));
        $overlap=Appointment::where('priest_id',$appointment->priest_id)->where('id','!=',$appointment->id)->whereIn('status',['pending','confirmed'])->where('starts_at','<',$start->copy()->addMinutes($appointment->duration_minutes))->get()->contains(fn($other)=>$other->starts_at->copy()->addMinutes($other->duration_minutes)->gt($start));
        abort_if($overlap,422,__('ui.change_slot_taken'));
    }
    public function propose(Request $request,Appointment $appointment)
    {
        abort_unless($request->user()->isPriest()&&$appointment->priest_id===$request->user()->id,403);
        abort_unless($request->user()->is_priest_verified&&$request->user()->approval_status==='approved',403,__('ui.priest_verification_pending'));
        abort_unless(in_array($appointment->status,['pending','confirmed'],true)&&$appointment->starts_at->isFuture(),422,__('ui.change_only_future'));
        abort_if(in_array($appointment->change_request_status,['pending_requester','pending_priest'],true),409,__('ui.change_already_pending'));
        $data=$request->validate(['type'=>'required|in:reschedule,cancel','starts_at'=>'required_if:type,reschedule|nullable|date|after:now','message'=>'nullable|string|max:2000']);
        if($data['type']==='reschedule') $this->assertAvailable($appointment,\Carbon\Carbon::parse($data['starts_at']));
        $appointment->forceFill(['change_request_type'=>$data['type'],'change_requested_by'=>'priest','change_request_status'=>'pending_requester','change_requested_at'=>now(),'change_requested_at_target'=>$data['type']==='reschedule'?$data['starts_at']:null,'change_request_message'=>$data['message']??null,'change_response'=>null])->save();
        $appointment->requester->notify(new AppointmentChangeRequested($appointment->load('requester','priest')));
        return back()->with('success',__('ui.change_proposal_sent'));
    }
    public function respond(Request $request,Appointment $appointment)
    {
        abort_unless($request->user()->id===$appointment->requester_id,403);
        abort_unless($appointment->change_requested_by==='priest'&&$appointment->change_request_status==='pending_requester',404);
        $data=$request->validate(['decision'=>'required|in:accept,reject','response'=>'nullable|string|max:2000']);
        DB::transaction(function()use($appointment,$data){
            Appointment::where('priest_id',$appointment->priest_id)->lockForUpdate()->first();
            $locked=Appointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->change_request_status==='pending_requester',409);
            if($data['decision']==='accept'&&$locked->change_request_type==='reschedule'){$newStart=\Carbon\Carbon::parse($locked->change_requested_at_target);$this->assertAvailable($locked,$newStart);$locked->starts_at=$newStart;}
            elseif($data['decision']==='accept'&&$locked->change_request_type==='cancel'){$locked->status='cancelled';$locked->archived_at=now();}
            $locked->change_request_status=$data['decision']==='accept'?'accepted':'rejected';
            $locked->change_response=$data['response']??($data['decision']==='accept'?__('ui.change_accepted_default'):__('ui.change_rejected_default'));
            $locked->save();
        });
        $appointment->refresh()->load(['priest','requester']);$appointment->priest->notify(new AppointmentChangeResponded($appointment));
        return back()->with('success',__('ui.change_response_sent'));
    }
}
