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
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'username', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

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
        return $this->isSuperAdmin() || $this->isAdmin() || $this->isCoordinador();
    }

    public function docente(): HasOne
    {
        return $this->hasOne(Docente::class);
    }

    public function codigoVerificacion(): HasOne
    {
        return $this->hasOne(CodigoVerificacion::class);
    }

    public function carrerasCoordinadas(): BelongsToMany
    {
        return $this->belongsToMany(Carrera::class, 'coordinador_carrera');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function gruposUsuarios(): BelongsToMany
    {
        return $this->belongsToMany(GrupoUsuario::class, 'grupo_usuario_user');
    }

    /**
     * El admin (superadmin) siempre tiene todos los permisos. Para el resto,
     * se revisa si alguno de sus roles personalizados incluye la clave dada.
     */
    public function tienePermiso(string $clave): bool
    {
        if ($this->isSuperAdmin() || $this->isAdmin()) {
            return true;
        }

        return $this->roles->contains(
            fn (Role $role) => $role->permissions->contains('clave', $clave)
        );
    }

    /**
     * El superadministrador está por encima del admin: control total del
     * sistema y, a diferencia de éste, invisible para todos los demás roles
     * (incluido el propio admin). Ver User::scopeVisiblesParaGestion().
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
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
     * Regla de edición de la pantalla Admin/Usuarios: nadie edita a un
     * superadmin salvo él mismo; al admin sólo lo edita él mismo o un
     * superadmin; cualquier otro usuario lo puede editar quien llegue a esta
     * pantalla (admin o superadmin, ya filtrado por el middleware de rutas).
     */
    public function puedeSerEditadoPor(User $editor): bool
    {
        if ($this->isSuperAdmin()) {
            return $editor->is($this);
        }

        if ($this->isAdmin()) {
            return $editor->is($this) || $editor->isSuperAdmin();
        }

        return true;
    }

    /**
     * IDs de carreras a las que este usuario tiene acceso administrativo.
     * Devuelve null para el admin (acceso a todas, sin filtrar por IDs).
     */
    public function carreraIdsAccesibles(): ?array
    {
        if ($this->isSuperAdmin() || $this->isAdmin()) {
            return null;
        }

        return $this->carrerasCoordinadas()->pluck('carreras.id')->all();
    }

    /**
     * Excluye a los superadministradores de cualquier listado de usuarios
     * usado por pantallas de gestión (usuarios, grupos de usuarios,
     * auditoría), para que ningún otro rol —incluido el admin— pueda verlos.
     */
    public function scopeVisiblesParaGestion(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('role', '!=', UserRole::SuperAdmin);
    }
}
