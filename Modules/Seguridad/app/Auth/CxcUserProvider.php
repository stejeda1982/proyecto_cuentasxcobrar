<?php

declare(strict_types=1);

namespace Modules\Seguridad\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;
use Modules\Seguridad\Contracts\LdapAuthenticator;
use Modules\Seguridad\Exceptions\LdapNoDisponibleException;
use Modules\Seguridad\Models\Usuario;

/**
 * Guardia unificada de usuarios (T0.3 — RNF-SEG-01).
 *
 * Un solo provider para los dos orígenes de identidad:
 *
 *  - LDAP: si existe un usuario local de origen 'ldap', la contraseña se
 *    valida contra el directorio corporativo (puerto LdapAuthenticator).
 *    El alta del usuario la hace ldap:import/sincronía; si no existe
 *    todavía, se crea con el rol por defecto configurado (JIT).
 *
 *  - Local: las cuentas técnicas verifican el hash bcrypt local.
 *
 * Tras autenticar, actualiza ultimo_acceso y registra el evento de
 * auditoría (login) de forma diferida vía evento de Laravel.
 */
class CxcUserProvider extends EloquentUserProvider
{
    public function __construct(
        $hasher,
        $model,
        protected LdapAuthenticator $ldap,
    ) {
        parent::__construct($hasher, $model);
    }

    /**
     * Recupera el usuario por credenciales: el identificador es la
     * columna usuario (uid LDAP o nombre de cuenta local).
     */
    public function retrieveByCredentials(array $credentials): ?Usuario
    {
        $usuario = $credentials['usuario'] ?? $credentials['email'] ?? null;

        if (! is_string($usuario) || $usuario === '') {
            return null;
        }

        return Usuario::query()
            ->with('rol')
            ->where('usuario', strtolower(trim($usuario)))
            ->first();
    }

    /**
     * Validación de credenciales según el origen del usuario.
     */
    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        /** @var Usuario $user */
        if (! $user->activo) {
            return false;
        }

        $password = (string) ($credentials['password'] ?? '');

        if ($user->esLocal()) {
            $valido = Hash::check($password, $user->getAuthPassword());

            if ($valido) {
                $this->postLogin($user);
            }

            return $valido;
        }

        // Origen LDAP: delegar en el directorio corporativo.
        try {
            $atributos = $this->ldap->autenticar($user->usuario, $password);

            if ($atributos !== null) {
                $this->postLogin($user);

                return true;
            }

            return false;
        } catch (LdapNoDisponibleException) {
            // El directorio caído no debe validar credenciales locales.
            return false;
        }
    }

    /**
     * Marca de último acceso (la auditoría del login la registra el
     * listener del evento Illuminate\Auth\Events\Login).
     */
    private function postLogin(Usuario $user): void
    {
        $user->forceFill(['ultimo_acceso' => now()])->saveQuietly();
    }
}
