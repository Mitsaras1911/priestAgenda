<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
class BlockedDate extends Model {
    protected $fillable = ['date','end_date','service_type','reason'];
    protected $casts = ['date' => 'date','end_date' => 'date'];
    public function scopeContainingDate(Builder $query,$date): Builder
    {
        return $query->whereDate('date','<=',$date)->where(function(Builder $range)use($date){$range->whereDate('end_date','>=',$date)->orWhere(function(Builder $single)use($date){$single->whereNull('end_date')->whereDate('date',$date);});});
    }
    public function scopeOverlappingPeriod(Builder $query,$from,$to): Builder
    {
        return $query->whereDate('date','<=',$to)->where(function(Builder $range)use($from){$range->whereDate('end_date','>=',$from)->orWhere(function(Builder $single)use($from){$single->whereNull('end_date')->whereDate('date','>=',$from);});});
    }
    public function priest() { return $this->belongsTo(User::class, 'priest_id'); }
}
