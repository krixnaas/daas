<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'email_otp', 'email_otp_expires_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function dadProfile() {
        return $this->hasOne(DadProfile::class);
    }

    public function generateOtp()
    {
        $otp = sprintf("%06d", mt_rand(1, 999999));
        $this->update([
            'email_otp' => $otp,
            'email_otp_expires_at' => now()->addMinutes(15),
        ]);
        return $otp;
    }

    public function sendEmailVerificationNotification()
    {
        $otp = $this->generateOtp();
        $this->notify(new \App\Notifications\TacticalOtpNotification($otp));
    }
}
