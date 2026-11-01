<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PriestVerificationLog;
use App\Notifications\RegisteredPriestPendingApproval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function storePriest(Request $request)
    {
        abort_unless($request->user()->isAdmin(),403);
        $data=$request->validate(['name'=>'required|string|max:120','email'=>'required|email|max:190|unique:users,email','parish'=>'nullable|string|max:160','city'=>['nullable','string',Rule::in(config('priest.cities'))]]);
        $priest=User::create($data+['password'=>Str::random(64),'role'=>'priest','approval_status'=>'pending','slug'=>Str::slug($data['name']).'-'.Str::lower(Str::random(5))]);
        Notification::send(User::where('role','admin')->get(),new RegisteredPriestPendingApproval($priest));
        return back()->with('success',__('ui.priest_created_pending_verification'));
    }

    public function updateVerification(Request $request, User $priest)
    {
        abort_unless($request->user()->isAdmin() && $priest->isPriest(),403);
        $data=$request->validate(['type'=>'required|in:priest,confessor','verified'=>'required|boolean','decision'=>'nullable|in:reject,revoke','reason'=>'nullable|string|max:2000']);
        $verified=(bool)$data['verified'];
        if($data['type']==='confessor') {
            if($verified && !$priest->is_priest_verified) return back()->withErrors(['verification'=>__('ui.verify_priest_first')]);
            if($verified && !$priest->is_confessor) return back()->withErrors(['verification'=>__('ui.confessor_not_declared')]);
            if (($data['decision']??null)==='reject') {
                $priest->is_confessor=false;
                $priest->is_confessor_verified=false;
            } else {
                $priest->is_confessor_verified=$verified;
            }
        } else {
            $priest->is_priest_verified=$verified;
            $priest->approval_status=$verified?'approved':'pending';
            if(!$verified) $priest->is_confessor_verified=false;
        }
        $priest->save();        $decision=($data['decision']??null)==='reject'?'rejected':(($data['decision']??null)==='revoke'?'revoked':($verified?'approved':'revoked'));
        PriestVerificationLog::create(['admin_id'=>$request->user()->id,'priest_id'=>$priest->id,'type'=>$data['type'],'decision'=>$decision,'reason'=>$data['reason']??null]);
        if($data['type']==='priest' && $verified) $this->markPriestNotificationsRead($request, $priest);
        if($data['type']==='confessor' && ($verified || ($data['decision']??null)==='reject')) $this->markConfessorNotificationsRead($request, $priest);
        return back()->with('success',__('ui.verification_updated'));
    }

    public function updatePriestApproval(Request $request, User $priest)
    {
        abort_unless($request->user()->isAdmin() && $priest->isPriest(),403);
        $data=$request->validate(['status'=>'required|in:approved,rejected','reason'=>'nullable|string|max:2000']);
        $priest->approval_status=$data['status'];
        $priest->is_priest_verified=$data['status']==='approved';
        if($data['status']==='rejected') $priest->is_confessor_verified=false;
        $priest->save();        PriestVerificationLog::create(['admin_id'=>$request->user()->id,'priest_id'=>$priest->id,'type'=>'priest','decision'=>$data['status'],'reason'=>$data['reason']??null]);
        $this->markPriestNotificationsRead($request, $priest);
        return back()->with('success',__('ui.priest_approval_updated'));
    }

    private function markConfessorNotificationsRead(Request $request, User $priest): void
    {
        $request->user()->notifications()->get()->filter(fn($notification)=>($notification->data['event']??null)==='confessor_pending_verification' && (int)($notification->data['priest_id']??0)===$priest->id)->each->markAsRead();
    }
    private function markPriestNotificationsRead(Request $request, User $priest): void
    {
        $request->user()->notifications()->get()->filter(fn($notification)=>($notification->data['event']??null)==='priest_pending_approval' && (int)($notification->data['priest_id']??0)===$priest->id)->each->markAsRead();
    }
}
