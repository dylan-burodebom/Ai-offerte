<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteSection extends Model
{
    /** @use HasFactory<\Database\Factories\QuoteSectionFactory> */
    use HasFactory;

    protected $fillable = [
        'quote_id',
        'titel',
        'content',
        'volgorde',
    ];

    protected $casts = [
        'content' => 'array',
    ];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }
}
