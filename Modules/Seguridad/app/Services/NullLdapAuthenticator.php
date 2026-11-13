<?php

declare(strict_types=1);

namespace Modules\Seguridad\Services;

use Modules\Seguridad\Contracts\LdapAuthenticator;
use Modules\Seguridad\Exceptions\LdapNoDisponibleException;

/**
 * Implementación nula del puerto LDAP para entornos sin directorio
 * (desarrollo local sin ext-ldap). autenticar() siempre lanza
 * LdapNoDisponibleException, de modo que el login ofrezca el mensaje
 * previsto en LOG-01: «El directorio corporativo no responde. Intente
 * más tarde o use un usuario del sistema.»
 */
class NullLdapAuthenticator implements LdapAuthenticator
{
    public function autenticar(string $usuario, string $password): ?array
    {
        throw new LdapNoDisponibleException(
            'LDAP no está configurado en este entorno.'
        );
    }

    public function disponible(): bool
    {
        return false;
    }
}
