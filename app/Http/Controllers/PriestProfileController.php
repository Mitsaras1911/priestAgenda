<?php
namespace App\Http\Controllers;
use App\Models\Availability;
use App\Models\BlockedDate;
use App\Models\User;
use App\Notifications\ConfessorVerificationRequested;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
class PriestProfileController extends Controller
{
    private function priest(Request $request) { abort_unless($request->user()->isPriest(),403,'Priest accounts only.'); return $request->user(); }
    public function edit(Request $request) {
        $p=$this->priest($request);
        $editingRule=$request->filled('edit_availability') ? $p->availabilities()->findOrFail($request->integer('edit_availability')) : null;
        $editingBlock=$request->filled('edit_block') ? $p->blockedDates()->findOrFail($request->integer('edit_block')) : null;
        return view('dashboard.profile',['priest'=>$p,'rules'=>$p->availabilities()->orderBy('service_type')->orderBy('weekday')->get(),'blocks'=>$p->blockedDates()->orderByDesc('date')->get(),'editingRule'=>$editingRule,'editingBlock'=>$editingBlock]);
    }
    public function update(Request $request) {
        $p=$this->priest($request); $d=$request->validate(['name'=>'required|string|max:120','parish'=>'nullable|string|max:160','city'=>['nullable','string',Rule::in(config('priest.cities'))],'age'=>'nullable|integer|between:18,100','is_married'=>'nullable|boolean','is_confessor'=>'required|boolean','bio'=>'nullable|string|max:3000']);
        $newConfessorDeclaration=(bool)$d['is_confessor'] && !$p->is_confessor;
        abort_if($p->is_confessor_verified && !(bool)$d['is_confessor'],422,__('ui.confessor_approved_locked'));
        if(!$d['is_confessor']) $p->is_confessor_verified=false;
        elseif($newConfessorDeclaration) $p->is_confessor_verified=false;
        $p->update($d+['slug'=>$p->slug ?: Str::slug($d['name']).'-'.$p->id]);
        if($newConfessorDeclaration) Notification::send(User::where('role','admin')->get(),new ConfessorVerificationRequested($p));
        return back()->with('success',__('ui.profile_saved'));
    }
    public function storeAvailability(Request $request) {
        $p=$this->priest($request); $d=$request->validate(['service_type'=>'required|in:christening,marriage,confession,liturgy','weekdays'=>'required|array|min:1','weekdays.*'=>'required|integer|distinct|between:0,6','starts_at'=>'required|date_format:H:i','ends_at'=>'required|date_format:H:i|after:starts_at','slot_minutes'=>'required_if:service_type,confession|nullable|integer|between:15,240']);
        $d['slot_minutes']=$d['service_type']==='confession' ? (int)$d['slot_minutes'] : 60;
        $days=$d['weekdays'];unset($d['weekdays']);
        if(in_array($d['service_type'],['christening','marriage'],true)&&count(array_diff($days,[0,6]))>0)throw \Illuminate\Validation\ValidationException::withMessages(['weekdays'=>'Christenings and marriages can only be set on Saturdays or Sundays.']);
        DB::transaction(function()use($p,$d,$days){foreach($days as $weekday)$p->availabilities()->firstOrCreate($d+['weekday'=>$weekday]);});
        return back()->with('success',__('ui.weekly_hours_saved',['count'=>count($days)]));
    }
    public function updateAvailability(Request $request, Availability $availability) {
        $p=$this->priest($request); abort_unless($availability->priest_id===$p->id,403);
        $d=$request->validate(['service_type'=>'required|in:christening,marriage,confession,liturgy','weekday'=>'required|integer|between:0,6','starts_at'=>'required|date_format:H:i','ends_at'=>'required|date_format:H:i|after:starts_at','slot_minutes'=>'required_if:service_type,confession|nullable|integer|between:15,240']);
        if(in_array($d['service_type'],['christening','marriage'],true)&&!in_array((int)$d['weekday'],[0,6],true)) throw \Illuminate\Validation\ValidationException::withMessages(['weekday'=>'Christenings and marriages can only be set on Saturdays or Sundays.']);
        $d['slot_minutes']=$d['service_type']==='confession' ? (int)$d['slot_minutes'] : 60;
        $availability->update($d); return redirect()->route('dashboard.profile.edit')->with('success',__('ui.availability_updated'));
    }
    public function deleteAvailability(Request $request, Availability $availability) { $p=$this->priest($request); abort_unless($availability->priest_id===$p->id,403); $availability->delete(); return back()->with('success',__('ui.availability_removed')); }
    public function storeBlockedDate(Request $request) { $p=$this->priest($request); $d=$request->validate(['date'=>'required|date|after_or_equal:today','end_date'=>'nullable|date|after_or_equal:date','service_type'=>'required|in:all,christening,marriage,confession,liturgy','reason'=>'nullable|string|max:160']); $p->blockedDates()->create($d); return back()->with('success',__('ui.blocked_saved')); }
    public function updateBlockedDate(Request $request, BlockedDate $blockedDate) { $p=$this->priest($request); abort_unless($blockedDate->priest_id===$p->id,403); $d=$request->validate(['date'=>'required|date','end_date'=>'nullable|date|after_or_equal:date','service_type'=>'required|in:all,christening,marriage,confession,liturgy','reason'=>'nullable|string|max:160']); $blockedDate->update($d); return redirect()->route('dashboard.profile.edit')->with('success',__('ui.blocked_updated')); }
    public function deleteBlockedDate(Request $request, BlockedDate $blockedDate) { $p=$this->priest($request); abort_unless($blockedDate->priest_id===$p->id,403); $blockedDate->delete(); return back()->with('success',__('ui.blocked_removed')); }
}
