<?php
namespace App\Http\Controllers;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Http\Request;
class UserNotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications=$request->user()->notifications()->whereNull('archived_at')->latest()->paginate(20);
        $appointmentRequests=collect(); $changeRequests=collect(); $pendingPriests=collect(); $pendingConfessors=collect();
        if($request->user()->isAdmin()) {
            $items=$notifications->getCollection();
            $priestIds=$items->filter(fn($n)=>($n->data['event']??null)==='priest_pending_approval')->map(fn($n)=>(int)($n->data['priest_id']??0))->filter()->unique();
            $pendingPriests=User::whereIn('id',$priestIds)->where('role','priest')->whereIn('approval_status',['pending','rejected'])->get()->keyBy('id');
            $confessorIds=$items->filter(fn($n)=>($n->data['event']??null)==='confessor_pending_verification')->map(fn($n)=>(int)($n->data['priest_id']??0))->filter()->unique();
            $pendingConfessors=User::whereIn('id',$confessorIds)->where('role','priest')->where('is_confessor',true)->where('is_confessor_verified',false)->get()->keyBy('id');
        }
        $items=$notifications->getCollection();
        if($request->user()->isPriest()) {
            $ids=$items->filter(fn($n)=>($n->data['event']??null)==='appointment_requested')->map(fn($n)=>(int)($n->data['appointment_id']??0))->filter()->unique();
            $appointmentRequests=Appointment::with('requester')->where('priest_id',$request->user()->id)->whereIn('id',$ids)->where('status','pending')->get()->keyBy('id');
        }
        if($request->user()->isVisitor()) {
            $ids=$items->filter(fn($n)=>($n->data['event']??null)==='appointment_change_requested')->map(fn($n)=>(int)($n->data['appointment_id']??0))->filter()->unique();
            $changeRequests=Appointment::with('priest')->where('requester_id',$request->user()->id)->whereIn('id',$ids)->where('change_requested_by','priest')->where('change_request_status','pending_requester')->get()->keyBy('id');
        }
        return view('dashboard.notifications',compact('notifications','appointmentRequests','changeRequests','pendingPriests','pendingConfessors'));
    }
    public function markRead(Request $request,string $notificationId) { $request->user()->notifications()->whereKey($notificationId)->firstOrFail()->markAsRead(); return back()->with('success',__('ui.notification_marked')); }
    public function markAllRead(Request $request) { $request->user()->unreadNotifications->markAsRead(); return back()->with('success',__('ui.all_notifications_marked')); }
}
