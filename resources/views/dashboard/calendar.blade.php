@extends('layouts.app')
@section('content')
<style>
.calendar-nav{display:flex;justify-content:space-between;gap:10px;margin:20px 0}.calendar-scroll{overflow-x:auto}.month-grid{display:grid;grid-template-columns:repeat(7,minmax(110px,1fr));min-width:770px;gap:6px}.weekday{font-weight:700;text-align:center;padding:8px}.calendar-day{min-height:132px;padding:8px;background:#fff;border:1px solid #e4e5dd;border-radius:9px}.calendar-day.today{outline:2px solid #c9a86a}.calendar-day.blocked{background:#fbf5ed}.day-number{font-weight:700;margin-bottom:5px}.calendar-event,.calendar-block{font-size:12px;line-height:1.35;margin-top:5px;padding:6px;border-radius:6px;overflow-wrap:anywhere}.calendar-event.confirmed{background:#e5f2e8;border-left:3px solid #245d4b}.calendar-event.pending{background:#fff4d8;border-left:3px solid #c9a86a}.calendar-block{background:#eee8df;color:#725a3b}.calendar-event input{width:100%;font-size:11px;margin:3px 0;padding:4px}
</style>
<span class="eyebrow">{{ __('ui.priest_calendar') }}</span><h1 style="font:42px Georgia,serif">{{ $month->translatedFormat('F Y') }}</h1>
<p class="muted">{{ __('ui.calendar_help') }}</p><p><a class="btn secondary" href="{{ route('dashboard.profile.edit') }}">{{ __('ui.set_weekly_hours_blocks') }}</a></p>
<div class="calendar-nav"><a class="btn secondary" href="{{ route('dashboard.calendar',['month'=>$previous]) }}">{{ __('ui.previous') }}</a><a class="btn secondary" href="{{ route('dashboard.calendar',['month'=>now()->format('Y-m')]) }}">{{ __('ui.today') }}</a><a class="btn secondary" href="{{ route('dashboard.calendar',['month'=>$next]) }}">{{ __('ui.next') }}</a></div>
<div class="calendar-scroll"><section class="month-grid">
@foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $i=>$weekday)<div class="weekday">{{ __('ui.short_day_'.$i) }}</div>@endforeach
@foreach($weeks as $week)@foreach($week as $day)
<div class="calendar-day {{ $day && $day->isToday() ? 'today' : '' }} {{ $day && isset($blocks[$day->toDateString()]) ? 'blocked' : '' }}">
@if($day)<div class="day-number">{{ $day->day }}</div>
@if(isset($blocks[$day->toDateString()]))@foreach($blocks[$day->toDateString()] as $block)<div class="calendar-block">{{ __('ui.blocked') }}{{ $block->reason ? ': '.$block->reason : '' }} ({{ __('ui.'.$block->service_type) }})</div>@endforeach @endif
@foreach($events[$day->toDateString()] ?? [] as $event)
<div class="calendar-event {{ $event->status }}"><strong>{{ $event->starts_at->translatedFormat('g:i A') }} · {{ __('ui.'.$event->service_type) }}</strong><br>{{ $event->requester->name }}<br><small>{{ __('ui.'.$event->status) }}</small>
@if($event->starts_at->isFuture() && in_array($event->status,['pending','confirmed'],true) && !in_array($event->change_request_status,['pending_requester','pending_priest'],true))
<details style="margin-top:6px"><summary>{{ __('ui.edit_or_revoke') }}</summary>
<form method="post" action="{{ route('dashboard.appointments.change.propose',$event) }}" style="margin-top:6px" data-confirm-submit data-confirm-message="{{ __('ui.confirm_save_changes') }}">@csrf<input type="hidden" name="type" value="reschedule"><input type="datetime-local" name="starts_at" min="{{ now()->format('Y-m-d\TH:i') }}" value="{{ $event->starts_at->format('Y-m-d\TH:i') }}" required><input name="message" maxlength="2000" placeholder="{{ __('ui.change_message_optional') }}"><button class="btn secondary" style="padding:5px 8px">{{ __('ui.propose_new_time') }}</button></form>
<form method="post" action="{{ route('dashboard.appointments.change.propose',$event) }}" style="margin-top:4px" data-confirm-submit data-confirm-message="{{ __('ui.confirm_delete_change') }}">@csrf<input type="hidden" name="type" value="cancel"><input name="message" maxlength="2000" placeholder="{{ __('ui.cancellation_reason_optional') }}"><button class="btn danger" style="padding:5px 8px">{{ __('ui.propose_cancellation') }}</button></form></details>
@elseif($event->change_request_status==='pending_requester')<small>{{ __('ui.waiting_for_couple_consent') }}</small>@endif
</div>
@endforeach
@endif
</div>
@endforeach @endforeach
</section></div>
<section class="card section"><h2>{{ __('ui.upcoming_events') }}</h2>@php($upcoming=$events->flatten()->sortBy('starts_at'))@forelse($upcoming as $event)<p><span class="pill">{{ __('ui.'.$event->status) }}</span> {{ $event->starts_at->translatedFormat('D, M j · g:i A') }} — {{ __('ui.'.$event->service_type) }}, {{ $event->requester->name }}</p>@empty<p class="muted">{{ __('ui.no_month_appointments') }}</p>@endforelse</section>
@endsection
