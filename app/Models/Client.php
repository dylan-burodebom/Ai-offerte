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
    const RELATIE_STATUSSEN = ['prospect', 'klant', 'inactief'];

    protected $fillable = [
        'naam',
        'contactpersoon',
        'email',
        'telefoon',
        'sector',
        'relatie_status',
        'adres',
        'postcode',
        'stad',
        'beschrijving',
        'logo',
    ];

    protected $appends = ['logo_url'];

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? '/storage/' . $this->logo : null;
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function contactpersonen(): HasMany
    {
        return $this->hasMany(Contactpersoon::class);
    }

    public function opmerkingen(): HasMany
    {
        return $this->hasMany(ClientOpmerking::class)->orderBy('created_at');
    }
}
