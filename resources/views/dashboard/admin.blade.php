@extends('layouts.app')
@section('content')
<style>.admin-page-controls{display:flex;justify-content:flex-end;align-items:center;gap:10px;margin:14px 0}.admin-page-controls label,.admin-pagination{font:13px system-ui,sans-serif;color:#6f716d}.admin-page-controls select{padding:8px 10px;border:1px solid #d4cab8;border-radius:5px;background:#fffdf9;color:#28344a}.admin-pagination{display:flex;justify-content:space-between;align-items:center;gap:14px;margin:14px 0 4px;flex-wrap:wrap}.admin-pagination-actions{display:flex;gap:8px}.admin-pagination-actions a,.admin-pagination-actions span{padding:7px 12px;border:1px solid #d7c7a3;border-radius:5px;background:#f7f2e8;color:#344c70;text-decoration:none}.admin-pagination-actions .disabled{opacity:.48;background:#f5f2ed;color:#817c72}.admin-pagination-pages{display:flex;gap:6px;align-items:center}.admin-pagination-pages a,.admin-pagination-pages span{padding:5px 9px;border:1px solid #e2d8c6;border-radius:4px;text-decoration:none}.admin-pagination-pages [aria-current=page]{background:#344c70;color:white;border-color:#344c70}@media(max-width:650px){.admin-pagination{align-items:flex-start;flex-direction:column}}</style>
<span class="eyebrow">{{ __('ui.administrator') }}</span>
<h1 style="font:42px Georgia,serif">{{ __('ui.overview') }}</h1>
<p class="muted">{{ __('ui.manage_accounts') }}</p>

<div class="grid section">
    <div class="card"><span class="eyebrow">{{ __('ui.admin_registered_couples') }}</span><h2>{{ $stats['users'] }}</h2></div>
    <div class="card"><span class="eyebrow">{{ __('ui.admin_total_accounts') }}</span><h2>{{ $stats['accounts'] }}</h2></div>
    <div class="card"><span class="eyebrow">{{ __('ui.priests') }}</span><h2>{{ $stats['priests'] }}</h2><p class="muted">{{ $stats['published_priests'] }} {{ __('ui.admin_published') }}</p></div>
    <div class="card"><span class="eyebrow">{{ __('ui.appointment_requests') }}</span><h2>{{ $stats['appointments'] }}</h2></div>
</div>
<div id="admin-page-size"><form id="admin-page-size-form" class="admin-page-controls" method="get" action="{{ route('dashboard.index') }}"><label for="admin-per-page">{{ __('ui.items_per_page') }}</label><select id="admin-per-page" name="per_page" onchange="this.form.requestSubmit()">@foreach([10,25,50] as $pageSize)<option value="{{ $pageSize }}" @selected($perPage===$pageSize)>{{ $pageSize }}</option>@endforeach</select></form></div>

<div class="grid section">
    <div class="card"><span class="eyebrow">{{ __('ui.pending') }}</span><h2>{{ $stats['pending'] }}</h2></div>
    <div class="card"><span class="eyebrow">{{ __('ui.admin_confirmed') }}</span><h2>{{ $stats['confirmed'] }}</h2></div>
    <div class="card"><span class="eyebrow">{{ __('ui.declined') }}</span><h2>{{ $stats['declined'] }}</h2></div>
    <div class="card"><span class="eyebrow">{{ __('ui.admin_upcoming') }}</span><h2>{{ $stats['upcoming'] }}</h2></div>
</div>

<section class="card section">
    <h2>{{ __('ui.create_priest_account') }}</h2><p class="muted">{{ __('ui.priest_otp_help') }}</p>
    <form method="post" action="{{ route('dashboard.admin.priests.store') }}" data-confirm-submit data-confirm-message="{{ __('ui.confirm_admin_action') }}">@csrf
        <div class="row">
            <div class="field"><label>{{ __('ui.name') }}</label><input name="name" required></div>
            <div class="field"><label>{{ __('ui.email') }}</label><input type="email" name="email" required></div>
            <div class="field"><label>{{ __('ui.parish') }}</label><input name="parish"></div>
            <div class="field"><label>{{ __('ui.city') }}</label><select name="city"><option value="">—</option>@foreach(config('priest.cities') as $city)<option value="{{ $city }}">{{ $city }}</option>@endforeach</select></div>
        </div><button class="btn">{{ __('ui.create_priest') }}</button>
    </form>
</section>

<section class="card section" id="admin-review-section">
    <h2>{{ __('ui.verification_queue') }}</h2>
    <p class="muted">{{ __('ui.verification_admin_help') }}</p>
    <div style="overflow-x:auto"><table class="table"><thead><tr><th>{{ __('ui.name') }}</th><th>{{ __('ui.parish') }}</th><th>{{ __('ui.priest_verification') }}</th><th>{{ __('ui.confessor_verification') }}</th></tr></thead><tbody>
    @forelse($verificationQueue as $priest)
        <tr><td>{{ $priest->name }}<br><small>{{ $priest->email }}</small></td><td>{{ $priest->parish ?: '—' }}</td>
            <td><span class="pill">{{ $priest->is_priest_verified ? __('ui.verified') : __('ui.pending_verification') }}</span>
                <form method="post" action="{{ route('dashboard.admin.priests.verification',$priest) }}" style="display:inline" data-confirm-submit data-confirm-message="{{ __('ui.confirm_admin_action') }}">@csrf<input type="hidden" name="type" value="priest"><input type="hidden" name="verified" value="{{ $priest->is_priest_verified ? '0' : '1' }}"><button class="btn secondary">{{ $priest->is_priest_verified ? __('ui.revoke_priest_verification') : __('ui.verify_priest') }}</button></form>
            </td>
            <td>@if(!$priest->is_confessor){{ __('ui.not_declared') }}@else
                <span class="pill">{{ $priest->is_confessor_verified ? __('ui.verified') : __('ui.pending_verification') }}</span>
                @if($priest->is_confessor_verified)
                    <form method="post" action="{{ route('dashboard.admin.priests.verification',$priest) }}" style="display:inline" data-confirm-submit data-confirm-message="{{ __('ui.confirm_admin_action') }}">@csrf<input type="hidden" name="type" value="confessor"><input type="hidden" name="verified" value="0"><input type="hidden" name="decision" value="revoke"><button class="btn secondary">{{ __('ui.revoke_confessor_verification') }}</button></form>
                @else
                    <form method="post" action="{{ route('dashboard.admin.priests.verification',$priest) }}" style="display:inline" data-confirm-submit data-confirm-message="{{ __('ui.confirm_admin_action') }}">@csrf<input type="hidden" name="type" value="confessor"><input type="hidden" name="verified" value="1"><button class="btn secondary" @disabled(!$priest->is_priest_verified)>{{ __('ui.verify_confessor') }}</button></form>
                    <form method="post" action="{{ route('dashboard.admin.priests.verification',$priest) }}" style="display:inline" data-confirm-submit data-confirm-message="{{ __('ui.confirm_admin_action') }}">@csrf<input type="hidden" name="type" value="confessor"><input type="hidden" name="verified" value="0"><input type="hidden" name="decision" value="reject"><button class="btn danger">{{ __('ui.reject_confessor') }}</button></form>
                @endif
                @if(!$priest->is_priest_verified)<small class="muted">{{ __('ui.verify_priest_first') }}</small>@endif
            @endif</td></tr>
    @empty<tr><td colspan="4">{{ __('ui.no_priest_accounts') }}</td></tr>@endforelse
    </tbody></table></div>
    <div class="admin-pagination"><span>{{ __('ui.pagination_showing',['from'=>$verificationQueue->firstItem()??0,'to'=>$verificationQueue->lastItem()??0,'total'=>$verificationQueue->total()]) }} · {{ __('ui.pagination_page',['current'=>$verificationQueue->currentPage(),'last'=>$verificationQueue->lastPage()]) }}</span><div class="admin-pagination-actions"><a class="{{ $verificationQueue->onFirstPage()?'disabled':'' }}" @if(!$verificationQueue->onFirstPage()) href="{{ $verificationQueue->url(1) }}" @endif>{{ __('ui.first') }}</a><a class="{{ $verificationQueue->onFirstPage()?'disabled':'' }}" @if(!$verificationQueue->onFirstPage()) href="{{ $verificationQueue->previousPageUrl() }}" @endif>{{ __('ui.previous') }}</a><a class="{{ $verificationQueue->hasMorePages()?'':'disabled' }}" @if($verificationQueue->hasMorePages()) href="{{ $verificationQueue->nextPageUrl() }}" @endif>{{ __('ui.next') }}</a></div></div>
</section>

<section class="card section" id="admin-priests-section">
    <h2>{{ __('ui.priest_directory') }}</h2>
    <div style="overflow-x:auto"><table class="table"><thead><tr>
        <th>{{ __('ui.name') }}</th><th>{{ __('ui.email') }}</th><th>{{ __('ui.phone') }}</th><th>{{ __('ui.parish') }}</th><th>{{ __('ui.city') }}</th><th>{{ __('ui.public_page') }}</th><th>{{ __('ui.admin_appointments') }}</th><th>{{ __('ui.admin_availability_rules') }}</th>
    </tr></thead><tbody>
    @forelse($priests as $priest)
        <tr><td>{{ $priest->name }}</td><td>{{ $priest->email }}</td><td>{{ $priest->phone ?: '—' }}</td><td>{{ $priest->parish ?: '—' }}</td><td>{{ $priest->city ?: '—' }}</td>
            <td>@if($priest->slug)<a href="{{ route('priests.show',$priest->slug) }}">{{ __('ui.view') }}</a>@else{{ __('ui.not_published') }}@endif</td>
            <td>{{ $priest->appointments_count }}</td><td>{{ $priest->availabilities_count }}</td></tr>
    @empty<tr><td colspan="8">{{ __('ui.no_priest_accounts') }}</td></tr>@endforelse
    </tbody></table></div>
    <div class="admin-pagination"><span>{{ __('ui.pagination_showing',['from'=>$priests->firstItem()??0,'to'=>$priests->lastItem()??0,'total'=>$priests->total()]) }} · {{ __('ui.pagination_page',['current'=>$priests->currentPage(),'last'=>$priests->lastPage()]) }}</span><div class="admin-pagination-actions"><a class="{{ $priests->onFirstPage()?'disabled':'' }}" @if(!$priests->onFirstPage()) href="{{ $priests->url(1) }}" @endif>{{ __('ui.first') }}</a><a class="{{ $priests->onFirstPage()?'disabled':'' }}" @if(!$priests->onFirstPage()) href="{{ $priests->previousPageUrl() }}" @endif>{{ __('ui.previous') }}</a><a class="{{ $priests->hasMorePages()?'':'disabled' }}" @if($priests->hasMorePages()) href="{{ $priests->nextPageUrl() }}" @endif>{{ __('ui.next') }}</a></div></div>
</section>

<section class="card section" id="admin-history-section">
    <h2>{{ __('ui.verification_history') }}</h2>
    @forelse($verificationHistory as $entry)
        <p><span class="pill">{{ __('ui.'.$entry->decision) }}</span> {{ $entry->priest->name }} · {{ __('ui.'.$entry->type) }} — {{ $entry->admin->name }} <small class="muted">{{ $entry->created_at->translatedFormat('M j, Y · g:i A') }}</small>@if($entry->reason)<br><span class="muted">{{ $entry->reason }}</span>@endif</p>
    @empty<p class="muted">{{ __('ui.no_verification_history') }}</p>@endforelse
    <div class="admin-pagination"><span>{{ __('ui.pagination_showing',['from'=>$verificationHistory->firstItem()??0,'to'=>$verificationHistory->lastItem()??0,'total'=>$verificationHistory->total()]) }} · {{ __('ui.pagination_page',['current'=>$verificationHistory->currentPage(),'last'=>$verificationHistory->lastPage()]) }}</span><div class="admin-pagination-actions"><a class="{{ $verificationHistory->onFirstPage()?'disabled':'' }}" @if(!$verificationHistory->onFirstPage()) href="{{ $verificationHistory->url(1) }}" @endif>{{ __('ui.first') }}</a><a class="{{ $verificationHistory->onFirstPage()?'disabled':'' }}" @if(!$verificationHistory->onFirstPage()) href="{{ $verificationHistory->previousPageUrl() }}" @endif>{{ __('ui.previous') }}</a><a class="{{ $verificationHistory->hasMorePages()?'':'disabled' }}" @if($verificationHistory->hasMorePages()) href="{{ $verificationHistory->nextPageUrl() }}" @endif>{{ __('ui.next') }}</a></div></div>
</section>

<section class="card section" id="admin-appointments-section">
    <h2>{{ __('ui.admin_recent_appointments') }}</h2>
    <div style="overflow-x:auto"><table class="table"><thead><tr><th>{{ __('ui.admin_service') }}</th><th>{{ __('ui.admin_date_time') }}</th><th>{{ __('ui.admin_priest') }}</th><th>{{ __('ui.admin_requester') }}</th><th>{{ __('ui.admin_status') }}</th><th>{{ __('ui.admin_requested') }}</th></tr></thead><tbody>
    @forelse($recentAppointments as $appointment)
        <tr><td>{{ __('ui.'.$appointment->service_type) }}</td><td>{{ $appointment->starts_at->format('M j, Y g:i A') }}</td><td>{{ $appointment->priest?->name ?? '—' }}</td><td>{{ $appointment->requester?->name ?? '—' }}</td><td><span class="pill">{{ __('ui.'.$appointment->status) }}</span></td><td>{{ $appointment->created_at->format('M j, Y') }}</td></tr>
    @empty<tr><td colspan="6">{{ __('ui.admin_no_activity') }}</td></tr>@endforelse
    </tbody></table></div>
    <div class="admin-pagination"><span>{{ __('ui.pagination_showing',['from'=>$recentAppointments->firstItem()??0,'to'=>$recentAppointments->lastItem()??0,'total'=>$recentAppointments->total()]) }} · {{ __('ui.pagination_page',['current'=>$recentAppointments->currentPage(),'last'=>$recentAppointments->lastPage()]) }}</span><div class="admin-pagination-actions"><a class="{{ $recentAppointments->onFirstPage()?'disabled':'' }}" @if(!$recentAppointments->onFirstPage()) href="{{ $recentAppointments->url(1) }}" @endif>{{ __('ui.first') }}</a><a class="{{ $recentAppointments->onFirstPage()?'disabled':'' }}" @if(!$recentAppointments->onFirstPage()) href="{{ $recentAppointments->previousPageUrl() }}" @endif>{{ __('ui.previous') }}</a><a class="{{ $recentAppointments->hasMorePages()?'':'disabled' }}" @if($recentAppointments->hasMorePages()) href="{{ $recentAppointments->nextPageUrl() }}" @endif>{{ __('ui.next') }}</a></div></div>
</section>
@endsection
