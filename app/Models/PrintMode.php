<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrintMode extends Model
{
    protected $fillable = [
        'name',
        'value',
        'status',
    ];

    protected $casts = [
        //
    ];
}
