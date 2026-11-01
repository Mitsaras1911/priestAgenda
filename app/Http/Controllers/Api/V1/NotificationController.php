<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(['unread_count'=>$request->user()->unreadNotifications()->count(),'data'=>$request->user()->notifications()->latest()->paginate(20)->through(fn($n)=>['id'=>$n->id,'data'=>$n->data,'read_at'=>$n->read_at,'created_at'=>$n->created_at])]);
    }
    public function markRead(Request $request,string $notificationId)
    {
        $notification=$request->user()->notifications()->whereKey($notificationId)->firstOrFail();$notification->markAsRead();return response()->json(['message'=>'Notification marked as read.']);
    }
    public function markAllRead(Request $request) { $request->user()->unreadNotifications->markAsRead();return response()->json(['message'=>'All notifications marked as read.']); }
}
