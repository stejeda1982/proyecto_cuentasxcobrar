<?php

declare(strict_types=1);

namespace Modules\Seguridad\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use SharedKernel\Enums\Permiso;
use SharedKernel\Enums\Rol as RolEnum;

/**
 * Usuario del sistema con guardia unificada (T0.3).
 *
 * Soporta dos orígenes: 'ldap' (credenciales corporativas, sin password
 * local) y 'local' (cuentas técnicas con hash). Implementa Authenticatable
 * para que el guard 'web' de Laravel lo autentique con sesión.
 */
/**
 * @property int $id
 * @property string $usuario
 * @property string $nombre
 * @property string|null $correo
 * @property string|null $password_hash
 * @property string $origen
 * @property int $rol_id
 * @property bool $activo
 * @property CarbonInterface|null $ultimo_acceso
 * @property CarbonInterface|null $desactivado_en
 * @property Rol $rol
 */
class Usuario extends Authenticatable
{
    use Notifiable;

    protected $table = 'seguridad_usuarios';

    protected $fillable = [
        'usuario', 'nombre', 'correo', 'password_hash', 'origen',
        'rol_id', 'activo', 'ultimo_acceso', 'desactivado_en',
    ];

    protected $hidden = ['password_hash', 'remember_token'];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'ultimo_acceso' => 'datetime',
            'desactivado_en' => 'datetime',
        ];
    }

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    public function auditorias(): HasMany
    {
        return $this->hasMany(Auditoria::class, 'usuario_id');
    }

    /**
     * Interface Authenticatable: getAuthPassword devuelve el hash local.
     * Para usuarios LDAP no se usa (la verificación es del directorio).
     */
    public function getAuthPassword(): string
    {
        return (string) $this->password_hash;
    }

    public function esLocal(): bool
    {
        return $this->origen === 'local';
    }

    public function esLdap(): bool
    {
        return $this->origen === 'ldap';
    }

    /**
     * Slug del rol (p. ej. 'supervisor'), para middleware «rol:».
     */
    public function rolSlug(): string
    {
        return (string) ($this->rol->slug ?? '');
    }

    public function rolEnum(): ?RolEnum
    {
        return RolEnum::tryFrom($this->rolSlug());
    }

    /**
     * Verifica un permiso contra la matriz SharedKernel (tabla 5 UI).
     */
    public function tienePermiso(Permiso $permiso): bool
    {
        $rol = $this->rolEnum();

        return $rol !== null && in_array($permiso, Permiso::porRol($rol), true);
    }
}
