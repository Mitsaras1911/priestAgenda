<?php
namespace App\Http\Controllers;
use App\Models\Appointment;
use Illuminate\Http\Request;
use App\Support\AppointmentNotifier;
class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user=$request->user();
        if($user->isAdmin()) {
            $request->validate(['per_page'=>'nullable|in:10,25,50']);
            $perPage=(int)$request->query('per_page',10);
            $priestQuery=\App\Models\User::where('role','priest');
            $priests=(clone $priestQuery)->withCount(['appointments','availabilities'])->orderBy('name')->paginate($perPage,['*'],'priests_page')->withQueryString();
            $verificationQueue=(clone $priestQuery)->withCount(['appointments','availabilities'])->orderBy('name')->paginate($perPage,['*'],'review_page')->withQueryString();
            $stats=[
                'users'=>\App\Models\User::where('role','visitor')->count(),
                'accounts'=>\App\Models\User::count(),
                'priests'=>(clone $priestQuery)->count(),
                'published_priests'=>(clone $priestQuery)->whereNotNull('slug')->where('is_priest_verified',true)->count(),
                'appointments'=>Appointment::count(),
                'pending'=>Appointment::where('status','pending')->count(),
                'confirmed'=>Appointment::where('status','confirmed')->count(),
                'declined'=>Appointment::where('status','declined')->count(),
                'upcoming'=>Appointment::whereIn('status',['pending','confirmed'])->where('starts_at','>=',now())->count(),
            ];
            $recentAppointments=Appointment::with(['priest','requester'])->orderByDesc('created_at')->paginate($perPage,['*'],'appointments_page')->withQueryString();
            $verificationHistory=\App\Models\PriestVerificationLog::with(['admin','priest'])->latest()->paginate($perPage,['*'],'history_page')->withQueryString();
            return view('dashboard.admin',compact('priests','verificationQueue','stats','recentAppointments','verificationHistory','perPage'));
        }
        if($user->isPriest()) return view('dashboard.priest',['appointments'=>$user->appointments()->with('requester')->orderByDesc('starts_at')->get()]);
        return view('dashboard.visitor',['appointments'=>$user->appointmentRequests()->with('priest')->orderByDesc('starts_at')->get()]);
    }
    public function respond(Request $request, Appointment $appointment)
    {
        abort_unless($request->user()->isPriest() && $appointment->priest_id===$request->user()->id,403);
        abort_unless($request->user()->is_priest_verified && $request->user()->approval_status==='approved',403,__('ui.priest_verification_pending'));
        $data=$request->validate(['decision'=>'required|in:confirmed,declined','suggested_at'=>'nullable|date|after:now','priest_response'=>'nullable|string|max:2000']);
        if($data['decision']==='confirmed') $appointment->update(['status'=>'confirmed','priest_response'=>$data['priest_response']??null,'suggested_at'=>null]);
        else $appointment->update(['status'=>'declined','suggested_at'=>$data['suggested_at']??null,'priest_response'=>$data['priest_response']??null]);
        foreach($request->user()->unreadNotifications as $notification) {
            if(($notification->data['event']??null)==='appointment_requested' && (int)($notification->data['appointment_id']??0)===$appointment->id) $notification->markAsRead();
        }
        AppointmentNotifier::responded($appointment);
        return back()->with('success',__('ui.requester_notified'));
    }
}
