<?php
namespace App\Http\Controllers;
use App\Models\Appointment;
use Illuminate\Http\Request;
class AppointmentArchiveController extends Controller
{
    public function index(Request $request)
    {
        $user=$request->user();
        $query=$user->isAdmin()?Appointment::query():($user->isPriest()?Appointment::where('priest_id',$user->id):Appointment::where('requester_id',$user->id));
        $appointments=$query->with(['priest','requester'])->where(function($q){$q->whereIn('status',['declined','cancelled','completed'])->orWhere('starts_at','<',now());})->orderByDesc('starts_at')->paginate(20,['*'],'appointments_page');
        $notifications=$user->notifications()->whereNotNull('archived_at')->latest()->paginate(20,['*'],'notifications_page');
        return view('dashboard.archive',compact('appointments','notifications'));
    }
    public function archiveNotification(Request $request,string $notificationId)
    {
        $request->user()->notifications()->whereKey($notificationId)->firstOrFail()->forceFill(['archived_at'=>now()])->save();
        return back()->with('success',__('ui.notification_archived'));
    }
}
