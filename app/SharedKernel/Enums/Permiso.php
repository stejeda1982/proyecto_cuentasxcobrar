<?php

declare(strict_types=1);

namespace SharedKernel\Enums;

/**
 * Permisos del sistema, derivados de la matriz de la tabla 5 de la
 * Especificación UI y de la matriz de roles del SRS (sección 2.3).
 *
 * Un permiso es una habilidad de Gate/Policy: se registra en el
 * AuthServiceProvider del módulo Seguridad.
 */
enum Permiso: string
{
    // Cartera
    case VerCarteraPropia = 'cartera.ver.propia';
    case VerCarteraConsolidada = 'cartera.ver.consolidada';
    case ExportarCartera = 'cartera.exportar';

    // Gestión comercial
    case RegistrarAccion = 'gestion.registrar';
    case AbrirConciliacion = 'conciliacion.abrir';
    case AprobarConciliacion = 'conciliacion.aprobar';
    case RegistrarPromesa = 'promesa.registrar';

    // Asignación
    case AsignarClientes = 'asignacion.asignar';
    case EscalarNivel = 'cartera.escalar';

    // Integración ERP
    case EjecutarCarga = 'integracion.carga.ejecutar';
    case VerColaErrores = 'integracion.errores.ver';

    // Administración
    case AdministrarUsuarios = 'admin.usuarios';
    case AdministrarParametros = 'admin.parametros';

    // Reportes
    case VerReportes = 'reportes.ver';
    case ExportarReportes = 'reportes.exportar';

    /**
     * Permisos por rol, conforme a la tabla 5 de la Especificación UI.
     *
     * @return array<self>
     */
    public static function porRol(Rol $rol): array
    {
        return match ($rol) {
            Rol::Especialista => [
                self::VerCarteraPropia,
                self::RegistrarAccion,
                self::AbrirConciliacion,
                self::RegistrarPromesa,
                self::ExportarCartera,
                self::VerReportes,
            ],
            Rol::Supervisor => [
                self::VerCarteraPropia,
                self::VerCarteraConsolidada,
                self::RegistrarAccion,
                self::AbrirConciliacion,
                self::AprobarConciliacion,
                self::RegistrarPromesa,
                self::AsignarClientes,
                self::EscalarNivel,
                self::ExportarCartera,
                self::VerReportes,
                self::ExportarReportes,
            ],
            Rol::AdmCartera => [
                self::VerCarteraPropia,
                self::VerCarteraConsolidada,
                self::RegistrarAccion,
                self::AbrirConciliacion,
                self::RegistrarPromesa,
                self::AsignarClientes,
                self::EscalarNivel,
                self::ExportarCartera,
                self::VerReportes,
                self::ExportarReportes,
            ],
            Rol::AnalistaConciliacion => [
                self::VerCarteraPropia,
                self::RegistrarAccion,
                self::AbrirConciliacion,
                self::RegistrarPromesa,
                self::ExportarCartera,
                self::VerReportes,
            ],
            Rol::AdmTecnico => [
                self::EjecutarCarga,
                self::VerColaErrores,
                self::AdministrarUsuarios,
                self::AdministrarParametros,
            ],
            Rol::Auditor => [
                self::VerCarteraPropia,
                self::VerCarteraConsolidada,
                self::VerColaErrores,
                self::VerReportes,
                self::ExportarReportes,
            ],
        };
    }
}
