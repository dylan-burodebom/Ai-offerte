<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    /** @use HasFactory<\Database\Factories\ClientFactory> */
    use HasFactory, SoftDeletes;

    const SECTOREN = ['Bouw', 'Industrie', 'Transport', 'Installatie', 'Overig'];

    protected $fillable = [
        'naam',
        'contactpersoon',
        'email',
        'telefoon',
        'sector',
        'adres',
        'postcode',
        'stad',
        'beschrijving',
    ];

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }
}
