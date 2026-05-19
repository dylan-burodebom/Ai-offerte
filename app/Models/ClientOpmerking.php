<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientOpmerking extends Model
{
    protected $table = 'client_opmerkingen';

    protected $fillable = ['client_id', 'tekst'];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
