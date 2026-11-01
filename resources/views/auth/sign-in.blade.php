@extends('layouts.app')
@section('content')
<div class="card" style="max-width:520px;margin:50px auto">
<span class="eyebrow">{{ __('ui.secure_signin') }}</span><h1 id="signin-heading" style="font:36px Georgia,serif">{{ __('ui.code_then_in') }}</h1>
<p class="muted" id="email-status" aria-live="polite">{{ __('ui.send_otp') }}</p>
<form method="post" action="{{ route('otp.send') }}">@csrf
@if(request('return_to'))<input type="hidden" name="return_to" value="{{ request('return_to') }}">@endif
<div class="field"><label for="destination">{{ __('ui.email_or_phone') }}</label><input id="destination" name="destination" value="{{ old('destination') }}" required placeholder="you@example.com or +357..." autocomplete="username"></div>
<div class="field" id="name-field"><label for="name">{{ __('ui.your_name') }} <span class="muted">{{ __('ui.new_account') }}</span></label><input id="name" name="name" value="{{ old('name') }}" autocomplete="name"></div>
<div class="field" id="admin-password-field" hidden><label for="password">{{ __('ui.admin_password_label') }}</label><input id="password" type="password" name="password" autocomplete="current-password" maxlength="255"></div>

<button class="btn" id="signin-submit">{{ __('ui.send_signin_code') }}</button></form></div>
@endsection
@push('scripts')
<script>
(()=>{
    const destination=document.getElementById('destination');
    const nameField=document.getElementById('name-field');
    const nameInput=document.getElementById('name');
    const passwordField=document.getElementById('admin-password-field');
    const passwordInput=document.getElementById('password');
    const status=document.getElementById('email-status');
    const submit=document.getElementById('signin-submit');
    const heading=document.getElementById('signin-heading');
    if(!destination||!nameField||!nameInput||!passwordField||!passwordInput||!status||!submit||!heading)return;
    let timer;
    let controller;
    const reset=()=>{nameField.hidden=false;passwordField.hidden=true;passwordInput.required=false;submit.textContent=@json(__('ui.send_signin_code'));heading.textContent=@json(__('ui.code_then_in'));status.textContent=@json(__('ui.send_otp'));};
    destination.addEventListener('input',()=>{
        clearTimeout(timer);
        controller?.abort();
        const email=destination.value.trim();
        if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)){reset();return;}
        timer=setTimeout(async()=>{
            controller=new AbortController();
            try{
                const response=await fetch(@json(route('otp.email-availability'))+'?email='+encodeURIComponent(email),{headers:{'Accept':'application/json'},signal:controller.signal});
                if(!response.ok){reset();return;}
                const result=await response.json();
                if(result.requires_password){nameField.hidden=true;nameInput.value='';passwordField.hidden=false;passwordInput.required=true;submit.textContent=@json(__('ui.admin_password_signin'));heading.textContent=@json(__('ui.admin_signin_title'));status.textContent=@json(__('ui.admin_password_prompt'));}
                else if(result.registered){nameField.hidden=true;nameInput.value='';passwordField.hidden=true;passwordInput.required=false;submit.textContent=@json(__('ui.send_signin_code'));status.textContent=@json(__('ui.email_already_registered_signin'));}
                else{reset();}
            }catch(error){if(error.name!=='AbortError')reset();}
        },300);
    });
    if(destination.value.trim())destination.dispatchEvent(new Event('input'));
})();
</script>
@endpush
