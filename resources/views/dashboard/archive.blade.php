@extends('layouts.app')
@section('content')
<span class="eyebrow">{{ __('ui.your_account') }}</span><h1 style="font:42px Georgia,serif">{{ __('ui.archive') }}</h1>
<section class="card section"><h2>{{ __('ui.appointment_archive') }}</h2>
@forelse($appointments as $appointment)<article class="section"><span class="pill">{{ __('ui.'.$appointment->status) }}</span> <strong>{{ __('ui.'.$appointment->service_type) }}</strong> · {{ $appointment->starts_at->translatedFormat('l, F j, Y · g:i A') }}<br><span class="muted">{{ auth()->user()->isPriest()?$appointment->requester->name:$appointment->priest->name }}</span><p>{{ $appointment->priest_response ?? $appointment->change_response }}</p>@unless(auth()->user()->isAdmin())<a class="btn secondary" href="{{ route('dashboard.appointments.messages',$appointment) }}">{{ __('ui.open_conversation') }}</a>@endunless</article>@empty<p class="muted">{{ __('ui.archive_empty_appointments') }}</p>@endforelse
{{ $appointments->links() }}</section>
<section class="card section"><h2>{{ __('ui.notification_archive') }}</h2>
@forelse($notifications as $notification)<article class="section"><strong>{{ $notification->data['title']??__('ui.notification') }}</strong><p>{{ $notification->data['body']??'' }}</p><small class="muted">{{ $notification->created_at->translatedFormat('M j, Y · g:i A') }}</small></article>@empty<p class="muted">{{ __('ui.archive_empty_notifications') }}</p>@endforelse
{{ $notifications->links() }}</section>
@endsection
