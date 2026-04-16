<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'dad_profile_id',
        'subject_id',
        'subject_type',
        'category',
        'data',
        'logged_at'
    ];

    protected $casts = [
        'data' => 'array',
        'logged_at' => 'datetime'
    ];

    public function subject()
    {
        return $this->morphTo();
    }
}