<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pregnancy extends Model
{
    protected $fillable = [
        'partner_name',
        'due_date',
        'type',
        'baby_name',
    ];

    public function dadProfile()
    {
        return $this->belongsTo(DadProfile::class);
    }
}
