<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Nombre de tabla portable: con prefijo de esquema en PostgreSQL
 * (seguridad.roles) y plano en SQLite (seguridad_roles), que no
 * soporta esquemas. La función es helper local de cada migración.
 */
if (! function_exists('nombreTabla')) {
    function nombreTabla(string $esquema, string $tabla): string
    {
        $driver = Schema::getConnection()->getDriverName();

        return $driver === 'sqlite' ? "{$esquema}_{$tabla}" : "{$esquema}.{$tabla}";
    }
}

/**
 * Tabla roles del módulo Seguridad (T0.2).
 *
 * Catálogo de roles conforme al SRS sección 2.3. La tabla materializa el
 * enum SharedKernel\Enums\Rol para integridad referencial e informe de
 * cobertura; la matriz de permisos vive en código (Permiso::porRol).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create(nombreTabla('seguridad', 'roles'), function (Blueprint $t) {
            $t->id();
            $t->string('slug', 40)->unique();
            $t->string('nombre', 80);
            $t->text('descripcion')->nullable();
            $t->timestampsTz();
        });

        DB::table(nombreTabla('seguridad', 'roles'))->insert([
            ['slug' => 'especialista', 'nombre' => 'Especialista comercial', 'descripcion' => 'Gestiona la cobranza de los clientes asignados', 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'supervisor', 'nombre' => 'Supervisor de cobranza', 'descripcion' => 'Dirige al equipo: prioriza cartera, aprueba conciliaciones, reasigna', 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'adm_cartera', 'nombre' => 'Administrador de cartera', 'descripcion' => 'Mantiene la estructura de la organización de cobranza y las asignaciones', 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'analista_conciliacion', 'nombre' => 'Analista de conciliación', 'descripcion' => 'Concilia diferencias entre el ERP y lo gestionado por el área comercial', 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'adm_tecnico', 'nombre' => 'Administrador técnico', 'descripcion' => 'Administra usuarios, roles, parámetros y monitorea la integración ERP', 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'auditor', 'nombre' => 'Auditor / consulta', 'descripcion' => 'Revisa la trazabilidad de la operación en modo de solo lectura', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists(nombreTabla('seguridad', 'roles'));
    }
};
