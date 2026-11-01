<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PriestVerificationLog extends Model
{
    protected $fillable=['admin_id','priest_id','type','decision','reason'];
    public function admin(){return $this->belongsTo(User::class,'admin_id');}
    public function priest(){return $this->belongsTo(User::class,'priest_id');}
}
