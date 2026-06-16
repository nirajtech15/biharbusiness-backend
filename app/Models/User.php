<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'mobile', 'email', 'password', 'role', 'city',
        'plan', 'status', 'otp', 'otp_expiry', 'avatar'
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'password' => 'hashed',
        'otp_expiry' => 'datetime',
        'role' => 'string',
        'plan' => 'string',
        'status' => 'string',
    ];

    public function businesses() { return $this->hasMany(Business::class, 'owner_id'); }
    public function postedJobs() { return $this->hasMany(Job::class, 'posted_by'); }
}
