<?php

namespace App\Models\Application;

use App\Models\VerificationOtp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class AppUsers extends Model
{
    use HasFactory, Notifiable, HasApiTokens;
    protected $fillable = ['name', 'phone', 'password', 'email_verified_at', 'phone_verified_at', 'status'];
    protected $hidden = ['password'];

    public function verificationOtp()
    {
        return $this->hasMany(VerificationOtp::class);
    }
}
