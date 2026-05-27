<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class Client extends Model
{
    /** @use HasFactory<\Database\Factories\ClientFactory> */
    use HasFactory, SoftDeletes;

    const SECTOREN = ['Bouw', 'Industrie', 'Transport', 'Installatie', 'Overig'];
    const RELATIE_STATUSSEN = ['prospect', 'klant', 'inactief'];

    const RECHTSVORMEN = ['BV', 'NV', 'VOF', 'Eenmanszaak', 'Stichting', 'Maatschap', 'CV', 'Overig'];
    const TALEN = ['nl' => 'Nederlands', 'en' => 'Engels', 'de' => 'Duits', 'fr' => 'Frans'];

    protected $fillable = [
        'naam',
        'contactpersoon',
        'email',
        'telefoon',
        'website',
        'sector',
        'relatie_status',
        'adres',
        'postcode',
        'stad',
        'beschrijving',
        'logo',
        // Bank
        'bank',
        'bic',
        'iban',
        'rekeninghouder',
        'vestigingsplaats',
        // Administratie
        'gebruik_afwijkende_factuurgegevens',
        // Extra
        'kvk_nummer',
        'rechtsvorm',
        'btw_nummer',
        'extern_id',
        // Instellingen
        'relatiebeheerder_id',
        'voertaal',
        'taal_berichten',
        'labels',
    ];

    protected $casts = [
        'labels' => 'array',
        'gebruik_afwijkende_factuurgegevens' => 'boolean',
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

    public function relatiebeheerder(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'relatiebeheerder_id');
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
