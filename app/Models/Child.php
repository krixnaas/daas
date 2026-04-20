<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Child extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'date_of_birth',
        'due_date',
        'gender',
        'status',
        'weight',
        'height',
        'head_circumference',
        'blood_group',
        'umbilical_cord_fell_off_at',
        'expected_weight',
        'birth_type',
        'labor_started_at',
    ];

    protected $casts = [
        'date_of_birth'             => 'date',
        'due_date'                  => 'date',
        'umbilical_cord_fell_off_at' => 'datetime',
        'labor_started_at'          => 'datetime',
    ];

    /**
     * Relationship back to the profile.
     */
    public function dadProfile()
    {
        return $this->belongsTo(DadProfile::class);
    }
}