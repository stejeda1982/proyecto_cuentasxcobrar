<?php

declare(strict_types=1);

namespace Modules\Seguridad\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\Seguridad\Models\Usuario;

/**
 * DAS-01 · Panel de operación (versión Fase 0).
 *
 * En la Fase 0 el panel es el «hello world» autenticado: confirma que
 * la guardia unificada, los Gates y la variante de menú por rol
 * funcionan. Las tarjetas de KPIs llegan con la Fase 2 (cartera).
 */
class PanelController extends Controller
{
    public function index(Request $request): View
    {
        /** @var Usuario $usuario */
        $usuario = $request->user();

        return view('seguridad::panel', [
            'usuario' => $usuario,
            'rol' => $usuario->rolEnum(),
            'menu' => $this->menuPara($usuario),
        ]);
    }

    /**
     * Variante de menú por rol (tabla 6 de la Especificación UI).
     *
     * @return array<int, array{seccion: string, items: array<int, string>}>
     */
    private function menuPara($usuario): array
    {
        return match ($usuario->rolSlug()) {
            'especialista' => [
                ['seccion' => 'Operación', 'items' => ['Cartera (a mi cargo)', 'Cobranza', 'Ficha de cliente']],
                ['seccion' => 'Análisis', 'items' => ['Reportes (ámbito propio)']],
            ],
            'supervisor' => [
                ['seccion' => 'Operación', 'items' => ['Panel de operación', 'Cartera (todo el equipo)', 'Cobranza + aprobaciones', 'Ficha de cliente', 'Asignación']],
                ['seccion' => 'Análisis', 'items' => ['Reportes']],
            ],
            'adm_cartera' => [
                ['seccion' => 'Operación', 'items' => ['Asignación', 'Cartera', 'Cobranza', 'Ficha de cliente']],
                ['seccion' => 'Análisis', 'items' => ['Reportes']],
            ],
            'analista_conciliacion' => [
                ['seccion' => 'Operación', 'items' => ['Cobranza (conciliaciones)', 'Ficha de cliente']],
                ['seccion' => 'Análisis', 'items' => ['Reportes']],
            ],
            'adm_tecnico' => [
                ['seccion' => 'Administración', 'items' => ['Integración ERP', 'Usuarios y parámetros']],
            ],
            'auditor' => [
                ['seccion' => 'Operación', 'items' => ['Panel de operación (solo lectura)', 'Cartera (lectura)', 'Cobranza (lectura)']],
                ['seccion' => 'Análisis', 'items' => ['Reportes']],
            ],
            default => [],
        };
    }
}
