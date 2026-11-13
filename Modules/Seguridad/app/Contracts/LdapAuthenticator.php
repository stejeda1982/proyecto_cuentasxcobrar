<?php

declare(strict_types=1);

namespace Modules\Seguridad\Contracts;

use Modules\Seguridad\Exceptions\LdapNoDisponibleException;

/**
 * Puerto de autenticación LDAP (T0.3).
 *
 * Aísla el acceso al directorio corporativo: la implementación real usa
 * LdapRecord (ext-ldap) en staging/producción; en desarrollo local, donde
 * no hay directorio ni extensión, la implementación NullLdapAuthenticator
 * responde «no disponible» y el guardia unificado autentica solo usuarios
 * locales, tal como prevé el riesgo T0.3 del PLN (empezar con usuarios
 * propios y habilitar LDAP cuando el directorio esté accesible).
 */
interface LdapAuthenticator
{
    /**
     * Intenta autenticar el par usuario/contraseña contra el directorio.
     *
     * @return array{uid: string, nombre: string, correo: ?string}|null
     *                                                                  Atributos del usuario LDAP si las credenciales son válidas;
     *                                                                  null si no lo son.
     *
     * @throws LdapNoDisponibleException cuando el directorio no responde.
     */
    public function autenticar(string $usuario, string $password): ?array;

    /**
     * Indica si el directorio está configurado y disponible.
     */
    public function disponible(): bool;
}
