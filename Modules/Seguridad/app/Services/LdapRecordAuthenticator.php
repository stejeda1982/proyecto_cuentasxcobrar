<?php

declare(strict_types=1);

namespace Modules\Seguridad\Services;

use LdapRecord\Container;
use LdapRecord\Models\Entry;
use Modules\Seguridad\Contracts\LdapAuthenticator;
use Modules\Seguridad\Exceptions\LdapNoDisponibleException;
use Throwable;

/**
 * Adaptador LDAP basado en LdapRecord (T0.3, ARQ §7.1).
 *
 * Requiere ext-ldap: se registra solo cuando la extensión existe y la
 * conexión está configurada (LDAP_HOST). La conexión se define en
 * config/ldap.php con credenciales de bind corporativas provistas por
 * variables de entorno, nunca en el código (RNF-SEG-05).
 */
class LdapRecordAuthenticator implements LdapAuthenticator
{
    public function autenticar(string $usuario, string $password): ?array
    {
        try {
            $conexion = Container::getDefaultConnection();
            $conexion->connect();

            $entrada = Entry::query()
                ->where(config('ldap.identificador', 'uid'), '=', $usuario)
                ->first();

            if ($entrada === null) {
                return null;
            }

            if (! $conexion->auth()->attempt($entrada->getDn(), $password)) {
                return null;
            }

            return [
                'uid' => $usuario,
                'nombre' => (string) ($entrada->getFirstAttribute('cn') ?? $usuario),
                'correo' => $entrada->getFirstAttribute('mail'),
            ];
        } catch (LdapNoDisponibleException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new LdapNoDisponibleException(
                'El directorio corporativo no responde.', 0, $e
            );
        }
    }

    public function disponible(): bool
    {
        return extension_loaded('ldap')
            && (string) config('ldap.logging', '') !== '';
    }
}
