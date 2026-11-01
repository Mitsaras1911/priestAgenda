<?php
namespace App\Http\Controllers;
use App\Models\Appointment;
use App\Support\AppointmentMessenger;
use Illuminate\Http\Request;
class AppointmentConversationController extends Controller
{
    private function authorizeParticipant(Request $request,Appointment $appointment): void { abort_unless(in_array($request->user()->id,[$appointment->priest_id,$appointment->requester_id],true),403); }
    public function show(Request $request,Appointment $appointment)
    {
        $this->authorizeParticipant($request,$appointment);
        $appointment->messages()->whereNull('read_at')->where('sender_id','!=',$request->user()->id)->update(['read_at'=>now()]);
        foreach($request->user()->unreadNotifications as $notification)if(($notification->data['event']??null)==='appointment_message'&&(int)($notification->data['appointment_id']??0)===$appointment->id)$notification->markAsRead();
        $appointment->load(['priest','requester']);$messages=$appointment->messages()->with('sender')->get();
        return view('dashboard.conversation',compact('appointment','messages'));
    }
    public function updates(Request $request,Appointment $appointment)
    {
        $this->authorizeParticipant($request,$appointment);
        $data=$request->validate(['after'=>'nullable|integer|min:0']);
        $messages=$appointment->messages()->with('sender')->where('id','>',$data['after']??0)->orderBy('id')->get();
        $appointment->messages()->whereIn('id',$messages->pluck('id'))->whereNull('read_at')->where('sender_id','!=',$request->user()->id)->update(['read_at'=>now()]);
        return response()->json(['messages'=>$messages->map(fn($m)=>['id'=>$m->id,'sender_id'=>$m->sender_id,'sender'=>$m->sender->name,'body'=>$m->body,'created_at'=>$m->created_at->translatedFormat('M j, Y · g:i A')])]);
    }
    public function store(Request $request,Appointment $appointment)
    {
        $this->authorizeParticipant($request,$appointment);
        $data=$request->validate(['body'=>'required|string|max:3000']);
        $message=$appointment->messages()->create(['sender_id'=>$request->user()->id,'body'=>$data['body']]);
        $recipient=$request->user()->id===$appointment->priest_id?$appointment->requester:$appointment->priest;
        AppointmentMessenger::notify($message,$recipient);
        if($request->expectsJson()) return response()->json(['id'=>$message->id,'sender_id'=>$message->sender_id,'sender'=>$request->user()->name,'body'=>$message->body,'created_at'=>$message->created_at->translatedFormat('M j, Y · g:i A')]);
        return redirect()->route('dashboard.appointments.messages',$appointment)->with('success',__('ui.message_sent'));
    }
}