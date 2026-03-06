<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'documents',
        'payments',
        'total_price',
        'additional_charge',
        'remark',
        'status',
        'payment_status',
        'comment'
    ];

    protected $casts = [
        'documents' => 'array',
        'payments' => 'array',
        'total_price' => 'decimal:2',
        'additional_charge' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}