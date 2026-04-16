<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DadProfile extends Model
{
  /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'track_mom',
        'tab_config',
        'partner_name',
    ];

    /**
     * Cast attributes to correct types.
     */
    protected $casts = [
        'track_mom' => 'boolean',
        'tab_config' => 'array', // Important for storing our tab lists
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function children() { return $this->hasMany(Child::class); }
    public function pregnancies() { return $this->hasMany(Pregnancy::class); }

    public function getMomNameAttribute()
{
    // 1. Try to get it from the profile (your main source)
    if ($this->partner_name) return $this->partner_name;

    // 2. Fallback: Try to get it from the most recent pregnancy if it's missing on the profile
    return $this->pregnancies()->latest()->first()?->partner_name ?? 'Partner';
}
}
