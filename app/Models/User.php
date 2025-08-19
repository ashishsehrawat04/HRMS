<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'employee_code',
        'email',
        'status',
        'department',
        'mobile',
        'reporting_manager',
        'blood_group',
        'location',
        'biometrics',
        'login_token',
        'designation',
        'notice_period_end_date',
        'dob',
        'doj',
        'password',
        'image',
        'type',
        'experience',
        'prohibition_end_date',
        'total_experience',
        'priority_levels',
        'intern_end_date',
        'intern_start_date',
        'fcm_token'
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
    ];
    
    public function isAdmin()
    {
        return $this->type === 'admin'; // Adjust according to your role logic
    }
    
    public function isEmployee()
    {
        return $this->type === 'employee'; // Adjust according to your role logic
    }

}
