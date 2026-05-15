<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteStatusHistory extends Model
{
    protected $table = 'quote_status_history';

    protected $fillable = [
        'quote_id',
        'user_id',
        'oude_status',
        'nieuwe_status',
        'reden',
        'datum',
    ];

    protected $casts = [
        'datum' => 'datetime',
    ];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
