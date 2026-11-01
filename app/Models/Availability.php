<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Availability extends Model {
    protected $fillable = ['service_type','weekday','starts_at','ends_at','slot_minutes'];
    public function priest() { return $this->belongsTo(User::class, 'priest_id'); }
}
