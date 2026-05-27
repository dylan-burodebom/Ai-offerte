<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quote extends Model
{
    /** @use HasFactory<\Database\Factories\QuoteFactory> */
    use HasFactory;

    const STATUSSEN = ['concept', 'verzonden', 'gewonnen', 'verloren'];

    // Allowed transitions: from → [to, ...]
    const GELDIGE_OVERGANGEN = [
        'concept'   => ['verzonden'],
        'verzonden' => ['gewonnen', 'verloren', 'concept'],
        'gewonnen'  => [],
        'verloren'  => [],
    ];

    protected $fillable = [
        'client_id',
        'user_id',
        'offerte_nummer',
        'titel',
        'projectbeschrijving',
        'diensten',
        'status',
        'geldig_tot',
        'totaal',
        'btw_tarief',
        'versie',
        'tokens_gebruikt',
        'verzonden_op',
        'fireflies_samenvatting',
        'prompt_versie',
        'pdf_blok_ruimte',
        'toon_btw',
        'inv_volgorde',
    ];

    protected $casts = [
        'geldig_tot'   => 'date',
        'verzonden_op' => 'datetime',
        'totaal'       => 'decimal:2',
        'diensten'     => 'array',
        'toon_btw'     => 'boolean',
    ];

    public function kanNaarStatus(string $nieuweStatus): bool
    {
        return in_array($nieuweStatus, self::GELDIGE_OVERGANGEN[$this->status] ?? []);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(QuoteSection::class)->orderBy('volgorde');
    }

    public function investments(): HasMany
    {
        return $this->hasMany(QuoteInvestment::class)->orderBy('volgorde');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(QuoteStatusHistory::class)->orderByDesc('datum');
    }

    public function scopeConcept(Builder $query): Builder
    {
        return $query->where('status', 'concept');
    }

    public function scopeVerzonden(Builder $query): Builder
    {
        return $query->where('status', 'verzonden');
    }

    public function scopeGewonnen(Builder $query): Builder
    {
        return $query->where('status', 'gewonnen');
    }

    public function scopeVerloren(Builder $query): Builder
    {
        return $query->where('status', 'verloren');
    }
}
