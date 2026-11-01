<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'slug',
        'parish',
        'city',
        'age',
        'is_married',
        'is_confessor',
        'is_priest_verified',
        'is_confessor_verified',
        'approval_status',
        'bio',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_confessor' => 'boolean',
        'is_priest_verified' => 'boolean',
        'is_confessor_verified' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $user) {
            if ($user->isVisitor()) $user->approval_status = 'approved';
            elseif ($user->isPriest() && blank($user->approval_status)) $user->approval_status = 'pending';
        });
    }

    public function availabilities() { return $this->hasMany(Availability::class, 'priest_id'); }
    public function blockedDates() { return $this->hasMany(BlockedDate::class, 'priest_id'); }
    public function appointments() { return $this->hasMany(Appointment::class, 'priest_id'); }
    public function appointmentRequests() { return $this->hasMany(Appointment::class, 'requester_id'); }
    public function isPriest(): bool { return $this->role === 'priest'; }
    public function isAdmin(): bool { return $this->role === 'admin'; }
    public function isVisitor(): bool { return $this->role === 'visitor'; }
}
