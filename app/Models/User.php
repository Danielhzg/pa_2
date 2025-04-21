<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasFactory;

    protected $fillable = [
        'username',  // Make sure this matches the database field
        'full_name', // Add full_name to fillable
        'email',
        'phone', // Add phone to fillable
        'address', // Add address to fillable
        'birth_date', // Add birth_date to fillable
        'password',
        'email_verified_at', // Add email_verified_at to fillable
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'birth_date' => 'date', // Cast birth_date as date
    ];

    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_id');
    }
}