<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Support\AppointmentMessenger;
use Illuminate\Http\Request;

class AppointmentMessageController extends Controller
{
    private function authorizeParticipant(Request $request,Appointment $appointment): void { abort_unless(in_array($request->user()->id,[$appointment->priest_id,$appointment->requester_id],true),403); }
    public function index(Request $request,Appointment $appointment)
    {
        $this->authorizeParticipant($request,$appointment);
        $appointment->messages()->whereNull('read_at')->where('sender_id','!=',$request->user()->id)->update(['read_at'=>now()]);
        foreach($request->user()->unreadNotifications as $notification)if(($notification->data['event']??null)==='appointment_message'&&($notification->data['appointment_id']??null)===$appointment->id)$notification->markAsRead();
        return response()->json(['data'=>$appointment->messages()->with('sender')->get()->map(fn($m)=>$this->format($m))]);
    }
    public function store(Request $request,Appointment $appointment)
    {
        $this->authorizeParticipant($request,$appointment);$data=$request->validate(['body'=>'required|string|max:3000']);
        $message=$appointment->messages()->create(['sender_id'=>$request->user()->id,'body'=>$data['body']]);
        $recipient=$request->user()->id===$appointment->priest_id?$appointment->requester:$appointment->priest;
        AppointmentMessenger::notify($message,$recipient);
        return response()->json(['data'=>$this->format($message->load('sender'))],201);
    }
    private function format($message): array { return ['id'=>$message->id,'body'=>$message->body,'sender'=>['id'=>$message->sender->id,'name'=>$message->sender->name,'role'=>$message->sender->role],'read_at'=>$message->read_at?->toIso8601String(),'created_at'=>$message->created_at->toIso8601String()]; }
}
