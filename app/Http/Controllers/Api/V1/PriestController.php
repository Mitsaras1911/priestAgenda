<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Availability;
use App\Models\BlockedDate;
use App\Models\User;
use App\Notifications\ConfessorVerificationRequested;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class PriestController extends Controller
{
    public function index() { return response()->json(['data'=>User::where('role','priest')->where('is_priest_verified',true)->whereNotNull('slug')->orderBy('name')->get()->map(fn($p)=>$this->profile($p))]); }
    public function show(User $priest) { abort_unless($priest->isPriest()&&$priest->is_priest_verified,404); return response()->json(['data'=>$this->profile($priest,true)]); }

    public function availability(Request $request, User $priest)
    {
        abort_unless($priest->isPriest()&&$priest->is_priest_verified,404);
        $data=$request->validate(['date'=>'required|date|after_or_equal:today','service_type'=>'required|in:confession,christening,marriage']);
        abort_if($data['service_type']==='confession'&&(!$priest->is_confessor||!$priest->is_confessor_verified),422,'This priest is not verified to accept confession appointments.');
        $date=Carbon::parse($data['date'])->startOfDay();$service=$data['service_type'];$slots=[];
        $blocked=$priest->blockedDates()->containingDate($date)->whereIn('service_type',['all',$service])->exists();
        if(!$blocked) foreach($priest->availabilities()->where('service_type',$service)->where('weekday',$date->dayOfWeek)->get() as $rule) {
            $duration=$service==='confession' ? (int)$rule->slot_minutes : 60;
            $cursor=Carbon::parse($date->toDateString().' '.$rule->starts_at);$end=Carbon::parse($date->toDateString().' '.$rule->ends_at);
            while($cursor->copy()->addMinutes($duration)->lte($end)) {
                $occupied=$priest->appointments()->whereIn('status',['pending','confirmed'])->whereDate('starts_at',$date)->get()->contains(function($a)use($cursor,$duration){$from=$a->starts_at;$to=$from->copy()->addMinutes($a->duration_minutes);return $cursor->lt($to)&&$cursor->copy()->addMinutes($duration)->gt($from);});
                if($cursor->gt(now())&&!$occupied)$slots[]=$cursor->format('H:i');$cursor->addMinutes($duration);
            }
        }
        return response()->json(['date'=>$date->toDateString(),'service_type'=>$service,'slots'=>array_values(array_unique($slots))]);
    }

    public function update(Request $request)
    {
        $priest=$this->priest($request);$data=$request->validate(['name'=>'required|string|max:120','parish'=>'nullable|string|max:160','city'=>['nullable','string',Rule::in(config('priest.cities'))],'is_confessor'=>'sometimes|boolean','bio'=>'nullable|string|max:3000']);
        $newConfessorDeclaration=array_key_exists('is_confessor',$data) && (bool)$data['is_confessor'] && !$priest->is_confessor;
        abort_if($priest->is_confessor_verified && array_key_exists('is_confessor',$data) && !$data['is_confessor'],422,'An approved confessor status can only be changed by an administrator.');
        if(array_key_exists('is_confessor',$data) && !$data['is_confessor']) $priest->is_confessor_verified=false;
        elseif($newConfessorDeclaration) $priest->is_confessor_verified=false;
        $priest->update($data+['slug'=>$priest->slug?:Str::slug($data['name']).'-'.$priest->id]);if($newConfessorDeclaration) Notification::send(User::where('role','admin')->get(),new ConfessorVerificationRequested($priest));return response()->json(['data'=>$this->profile($priest,true)]);
    }
    public function storeAvailability(Request $request)
    {
        $priest=$this->priest($request);$data=$request->validate(['service_type'=>'required|in:christening,marriage,confession,liturgy','weekdays'=>'required_without:weekday|array|min:1','weekdays.*'=>'required|integer|distinct|between:0,6','weekday'=>'required_without:weekdays|integer|between:0,6','starts_at'=>'required|date_format:H:i','ends_at'=>'required|date_format:H:i|after:starts_at','slot_minutes'=>'required_if:service_type,confession|nullable|integer|between:15,240']);
        $data['slot_minutes']=$data['service_type']==='confession' ? (int)$data['slot_minutes'] : 60;
        $days=$data['weekdays']??[$data['weekday']];unset($data['weekdays'],$data['weekday']);
        if(in_array($data['service_type'],['christening','marriage'],true)&&count(array_diff($days,[0,6]))>0)throw \Illuminate\Validation\ValidationException::withMessages(['weekdays'=>'Christenings and marriages can only be set on Saturdays or Sundays.']);
        $rules=DB::transaction(function()use($priest,$days,$data){$created=[];foreach($days as $weekday)$created[]=$priest->availabilities()->firstOrCreate($data+['weekday'=>$weekday]);return $created;});
        return response()->json(['data'=>$rules],201);
    }
    public function deleteAvailability(Request $request,Availability $availability) { abort_unless($availability->priest_id===$this->priest($request)->id,403);$availability->delete();return response()->json(['message'=>'Availability deleted.']); }
    public function storeBlockedDate(Request $request)
    {
        $priest=$this->priest($request);$data=$request->validate(['date'=>'required|date|after_or_equal:today','end_date'=>'nullable|date|after_or_equal:date','service_type'=>'required|in:all,christening,marriage,confession,liturgy','reason'=>'nullable|string|max:160']);
        return response()->json(['data'=>$priest->blockedDates()->create($data)],201);
    }
    public function deleteBlockedDate(Request $request,BlockedDate $blockedDate) { abort_unless($blockedDate->priest_id===$this->priest($request)->id,403);$blockedDate->delete();return response()->json(['message'=>'Blocked date deleted.']); }

    private function priest(Request $request): User { abort_unless($request->user()->isPriest(),403,'Priest accounts only.');return $request->user(); }
    private function profile(User $p,bool $hours=false): array
    {
        $out=['id'=>$p->id,'name'=>$p->name,'slug'=>$p->slug,'parish'=>$p->parish,'city'=>$p->city,'is_priest_verified'=>(bool)$p->is_priest_verified,'is_confessor'=>(bool)$p->is_confessor,'is_confessor_verified'=>(bool)$p->is_confessor_verified,'bio'=>$p->bio];
        if($hours)$out['weekly_availability']=$p->availabilities()->whereIn('service_type',['confession','christening','marriage'])->orderBy('service_type')->orderBy('weekday')->get(['id','service_type','weekday','starts_at','ends_at','slot_minutes'])->map(function($rule){if($rule->service_type!=='confession')$rule->slot_minutes=60;return $rule;});
        return $out;
    }
}
