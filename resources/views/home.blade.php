@extends('layouts.app')
@section('content')
<section class="hero hero-orthodox">
    <div><span class="eyebrow">{{ __('ui.orthodox_appointments') }}</span><h1>@auth{{ __('ui.welcome_user',['name'=>auth()->user()->name]) }}@else{{ __('ui.hero_title') }}@endauth</h1><p class="muted">{{ __('ui.hero_intro') }}</p><div class="cross-rule" aria-hidden="true">☦</div></div>
    <div class="icon-pair" aria-label="{{ __('ui.sacred_icons') }}">
        <figure class="icon-frame"><img src="https://commons.wikimedia.org/wiki/Special:FilePath/Spas_vsederzhitel_sinay.jpg?width=900" alt="{{ __('ui.christ_icon_alt') }}"><figcaption class="icon-caption"><a href="https://commons.wikimedia.org/wiki/File:Spas_vsederzhitel_sinay.jpg" target="_blank" rel="noopener">{{ __('ui.christ_icon_credit') }}</a></figcaption></figure>
        <figure class="icon-frame"><img src="https://commons.wikimedia.org/wiki/Special:FilePath/Theotokos,_the_Orthodox_icon.jpg?width=900" alt="{{ __('ui.mary_icon_alt') }}"><figcaption class="icon-caption"><a href="https://commons.wikimedia.org/wiki/File:Theotokos,_the_Orthodox_icon.jpg" target="_blank" rel="noopener">{{ __('ui.mary_icon_credit') }}</a></figcaption></figure>
    </div>
</section>
<section class="teaching-section" aria-labelledby="teaching-heading">
    <div class="teaching-heading"><span class="eyebrow">☦ {{ __('ui.orthodox_appointments') }}</span><h2 id="teaching-heading">{{ __('ui.elder_teachings') }}</h2><p class="muted">{{ __('ui.elder_teachings_intro') }}</p></div>
    <div class="teaching-carousel" id="teaching-carousel" aria-roledescription="carousel" aria-label="{{ __('ui.elder_teachings') }}">
        @foreach($teachings as $index=>$teaching)
        <article class="teaching-slide" data-teaching-slide @if($index>0)hidden aria-hidden="true"@else aria-hidden="false"@endif>
            <img class="elder-portrait" src="{{ $teaching['image'] }}" alt="{{ $teaching['image_alt'] }}" width="84" height="84" loading="lazy">
            <div class="teaching-copy"><p class="teaching-text" @if($teaching['text']===$teaching['original_text']) lang="el" @endif>{{ $teaching['text'] }}</p>@if(app()->getLocale()!=='el' && $teaching['text']!==$teaching['original_text'])<p class="teaching-original" lang="el">{{ $teaching['original_text'] }}</p>@endif<p class="teaching-author">{{ $teaching['name'] }}</p><small class="image-credit">@if($teaching['image_credit'] && $teaching['image_url'])<a href="{{ $teaching['image_url'] }}" target="_blank" rel="noopener">{{ $teaching['image_credit'] }}</a> · @endif<a href="{{ $teaching['source_url'] }}" target="_blank" rel="noopener">{{ __('ui.teaching_source') }}</a></small></div>
        </article>
        @endforeach
        <div class="teaching-controls"><button type="button" class="teaching-arrow" data-teaching-prev aria-label="{{ __('ui.previous_teaching') }}">←</button><button type="button" class="teaching-arrow" data-teaching-next aria-label="{{ __('ui.next_teaching') }}">→</button></div>
    </div>
</section>
@if(!auth()->check() || (!auth()->user()->isPriest() && !auth()->user()->isAdmin()))
<section class="section">
    <div class="cross-rule" aria-hidden="true">☦</div><h2>{{ __('ui.priests') }}</h2>
    <form method="get" action="{{ route('home') }}" class="card section priest-filter-form">
        <div class="row">
            <div class="field"><label for="priest-search">{{ __('ui.search_priests') }}</label><input id="priest-search" name="search" value="{{ $filters['search'] ?? '' }}" list="priest-name-suggestions" autocomplete="on" placeholder="{{ __('ui.search_priest_placeholder') }}"><datalist id="priest-name-suggestions">@foreach($priestNames as $name)<option value="{{ $name }}">@endforeach</datalist></div>
            <div class="field"><label>{{ __('ui.search_by_date_range') }}</label><div class="row"><div class="field"><label for="available-from">{{ __('ui.from') }}</label><input type="date" id="available-from" name="date_from" min="{{ now()->toDateString() }}" max="{{ $filters['date_to'] ?? '' }}" value="{{ $filters['date_from'] ?? '' }}"></div><div class="field"><label for="available-until">{{ __('ui.until') }}</label><input type="date" id="available-until" name="date_to" min="{{ $filters['date_from'] ?? now()->toDateString() }}" value="{{ $filters['date_to'] ?? '' }}"></div></div></div>
            <div class="field"><label for="available-service">{{ __('ui.appointment_type') }}</label><select id="available-service" name="service_type"><option value="">{{ __('ui.any_appointment_type') }}</option><option value="confession" @selected(($filters['service_type'] ?? '')==='confession')>{{ __('ui.confession') }}</option><option value="christening" @selected(($filters['service_type'] ?? '')==='christening')>{{ __('ui.christening') }}</option><option value="marriage" @selected(($filters['service_type'] ?? '')==='marriage')>{{ __('ui.marriage') }}</option></select></div>
            <div class="field"><label for="city-filter">{{ __('ui.city') }}</label><select id="city-filter" name="city"><option value="">{{ __('ui.any_city') }}</option>@foreach($cities as $city)<option value="{{ $city }}" @selected(($filters['city'] ?? '')===$city)>{{ $city }}</option>@endforeach</select></div>
        </div>
        <button class="btn">{{ __('ui.apply_filters') }}</button> <a class="btn secondary" href="{{ route('home') }}">{{ __('ui.clear_filters') }}</a>
    </form>
    @if($priests->isEmpty())<div class="card"><p>{{ __('ui.no_priests_match') }}</p></div>
    @else<div class="grid priest-card-grid" id="priest-card-grid">@foreach($priests as $priest)
        <article class="priest-card" data-priest-card data-priest-name="{{ $priest->name }}">
            <div class="priest-card-inner">
                <section class="priest-card-face priest-card-front">
                    <span class="priest-card-cross" aria-hidden="true">☦</span>
                    <h3>{{ $priest->name }}</h3>@if($priest->is_confessor && $priest->is_confessor_verified)<span class="pill">{{ __('ui.is_confessor') }}</span>@endif
                    <p class="priest-card-location">{{ $priest->age ? __('ui.age').': '.$priest->age : '' }}{{ $priest->age && $priest->city ? ' · ' : '' }}{{ $priest->city }}</p>
                    <div class="priest-card-actions"><a class="btn" href="{{ route('priests.show',$priest->slug) }}#profile-details">{{ __('ui.priest_profile_button') }}</a><a class="btn secondary" href="{{ route('priests.show',$priest->slug) }}#appointment-booking">{{ __('ui.priest_availability_button') }}</a></div>
                    <button type="button" class="priest-card-flip" data-flip-card aria-expanded="false">{{ __('ui.priest_card_details') }} <span aria-hidden="true">↻</span></button>
                </section>
                <section class="priest-card-face priest-card-back" aria-hidden="true">
                    <span class="eyebrow">{{ __('ui.orthodox_priest') }}</span>
                    <h3>{{ $priest->name }}</h3>@if($priest->is_confessor && $priest->is_confessor_verified)<span class="pill">{{ __('ui.is_confessor') }}</span>@endif
                    <dl><dt>{{ __('ui.parish') }}</dt><dd>{{ $priest->parish ?: '—' }}</dd><dt>{{ __('ui.marital_status') }}</dt><dd>{{ is_null($priest->is_married) ? __('ui.not_specified') : ($priest->is_married ? __('ui.married') : __('ui.not_married')) }}</dd></dl>
                    <p class="priest-card-bio">{{ $priest->bio ?: __('ui.profile_welcome') }}</p>
                    <button type="button" class="priest-card-flip" data-flip-card aria-expanded="true">{{ __('ui.priest_card_back') }} <span aria-hidden="true">↻</span></button>
                </section>
            </div>
        </article>
    @endforeach</div><div class="card" id="priest-search-empty" role="status" hidden><p>{{ __('ui.no_priests_match') }}</p></div>@endif
</section>
@endif
@endsection
@push('scripts')
<script>
(()=>{const carousel=document.getElementById('teaching-carousel');if(!carousel)return;const slides=[...carousel.querySelectorAll('[data-teaching-slide]')];let current=0,timer;const show=index=>{current=(index+slides.length)%slides.length;slides.forEach((slide,i)=>{slide.hidden=i!==current;slide.setAttribute('aria-hidden',String(i!==current))})};const stop=()=>{clearInterval(timer);timer=null};const start=()=>{if(!timer&&!window.matchMedia('(prefers-reduced-motion: reduce)').matches)timer=setInterval(()=>show(current+1),10000)};carousel.querySelector('[data-teaching-prev]').addEventListener('click',()=>show(current-1));carousel.querySelector('[data-teaching-next]').addEventListener('click',()=>show(current+1));carousel.addEventListener('mouseenter',stop);carousel.addEventListener('mouseleave',start);carousel.addEventListener('focusin',stop);carousel.addEventListener('focusout',event=>{if(!carousel.contains(event.relatedTarget))start()});start()})();
</script>
<script>
(()=>{const input=document.getElementById('priest-search'),grid=document.getElementById('priest-card-grid'),empty=document.getElementById('priest-search-empty');if(!input||!grid)return;const cards=[...grid.querySelectorAll('[data-priest-card]')];const filter=()=>{const query=input.value.trim().toLocaleLowerCase();let matches=0;cards.forEach(card=>{const matched=card.dataset.priestName.toLocaleLowerCase().includes(query);card.hidden=!matched;if(matched)matches++});if(empty)empty.hidden=matches!==0};input.addEventListener('input',filter);filter()})();
</script>
<script>
(()=>{const form=document.querySelector('.priest-filter-form'),from=document.getElementById('available-from'),until=document.getElementById('available-until'),service=document.getElementById('available-service');if(!form||!from||!until||!service)return;const submitIfReady=()=>{if(from.value&&until.value)form.requestSubmit()};from.addEventListener('change',()=>{until.min=from.value||until.min;if(until.value&&until.value<from.value)until.value='';submitIfReady()});until.addEventListener('change',()=>{from.max=until.value;submitIfReady()});service.addEventListener('change',submitIfReady)})();
</script>
@endpush
