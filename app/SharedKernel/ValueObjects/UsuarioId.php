<?php

declare(strict_types=1);

namespace SharedKernel\ValueObjects;

use InvalidArgumentException;
use Stringable;

/**
 * Identificador de un usuario del sistema (LDAP o propio).
 *
 * Value object inmutable del SharedKernel: normaliza el identificador
 * (minúsculas, sin espacios) y valida su formato.
 */
final readonly class UsuarioId implements Stringable
{
    private const MIN = 3;

    private const MAX = 60;

    private function __construct(public string $valor) {}

    public static function desde(string $valorCrudo): self
    {
        $valor = strtolower(trim($valorCrudo));

        if ($valor === '' || strlen($valor) < self::MIN || strlen($valor) > self::MAX) {
            throw new InvalidArgumentException(
                sprintf('El identificador de usuario debe tener entre %d y %d caracteres.', self::MIN, self::MAX)
            );
        }

        if (! preg_match('/^[a-z0-9._-]+$/', $valor)) {
            throw new InvalidArgumentException(
                'El identificador de usuario solo admite letras, números, punto, guion y guion bajo.'
            );
        }

        return new self($valor);
    }

    public function __toString(): string
    {
        return $this->valor;
    }
}
