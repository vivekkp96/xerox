<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Model
{
     use HasFactory;
    protected $table = 'users';

    protected $fillable = [
        'fullname',
        'email',
        'password',
        'phonenumber',
        'status',
    ];
       protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];
}