<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreUser extends Model
{
    use HasFactory;

    protected $table = 'pre_users';

    protected $fillable = [
        'fullname',
        'email',
        'password',
        'phonenumber',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];
}