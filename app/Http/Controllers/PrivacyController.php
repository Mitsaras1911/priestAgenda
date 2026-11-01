<?php
namespace App\Http\Controllers;
use App\Models\Appointment;
use Illuminate\Http\Request;
class PrivacyController extends Controller
{
    public function export(Request $request)
    {
        $user=$request->user();
        $query=$user->isPriest()?Appointment::where('priest_id',$user->id):Appointment::where('requester_id',$user->id);
        $appointments=$query->with(['priest:id,name,email,phone,parish,city','requester:id,name,email,phone','messages.sender:id,name'])->orderByDesc('starts_at')->get();
        $payload=['exported_at'=>now()->toIso8601String(),'account'=>$user->makeHidden(['password','remember_token'])->toArray(),'appointments'=>$appointments->map(fn($a)=>['id'=>$a->id,'service_type'=>$a->service_type,'starts_at'=>$a->starts_at->toIso8601String(),'duration_minutes'=>$a->duration_minutes,'status'=>$a->status,'message'=>$a->message,'response'=>$a->priest_response,'priest'=>$a->priest,'requester'=>$a->requester,'conversation'=>$a->messages->map(fn($m)=>['sender'=>$m->sender->name,'body'=>$m->body,'sent_at'=>$m->created_at->toIso8601String()])])->all(),'notifications'=>$user->notifications()->latest()->get()->map(fn($n)=>['data'=>$n->data,'created_at'=>$n->created_at->toIso8601String(),'read_at'=>$n->read_at?->toIso8601String()])->all()];
        return response()->streamDownload(fn()=>print(json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)), 'priestagenda-data-'.$user->id.'.json',['Content-Type'=>'application/json; charset=UTF-8']);
    }
}
