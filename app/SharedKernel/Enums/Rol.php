<?php

declare(strict_types=1);

namespace SharedKernel\Enums;

/**
 * Roles del sistema conforme a la matriz del SRS (sección 2.3).
 *
 * Los valores coinciden con el slug usado por Gates, Policies y middleware
 * («rol:supervisor»), y con la columna users.rol de la base de datos.
 */
enum Rol: string
{
    case Especialista = 'especialista';
    case Supervisor = 'supervisor';
    case AdmCartera = 'adm_cartera';
    case AnalistaConciliacion = 'analista_conciliacion';
    case AdmTecnico = 'adm_tecnico';
    case Auditor = 'auditor';

    /**
     * Etiqueta legible para UI, bitácoras y auditoría.
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Especialista => 'Especialista comercial',
            self::Supervisor => 'Supervisor de cobranza',
            self::AdmCartera => 'Administrador de cartera',
            self::AnalistaConciliacion => 'Analista de conciliación',
            self::AdmTecnico => 'Administrador técnico',
            self::Auditor => 'Auditor / consulta',
        };
    }

    /**
     * Indica si el rol es de solo lectura (auditor): sin acciones de negocio.
     */
    public function soloLectura(): bool
    {
        return $this === self::Auditor;
    }

    /**
     * Indica si el rol puede ver la cartera de todo el equipo (consolidada).
     */
    public function veCarteraConsolidada(): bool
    {
        return in_array($this, [self::Supervisor, self::AdmCartera, self::Auditor], true);
    }
}
