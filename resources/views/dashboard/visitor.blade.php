@extends('layouts.app')
@section('content')
<span class="eyebrow">{{ __('ui.your_account') }}</span><h1 style="font:42px Georgia,serif">{{ __('ui.welcome',['name'=>auth()->user()->name]) }}</h1>
<p class="muted">{{ __('ui.review_confession_requests') }}</p>
<p><a class="btn secondary" href="{{ route('dashboard.archive') }}">{{ __('ui.archive') }}</a> <a class="btn secondary" href="{{ route('dashboard.privacy.export') }}">{{ __('ui.download_my_data') }}</a></p>
<div class="section">
@forelse($appointments as $appointment)
<article class="card section"><div class="row"><div><span class="pill">{{ __('ui.'.$appointment->status) }}</span><h3>{{ __('ui.'.$appointment->service_type) }} · {{ $appointment->priest->name }}</h3><p>{{ $appointment->starts_at->translatedFormat('l, F j, Y · g:i A') }}</p><p>{{ $appointment->priest->parish }}</p>@if($appointment->priest_response)<p>{{ $appointment->priest_response }}</p>@endif @if($appointment->suggested_at)<p><strong>{{ __('ui.suggested_time') }}</strong> {{ $appointment->suggested_at->translatedFormat('l, F j, Y · g:i A') }}</p>@endif</div><div><a class="btn" href="{{ route('dashboard.appointments.messages',$appointment) }}">{{ __('ui.open_conversation') }}@if($appointment->messages()->whereNull('read_at')->where('sender_id',$appointment->priest_id)->exists()) · {{ __('ui.new_reply') }}@endif</a><p style="margin-top:12px"><a href="{{ route('priests.show',$appointment->priest->slug) }}">{{ __('ui.view_priest_profile') }}</a></p></div></div></article>
@empty<div class="card">{{ __('ui.no_your_requests') }} <a href="{{ route('home') }}">{{ __('ui.browse_priests') }}</a>.</div>@endforelse
</div>
@endsection
