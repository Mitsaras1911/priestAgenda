<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function adminLogin(Request $request)
    {
        $data=$request->validate(['email'=>'required|email|max:190','password'=>'required|string|max:255','device_name'=>'nullable|string|max:100']);
        $email=strtolower(trim($data['email']));
        $user=User::whereRaw('LOWER(email) = ?',[$email])->where('role','admin')->first();
        if(!$user || !Hash::check($data['password'],$user->password)) return response()->json(['message'=>__('ui.invalid_admin_credentials')],422);
        return response()->json(['token'=>$user->createToken($data['device_name']??'priestagenda-admin')->plainTextToken,'token_type'=>'Bearer','user'=>$this->publicUser($user)],201);
    }
    public function sendCode(Request $request)
    {
        $data=$request->validate(['destination'=>'required|string|max:190','name'=>'nullable|string|max:120']);
        $destination=trim($data['destination']); $email=filter_var($destination,FILTER_VALIDATE_EMAIL);
        if (!$email && !preg_match('/^\+[1-9]\d{7,14}$/',$destination)) return response()->json(['message'=>'Use an email address or phone number in international format.'],422);
        if($email) $destination=strtolower($destination);
        $user=$email?User::where('email',$destination)->first():User::where('phone',$destination)->first();
        if($user?->isAdmin()) return response()->json(['message'=>'Administrators must use the email and password sign-in endpoint.','login_endpoint'=>'/api/v1/auth/admin-login'],409);
        if(!$user) $user=User::create(['name'=>$data['name']?:($email?Str::before($destination,'@'):'New member'),'email'=>$email?$destination:((string)Str::of($destination)->replace('+','')->replace('@','')).'@sms.priestagenda.invalid','phone'=>$email?null:$destination,'password'=>Str::random(48),'role'=>'visitor']);
        $code=(string)random_int(100000,999999);
        DB::table('login_codes')->where('destination',$destination)->delete();
        DB::table('login_codes')->insert(['destination'=>$destination,'code_hash'=>Hash::make($code),'expires_at'=>now()->addMinutes(10),'created_at'=>now()]);
        if($email) Mail::send('emails.otp',['code'=>$code],fn($m)=>$m->to($destination)->subject(__('ui.otp_subject')));
        else {
            $sid=config('services.twilio.sid');$token=config('services.twilio.token');$from=config('services.twilio.from');
            if(!$sid||!$token||!$from) return response()->json(['message'=>'SMS sign-in is not configured. Configure Twilio or use email.'],503);
            Http::asForm()->withBasicAuth($sid,$token)->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json",['To'=>$destination,'From'=>$from,'Body'=>"Your PriestAgenda code is {$code}. It expires in 10 minutes."])->throw();
        }
        return response()->json(['message'=>'If the destination is valid, a sign-in code has been sent.','expires_in'=>600],202);
    }

    public function verify(Request $request)
    {
        $data=$request->validate(['destination'=>'required|string|max:190','code'=>'required|digits:6','device_name'=>'nullable|string|max:100']);
        $destination=filter_var($data['destination'],FILTER_VALIDATE_EMAIL)?strtolower(trim($data['destination'])):trim($data['destination']);
        $entry=DB::table('login_codes')->where('destination',$destination)->first();
        if(!$entry||now()->gt($entry->expires_at)||!Hash::check($data['code'],$entry->code_hash)) return response()->json(['message'=>'The code is invalid or expired.'],422);
        $user=filter_var($destination,FILTER_VALIDATE_EMAIL)?User::where('email',$destination)->first():User::where('phone',$destination)->first();
        if(!$user) return response()->json(['message'=>'Account not found. Request a new code.'],422);
        if($user->isAdmin()) { DB::table('login_codes')->where('destination',$destination)->delete(); return response()->json(['message'=>'Administrators must use the email and password sign-in endpoint.','login_endpoint'=>'/api/v1/auth/admin-login'],409); }
        if(filter_var($destination,FILTER_VALIDATE_EMAIL)) $user->forceFill(['email_verified_at'=>now()])->save();
        DB::table('login_codes')->where('destination',$destination)->delete();
        return response()->json(['token'=>$user->createToken($data['device_name']??'priestagenda-api')->plainTextToken,'token_type'=>'Bearer','user'=>$this->publicUser($user)],201);
    }

    public function me(Request $request) { return response()->json(['user'=>$this->publicUser($request->user())]); }
    public function logout(Request $request) { $request->user()->currentAccessToken()?->delete(); return response()->json(['message'=>'Signed out.']); }

    private function publicUser(User $user): array { return ['id'=>$user->id,'name'=>$user->name,'email'=>str_ends_with((string)$user->email,'@sms.priestagenda.invalid')?null:$user->email,'phone'=>$user->phone,'role'=>$user->role,'slug'=>$user->slug]; }
}
