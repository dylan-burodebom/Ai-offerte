<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteInvestment extends Model
{
    /** @use HasFactory<\Database\Factories\QuoteInvestmentFactory> */
    use HasFactory;

    protected $fillable = [
        'quote_id',
        'omschrijving',
        'bedrag',
        'volgorde',
    ];

    protected $casts = [
        'bedrag' => 'decimal:2',
    ];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }
}
