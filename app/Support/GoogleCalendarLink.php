<?php
namespace App\Support;
use App\Models\Appointment;
class GoogleCalendarLink
{
    public static function forAppointment(Appointment $appointment): string
    {
        $start=$appointment->starts_at->copy()->utc();
        $end=$start->copy()->addMinutes($appointment->duration_minutes);
        $priest=$appointment->priest;
        $location=implode(', ',array_filter([$priest->parish,$priest->city]));
        $details=__('ui.calendar_event_description',['priest'=>$priest->name,'service'=>__('ui.'.$appointment->service_type)]);
        return 'https://calendar.google.com/calendar/render?'.http_build_query([
            'action'=>'TEMPLATE','text'=>__('ui.calendar_event_title',['service'=>__('ui.'.$appointment->service_type),'priest'=>$priest->name]),
            'dates'=>$start->format('Ymd\\THis\\Z').'/'.$end->format('Ymd\\THis\\Z'),
            'details'=>$details,'location'=>$location,'ctz'=>'Asia/Nicosia',
        ],'', '&', PHP_QUERY_RFC3986);
    }
}
