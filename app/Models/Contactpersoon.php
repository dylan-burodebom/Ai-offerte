<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contactpersoon extends Model
{
    use SoftDeletes;

    protected $table = 'contactpersonen';

    protected $fillable = ['client_id', 'naam', 'email', 'telefoon', 'geboortedatum'];

    protected $casts = ['geboortedatum' => 'date'];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
