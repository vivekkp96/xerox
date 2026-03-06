<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;


class PrintOrientation extends Model
{
    protected $fillable = ['name', 'svg'];

    public const PORTRAIT = 'portrait';
    public const LANDSCAPE = 'landscape';

    public static function getOrientations(): Collection
    {
        return self::all(['id', 'name', 'svg']);
    }
}
