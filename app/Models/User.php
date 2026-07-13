<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'username', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'two_factor_secret' => 'encrypted',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function hasTwoFactorEnabled(): bool
    {
        return ! is_null($this->two_factor_secret) && ! is_null($this->two_factor_confirmed_at);
    }

    /**
     * Roles a los que se les ofrece activar 2FA (uso administrativo con más
     * privilegios; el docente no lo necesita).
     */
    public function puedeUsarDosFactores(): bool
    {
        return $this->isAdmin() || $this->isCoordinador();
    }

    public function docente(): HasOne
    {
        return $this->hasOne(Docente::class);
    }

    public function carrerasCoordinadas(): BelongsToMany
    {
        return $this->belongsToMany(Carrera::class, 'coordinador_carrera');
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isCoordinador(): bool
    {
        return $this->role === UserRole::Coordinador;
    }

    public function isDocente(): bool
    {
        return $this->role === UserRole::Docente;
    }

    /**
     * IDs de carreras a las que este usuario tiene acceso administrativo.
     * Devuelve null para el admin (acceso a todas, sin filtrar por IDs).
     */
    public function carreraIdsAccesibles(): ?array
    {
        if ($this->isAdmin()) {
            return null;
        }

        return $this->carrerasCoordinadas()->pluck('carreras.id')->all();
    }
}
