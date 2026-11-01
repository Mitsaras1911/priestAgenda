<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class OtpController extends Controller
{
    public function requestForm() { return view('auth.sign-in'); }
    public function verifyForm() { abort_unless(session('otp_destination'), 403); return view('auth.verify'); }

    public function emailAvailability(Request $request)
    {
        $data=$request->validate(['email'=>'required|email|max:190']);
        $email=strtolower(trim($data['email']));
        $user=User::whereRaw('LOWER(email) = ?',[$email])->first();
        return response()->json(['registered'=>(bool)$user,'requires_password'=>(bool)$user?->isAdmin()]);
    }

    public function send(Request $request)
    {
        $data = $request->validate(['destination' => 'required|string|max:190', 'name' => 'nullable|string|max:120']);
        $returnTo = $request->input('return_to');
        if (is_string($returnTo) && str_starts_with($returnTo, '/p/') && !str_starts_with($returnTo, '//')) {
            $request->session()->put('booking_return_to', $returnTo);
            redirect()->setIntendedUrl(url($returnTo));
        }
        $dest = trim($data['destination']);
        $isEmail = filter_var($dest, FILTER_VALIDATE_EMAIL);
        if (!$isEmail && !preg_match('/^\+[1-9]\d{7,14}$/', $dest)) return back()->withErrors(['destination' => __('ui.email_phone_format')])->withInput();
        if($isEmail) $dest=strtolower($dest);
        $user = $isEmail ? User::whereRaw('LOWER(email) = ?', [$dest])->first() : User::where('phone', $dest)->first();
        if ($user?->isAdmin()) {
            if (!$isEmail) return back()->withErrors(['destination'=>__('ui.admin_signin_email_required')])->withInput();
            $credentials=$request->validate(['password'=>'required|string|max:255']);
            if (!Hash::check($credentials['password'], $user->password)) return back()->withErrors(['password'=>__('ui.invalid_admin_credentials')])->withInput($request->except('password'));
            auth()->login($user);
            $request->session()->regenerate();
            $request->session()->forget(['url.intended','booking_return_to']);
            return redirect()->route('dashboard.index');
        }
        if($isEmail && $user && filled($data['name'])) {
            return back()->withErrors(['destination'=>__('ui.email_already_registered')])->withInput();
        }
        if (!$user) {
            $attributes=['name' => $data['name'] ?: ($isEmail ? Str::before($dest, '@') : 'New member'), 'email' => $isEmail ? $dest : ((string) Str::of($dest)->replace('+','')->replace('@','')) . '@sms.priestagenda.invalid', 'phone' => $isEmail ? null : $dest, 'password' => Str::random(48), 'role' => 'visitor'];
            $identityColumn=$isEmail?'email':'phone';
            $user=User::firstOrCreate([$identityColumn=>$dest],$attributes);
        }
        $code = (string) random_int(100000, 999999);
        DB::table('login_codes')->where('destination', $dest)->delete();
        DB::table('login_codes')->insert(['destination' => $dest, 'code_hash' => Hash::make($code), 'expires_at' => now()->addMinutes(10), 'created_at' => now()]);
        if ($isEmail) {
            Mail::send('emails.otp', ['code'=>$code], function ($message) use ($dest) { $message->to($dest)->subject(__('ui.otp_subject')); });
            if (app()->environment('local') && config('mail.default') === 'log') session()->flash('dev_otp', $code);
        } else {
            $sid = config('services.twilio.sid'); $token = config('services.twilio.token'); $from = config('services.twilio.from');
            if (!$sid || !$token || !$from) return back()->withErrors(['destination' => __('ui.sms_unconfigured')])->withInput();
            Http::asForm()->withBasicAuth($sid, $token)->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", ['To' => $dest, 'From' => $from, 'Body' => __('ui.otp_sms',['code'=>$code])])->throw();
        }
        session(['otp_destination' => $dest]);
        return redirect()->route('otp.form')->with('status', __('ui.otp_sent'));
    }

    public function verify(Request $request)
    {
        $request->validate(['code' => 'required|digits:6']); $dest = session('otp_destination'); abort_unless($dest, 403);
        $entry = DB::table('login_codes')->where('destination', $dest)->first();
        if (!$entry || now()->gt($entry->expires_at) || !Hash::check($request->code, $entry->code_hash)) return back()->withErrors(['code' => __('ui.otp_invalid')]);
        $user = filter_var($dest, FILTER_VALIDATE_EMAIL) ? User::where('email', strtolower($dest))->firstOrFail() : User::where('phone', $dest)->firstOrFail();
        if ($user->isAdmin()) {
            DB::table('login_codes')->where('destination', $dest)->delete();
            $request->session()->forget('otp_destination');
            return redirect()->route('login')->withErrors(['destination'=>__('ui.admin_signin_email_required')]);
        }
        if (filter_var($dest, FILTER_VALIDATE_EMAIL)) $user->forceFill(['email_verified_at' => now()])->save();
        DB::table('login_codes')->where('destination', $dest)->delete(); auth()->login($user); $request->session()->regenerate(); $request->session()->forget('otp_destination');
        $intended=$request->session()->get('url.intended');
        $intendedHost=is_string($intended)?parse_url($intended,PHP_URL_HOST):null;
        $intendedPath=is_string($intended)?parse_url($intended,PHP_URL_PATH):null;
        if($user->isPriest()||$user->isAdmin()) {
            $request->session()->forget(['url.intended','booking_return_to']);
            return redirect()->route('dashboard.index');
        }
        $bookingReturnTo=$request->session()->pull('booking_return_to');
        $bookingPath=is_string($bookingReturnTo)?parse_url($bookingReturnTo,PHP_URL_PATH):null;
        if(is_string($bookingReturnTo)&&is_string($bookingPath)&&str_starts_with($bookingPath,'/p/')&&!str_starts_with($bookingPath,'//')) return redirect()->to(url($bookingReturnTo));
        if(is_string($intendedPath)&&str_starts_with($intendedPath,'/p/')&&(!$intendedHost||$intendedHost===$request->getHost())) {
            return redirect()->to($intended);
        }
        $request->session()->forget(['url.intended','booking_return_to']);
        return redirect()->route('dashboard.index');
    }

    public function logout(Request $request) { auth()->logout(); $request->session()->invalidate(); $request->session()->regenerateToken(); return redirect()->route('home'); }
}
