<?php
namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;

class PriestCalendarController extends Controller
{
    public function index(Request $request)
    {
        $priest=$request->user();abort_unless($priest->isPriest(),403,'Priest accounts only.');
        $monthValue=$request->query('month',now()->format('Y-m'));
        abort_unless(preg_match('/^\d{4}-(0[1-9]|1[0-2])$/',$monthValue)===1,404);
        $first=Carbon::createFromFormat('!Y-m',$monthValue);$last=$first->copy()->endOfMonth();
        $events=$priest->appointments()->with('requester')->whereIn('status',['pending','confirmed'])->whereBetween('starts_at',[$first->copy()->startOfDay(),$last->copy()->endOfDay()])->orderBy('starts_at')->get()->groupBy(fn($a)=>$a->starts_at->toDateString());
        $blockRecords=$priest->blockedDates()->overlappingPeriod($first->toDateString(),$last->toDateString())->get();$blocks=[];
        foreach($blockRecords as $block){$from=Carbon::parse(max($block->date->toDateString(),$first->toDateString()));$endDate=$block->end_date??$block->date;$to=Carbon::parse(min($endDate->toDateString(),$last->toDateString()));for($day=$from->copy();$day->lte($to);$day->addDay())$blocks[$day->toDateString()][]=$block;}
        $cells=array_fill(0,$first->dayOfWeek,null);for($day=1;$day<=$first->daysInMonth;$day++)$cells[]=Carbon::create($first->year,$first->month,$day);
        while(count($cells)%7!==0)$cells[]=null;
        return view('dashboard.calendar',['month'=>$first,'previous'=>$first->copy()->subMonth()->format('Y-m'),'next'=>$first->copy()->addMonth()->format('Y-m'),'weeks'=>array_chunk($cells,7),'events'=>$events,'blocks'=>$blocks]);
    }
}
