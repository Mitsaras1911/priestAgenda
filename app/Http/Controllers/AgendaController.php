<?php
namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Http\Request;
use App\Support\AppointmentNotifier;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class AgendaController extends Controller
{
    public function index()
    {
        $user=request()->user();
        if($user?->isPriest() || $user?->isAdmin()) return redirect()->route('dashboard.index');
        $filters=request()->validate(['search'=>'nullable|string|max:120','city'=>['nullable','string',Rule::in(config('priest.cities'))],'date_from'=>'nullable|date|after_or_equal:today','date_to'=>'nullable|date|after_or_equal:today','service_type'=>'nullable|in:confession,christening,marriage']);
        if(!empty($filters['date_from']) xor !empty($filters['date_to'])) throw \Illuminate\Validation\ValidationException::withMessages(['date_to'=>__('ui.date_range_both_required')]);
        if(!empty($filters['date_from'])&&!empty($filters['date_to'])&&$filters['date_to']<$filters['date_from']) throw \Illuminate\Validation\ValidationException::withMessages(['date_to'=>__('ui.date_range_order')]);
        $query=User::where('role','priest')->where('is_priest_verified',true)->whereNotNull('slug');
        if(!empty($filters['search'])) $query->where('name','like','%'.str_replace(['%','_'],['\\%','\\_'],$filters['search']).'%');
        if(!empty($filters['city'])) $query->where('city',$filters['city']);
        if(!empty($filters['service_type'])) {
            if($filters['service_type']==='confession') $query->where('is_confessor',true)->where('is_confessor_verified',true);
            $query->whereHas('availabilities',fn($availability)=>$availability->where('service_type',$filters['service_type']));
        }
        $priestsQuery=$query->orderBy('name');
        if(!empty($filters['date_from'])&&!empty($filters['date_to'])) {
            $from=\Carbon\Carbon::parse($filters['date_from'])->startOfDay();
            $until=\Carbon\Carbon::parse($filters['date_to'])->endOfDay();
            if($from->diffInDays($until)>365) throw \Illuminate\Validation\ValidationException::withMessages(['date_to'=>__('ui.date_range_maximum')]);
            $fromString=$from->toDateString();
            $untilString=$until->toDateString();
            $serviceTypes=!empty($filters['service_type'])?[$filters['service_type']]:['confession','christening','marriage'];
            $priests=$priestsQuery->with([
                'availabilities'=>fn($rules)=>$rules->whereIn('service_type',$serviceTypes),
                'blockedDates'=>fn($blocks)=>$blocks->overlappingPeriod($fromString,$untilString)->whereIn('service_type',array_merge(['all'],$serviceTypes)),
                'appointments'=>fn($appointments)=>$appointments->whereIn('status',['pending','confirmed'])->whereBetween('starts_at',[$from,$until]),
            ])->get()->filter(fn(User $priest)=>$this->hasAvailableSlotInRange($priest,$from,$until))->values();
        } else {
            $priests=$priestsQuery->get();
        }
        $catalog=json_decode(file_get_contents(resource_path('data/saint_sayings.json')),true,512,JSON_THROW_ON_ERROR);
        $sayings=collect($catalog['sayings']);
        $featuredIds=[1,151,7];
        $selectedSayings=$sayings->whereIn('id',$featuredIds)
            ->concat($sayings->where('kind','church_father')->reject(fn($saying)=>in_array($saying['id'],$featuredIds,true))->take(47));
        $teachings=$selectedSayings->map(function($saying)use($catalog){
            $author=$saying['attribution']?:__('ui.unknown_father');
            $image='/images/elders/orthodox-cross.svg';
            $imageAlt=$author;
            $imageCredit=null;
            $imageUrl=null;
            $text=$saying['text'];

            if($saying['id']===1){$author=__('ui.saint_isaac');$text=__('ui.teaching_isaac');$image='https://commons.wikimedia.org/wiki/Special:FilePath/Isak_Sirin.jpg';$imageAlt=__('ui.isaac_icon_alt');$imageCredit='Wikimedia Commons · public domain';$imageUrl='https://commons.wikimedia.org/wiki/File:Isak_Sirin.jpg';}
            elseif($saying['id']===151){$author=__('ui.saint_paisios');$text=__('ui.teaching_paisios');$image='/images/elders/paisios.jpg';$imageAlt=__('ui.paisios_icon_alt');$imageCredit='Wikimedia Commons · Spartacos31 · CC BY 4.0';$imageUrl='https://commons.wikimedia.org/wiki/File:Paisios_of_Mount_Athos.jpg';}
            elseif($saying['id']===7){$author=__('ui.saint_chrysostom');$text=__('ui.teaching_chrysostom');$image='https://commons.wikimedia.org/wiki/Special:FilePath/Chrysostomos.jpg';$imageAlt=__('ui.chrysostom_icon_alt');$imageCredit='Wikimedia Commons · public domain';$imageUrl='https://commons.wikimedia.org/wiki/File:Chrysostomos.jpg';}
            elseif(str_contains($author,'Παΐσ')){$image='/images/elders/paisios.jpg';$imageAlt=$author;$imageCredit='Wikimedia Commons · Spartacos31 · CC BY 4.0';$imageUrl='https://commons.wikimedia.org/wiki/File:Paisios_of_Mount_Athos.jpg';}
            elseif(str_contains($author,'Σιλουαν')){$image='/images/elders/silouan.jpg';}
            elseif(str_contains($author,'Ἰωσὴφ')||str_contains($author,'Ιωσήφ')){$image='/images/elders/joseph.jpg';}
            elseif(str_contains($author,'Ἰσαὰκ')||str_contains($author,'Ισαάκ')){$image='https://commons.wikimedia.org/wiki/Special:FilePath/Isak_Sirin.jpg';$imageCredit='Wikimedia Commons · public domain';$imageUrl='https://commons.wikimedia.org/wiki/File:Isak_Sirin.jpg';}
            elseif(str_contains($author,'Χρυσόστομος')){$image='https://commons.wikimedia.org/wiki/Special:FilePath/Chrysostomos.jpg';$imageCredit='Wikimedia Commons · public domain';$imageUrl='https://commons.wikimedia.org/wiki/File:Chrysostomos.jpg';}

            return ['name'=>$author,'text'=>$text,'original_text'=>$saying['text'],'image'=>$image,'image_alt'=>$imageAlt,'image_credit'=>$imageCredit,'image_url'=>$imageUrl,'source_url'=>$catalog['source_url']];
        })->values();
        return view('home',[
            'priests'=>$priests,
            'filters'=>$filters,
            'priestNames'=>User::where('role','priest')->where('is_priest_verified',true)->whereNotNull('slug')->orderBy('name')->pluck('name')->unique(),
            'cities'=>config('priest.cities'),
            'teachings'=>$teachings,
        ]);
    }
    private function hasAvailableSlotInRange(User $priest,\Carbon\Carbon $from,\Carbon\Carbon $until): bool
    {
        for($date=$from->copy()->startOfDay();$date->lte($until);$date->addDay()) {
            foreach($priest->availabilities->where('weekday',$date->dayOfWeek) as $rule) {
                if($rule->service_type==='confession'&&(!$priest->is_confessor||!$priest->is_confessor_verified)) continue;
                $dateString=$date->toDateString();
                $blocked=$priest->blockedDates->contains(function($block)use($dateString,$rule){
                    if($block->service_type!=='all'&&$block->service_type!==$rule->service_type) return false;
                    $start=$block->date->toDateString();
                    $end=($block->end_date??$block->date)->toDateString();
                    return $start<=$dateString&&$end>=$dateString;
                });
                if($blocked) continue;
                $duration=$rule->service_type==='confession'?(int)$rule->slot_minutes:60;
                $cursor=\Carbon\Carbon::parse($dateString.' '.$rule->starts_at);
                $end=\Carbon\Carbon::parse($dateString.' '.$rule->ends_at);
                while($cursor->copy()->addMinutes($duration)->lte($end)) {
                    $occupied=$priest->appointments->contains(function($appointment)use($cursor,$duration){
                        $start=$appointment->starts_at;
                        $finish=$start->copy()->addMinutes($appointment->duration_minutes);
                        return $cursor->lt($finish)&&$cursor->copy()->addMinutes($duration)->gt($start);
                    });
                    if($cursor->gt(now())&&!$occupied) return true;
                    $cursor->addMinutes($duration);
                }
            }
        }
        return false;
    }
    public function show(User $priest) { abort_unless($priest->isPriest()&&$priest->is_priest_verified,404); return view('priests.show',['priest'=>$priest]); }
    public function availableDates(Request $request, User $priest)
    {
        abort_unless($priest->isPriest()&&$priest->is_priest_verified,404);
        $data=$request->validate(['month'=>'required|date_format:Y-m','service_type'=>'required|in:confession,christening,marriage']);
        abort_if($data['service_type']==='confession'&&(!$priest->is_confessor||!$priest->is_confessor_verified),422,'This priest is not verified to accept confession appointments.');
        $month=\Carbon\Carbon::createFromFormat('!Y-m',$data['month']);
        abort_if($month->lt(now()->startOfMonth()),422,'Choose the current month or a future month.');
        $from=$month->copy()->startOfMonth()->startOfDay();
        $until=$month->copy()->endOfMonth()->endOfDay();
        $service=$data['service_type'];
        $rules=$priest->availabilities()->where('service_type',$service)->get()->groupBy('weekday');
        $blocks=$priest->blockedDates()->overlappingPeriod($from->toDateString(),$until->toDateString())->whereIn('service_type',['all',$service])->get();
        $appointments=$priest->appointments()->whereIn('status',['pending','confirmed'])->whereBetween('starts_at',[$from,$until])->get();
        $dates=[];

        for($day=$from->copy();$day->lte($until);$day->addDay()) {
            if($day->isPast() && !$day->isToday()) continue;
            $dateString=$day->toDateString();
            $isBlocked=$blocks->contains(function($block)use($dateString){
                $start=$block->date->toDateString();
                $end=($block->end_date??$block->date)->toDateString();
                return $start<=$dateString && $end>=$dateString;
            });
            if($isBlocked) continue;

            foreach($rules->get($day->dayOfWeek,collect()) as $rule) {
                $duration=$service==='confession'?(int)$rule->slot_minutes:60;
                $cursor=\Carbon\Carbon::parse($dateString.' '.$rule->starts_at);
                $end=\Carbon\Carbon::parse($dateString.' '.$rule->ends_at);
                while($cursor->copy()->addMinutes($duration)->lte($end)) {
                    $occupied=$appointments->contains(function($appointment)use($cursor,$duration){
                        $appointmentStart=$appointment->starts_at;
                        $appointmentEnd=$appointmentStart->copy()->addMinutes($appointment->duration_minutes);
                        return $cursor->lt($appointmentEnd)&&$cursor->copy()->addMinutes($duration)->gt($appointmentStart);
                    });
                    if($cursor->gt(now())&&!$occupied) {
                        $dates[]=$dateString;
                        break 2;
                    }
                    $cursor->addMinutes($duration);
                }
            }
        }

        return response()->json(['month'=>$month->format('Y-m'),'service_type'=>$service,'dates'=>$dates]);
    }
    public function availability(Request $request, User $priest)
    {
        abort_unless($priest->isPriest()&&$priest->is_priest_verified,404);
        $data = $request->validate(['date'=>'required|date|after_or_equal:today','service_type'=>'required|in:confession,christening,marriage']);
        abort_if($data['service_type']==='confession'&&(!$priest->is_confessor||!$priest->is_confessor_verified),422,'This priest is not verified to accept confession appointments.');
        $date = \Carbon\Carbon::parse($data['date'])->startOfDay(); $service = $data['service_type'];
        $slots=[];
        $blocked=$priest->blockedDates()->containingDate($date)->whereIn('service_type',['all',$service])->exists();
        if (!$blocked) {
            $rules=$priest->availabilities()->where('service_type',$service)->where('weekday',$date->dayOfWeek)->get();
            foreach($rules as $rule) {
                $duration=$service==='confession' ? (int)$rule->slot_minutes : 60;
                $cursor=\Carbon\Carbon::parse($date->toDateString().' '.$rule->starts_at); $end=\Carbon\Carbon::parse($date->toDateString().' '.$rule->ends_at);
                while($cursor->copy()->addMinutes($duration)->lte($end)) {
                    $occupied=$priest->appointments()->whereIn('status',['pending','confirmed'])->whereDate('starts_at',$date)->get()->contains(function($appointment) use($cursor,$duration) {
                        $a=$appointment->starts_at; $b=$a->copy()->addMinutes($appointment->duration_minutes);
                        return $cursor->lt($b) && $cursor->copy()->addMinutes($duration)->gt($a);
                    });
                    if ($cursor->gt(now()) && !$occupied) $slots[]=$cursor->format('H:i');
                    $cursor->addMinutes($duration);
                }
            }
        }
        return response()->json(['slots'=>array_values(array_unique($slots))]);
    }
    public function store(Request $request, User $priest)
    {
        abort_unless($priest->isPriest()&&$priest->is_priest_verified,404);
        $data=$request->validate(['service_type'=>'required|in:confession,christening,marriage','starts_at'=>'required|date|after:now','outside_ceremony'=>'nullable|boolean','message'=>'nullable|required_if:outside_ceremony,1|string|max:2000']);
        $appt=DB::transaction(function()use($request,$priest,$data){
            $lockedPriest=User::whereKey($priest->id)->lockForUpdate()->firstOrFail();
            abort_if($data['service_type']==='confession'&&(!$lockedPriest->is_confessor||!$lockedPriest->is_confessor_verified),422,'This priest is not verified to accept confession appointments.');
            $start=\Carbon\Carbon::parse($data['starts_at']);$type=$data['service_type'];$duration=$type==='confession'?null:60;
            $rule=$lockedPriest->availabilities()->where('service_type',$type)->where('weekday',$start->dayOfWeek)->get()->first(function($rule)use($start,$type,&$duration){$from=\Carbon\Carbon::parse($start->toDateString().' '.$rule->starts_at);$until=\Carbon\Carbon::parse($start->toDateString().' '.$rule->ends_at);$step=$type==='confession'?(int)$rule->slot_minutes:60;if($start->second!==0||$start->lt($from)||$start->gte($until)||$from->diffInMinutes($start)%$step!==0||$start->copy()->addMinutes($step)->gt($until))return false;$duration=$step;return true;});
            abort_unless($rule,422,'That time is not one of the available slots.');
            abort_if($lockedPriest->blockedDates()->containingDate($start->toDateString())->whereIn('service_type',['all',$type])->exists(),422,'That date is unavailable.');
            $overlap=$lockedPriest->appointments()->whereIn('status',['pending','confirmed'])->where('starts_at','<',$start->copy()->addMinutes($duration))->get()->contains(fn($a)=>$a->starts_at->copy()->addMinutes($a->duration_minutes)->gt($start));
            abort_if($overlap,422,'That time was just requested. Please choose another slot.');
            return $lockedPriest->appointments()->create(['requester_id'=>$request->user()->id,'service_type'=>$type,'starts_at'=>$start,'duration_minutes'=>$duration,'status'=>'pending','message'=>$data['message']??null]);
        });
        AppointmentNotifier::requested($appt);
        return back()->with('success','Your request has been sent to the priest for review.');
    }
}
