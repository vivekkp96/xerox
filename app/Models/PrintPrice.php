<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrintPrice extends Model
{
    protected $fillable = [
        'paper_size_id',
        'print_mode_id',
        'price',
    ];

    protected $casts = [
        'price' => 'float',
    ];

    public function paperSize(): BelongsTo
    {
        return $this->belongsTo(PaperSize::class);
    }

    public function printMode(): BelongsTo
    {
        return $this->belongsTo(PrintMode::class);
    }
}