<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'avatar', 'role', 'client_id'];
    protected $hidden   = ['password', 'remember_token'];

    protected $appends = ['avatar_url'];

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar ? url('/file/' . $this->avatar) : null;
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function isAdmin(): bool      { return $this->role === 'admin'; }
    public function isMedewerker(): bool { return $this->role === 'medewerker'; }
    public function isKlant(): bool      { return $this->role === 'klant'; }
    public function isStaff(): bool      { return in_array($this->role, ['admin', 'medewerker']); }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
