<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Appointment extends Model {
    protected $fillable = ['requester_id','service_type','starts_at','duration_minutes','status','message','suggested_at','priest_response','change_request_type','change_requested_by','change_request_status','change_requested_at','change_requested_at_target','change_request_message','change_response','reminder_sent_at','archived_at'];
    protected $casts = ['starts_at' => 'datetime','suggested_at' => 'datetime','change_requested_at'=>'datetime','change_requested_at_target'=>'datetime','reminder_sent_at'=>'datetime','archived_at'=>'datetime'];
    public function priest() { return $this->belongsTo(User::class, 'priest_id'); }
    public function requester() { return $this->belongsTo(User::class, 'requester_id'); }
    public function messages() { return $this->hasMany(AppointmentMessage::class)->with('sender')->orderBy('created_at'); }
}
