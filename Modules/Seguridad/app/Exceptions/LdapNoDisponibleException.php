<?php

declare(strict_types=1);

namespace Modules\Seguridad\Exceptions;

use RuntimeException;

/**
 * El directorio LDAP corporativo no responde o no está configurado.
 * El login muestra el mensaje definido en la especificación LOG-01:
 * «El directorio corporativo no responde. Intente más tarde o use un
 * usuario del sistema.»
 */
class LdapNoDisponibleException extends RuntimeException {}
