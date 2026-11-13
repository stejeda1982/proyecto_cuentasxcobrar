<?php

declare(strict_types=1);

namespace Modules\Seguridad\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Seguridad\Models\Rol;
use Modules\Seguridad\Models\Usuario;

/**
 * Seeder de la Fase 0: usuarios técnicos locales de prueba para validar
 * la guardia unificada y la variante de menú por rol (criterio de
 * aceptación 4.6.1: un usuario técnico inicia sesión con credenciales
 * locales). En producción estos usuarios se crean por consola con
 * `seguridad:crear-usuario`.
 */
class SeguridadDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $roles = Rol::pluck('id', 'slug');

        $usuarios = [
            ['usuario' => 'admin.tecnico', 'nombre' => 'Administrador Técnico', 'rol' => 'adm_tecnico', 'correo' => 'tecnico@cxc.local'],
            ['usuario' => 'supervisor', 'nombre' => 'Supervisora de Cobranza', 'rol' => 'supervisor', 'correo' => 'supervisor@cxc.local'],
            ['usuario' => 'especialista', 'nombre' => 'Especialista Comercial', 'rol' => 'especialista', 'correo' => 'especialista@cxc.local'],
            ['usuario' => 'auditor', 'nombre' => 'Auditor de Consulta', 'rol' => 'auditor', 'correo' => 'auditor@cxc.local'],
        ];

        foreach ($usuarios as $u) {
            Usuario::query()->updateOrCreate(
                ['usuario' => $u['usuario']],
                [
                    'nombre' => $u['nombre'],
                    'correo' => $u['correo'],
                    'password_hash' => Hash::make('CxcFase0!2026'),
                    'origen' => 'local',
                    'rol_id' => $roles[$u['rol']],
                    'activo' => true,
                ],
            );
        }
    }
}
