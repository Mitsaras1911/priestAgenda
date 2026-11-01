<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
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
        return response()->json(['data'=>['id'=>$priest->id,'name'=>$priest->name,'email'=>$priest->email,'role'=>$priest->role,'slug'=>$priest->slug,'approval_status'=>$priest->approval_status]],201);
    }
}
