<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isPriest(),403);
        $month=$request->query('month',now()->format('Y-m'));abort_unless(preg_match('/^\d{4}-(0[1-9]|1[0-2])$/',$month)===1,422);
        $first=Carbon::createFromFormat('!Y-m',$month);$last=$first->copy()->endOfMonth();
        $events=$request->user()->appointments()->with('requester')->whereIn('status',['pending','confirmed'])->whereBetween('starts_at',[$first->copy()->startOfDay(),$last->copy()->endOfDay()])->orderBy('starts_at')->get()->map(fn($a)=>['id'=>$a->id,'service_type'=>$a->service_type,'starts_at'=>$a->starts_at->toIso8601String(),'duration_minutes'=>$a->duration_minutes,'status'=>$a->status,'requester'=>['id'=>$a->requester->id,'name'=>$a->requester->name],'message'=>$a->message]);
        $blocked=$request->user()->blockedDates()->overlappingPeriod($first->toDateString(),$last->toDateString())->get(['id','date','end_date','service_type','reason']);
        return response()->json(['month'=>$first->format('Y-m'),'events'=>$events,'blocked_periods'=>$blocked]);
    }
}
