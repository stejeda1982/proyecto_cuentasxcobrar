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
 * Tabla configuracion del módulo Seguridad (T0.2).
 *
 * Parámetros operativos del sistema con trazabilidad de quién cambió qué
 * (columnas actualizado_por y auditoría vía Observer). Las credenciales
 * sensibles del ERP no viven aquí: se almacenan cifradas y enmascaradas
 * conforme a RNF-SEG-05 (Fase 1 las gestionará el módulo Integración).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create(nombreTabla('seguridad', 'configuracion'), function (Blueprint $t) {
            $t->id();
            $t->string('clave', 80)->unique();          // p. ej. sesion.expiracion_min
            $t->text('valor')->nullable();
            $t->string('tipo', 20)->default('string');  // string | integer | boolean | json
            $t->text('descripcion')->nullable();
            $t->foreignId('actualizado_por')->nullable()->constrained(nombreTabla('seguridad', 'usuarios'));
            $t->timestampsTz();
        });

        DB::table(nombreTabla('seguridad', 'configuracion'))->insert([
            ['clave' => 'sesion.expiracion_min', 'valor' => '30', 'tipo' => 'integer', 'descripcion' => 'Expiración de sesión por inactividad en minutos (RNF-SEG-01)', 'created_at' => now(), 'updated_at' => now()],
            ['clave' => 'sesion.aviso_min', 'valor' => '5', 'tipo' => 'integer', 'descripcion' => 'Aviso previo a la expiración de sesión en minutos', 'created_at' => now(), 'updated_at' => now()],
            ['clave' => 'login.intentos_max', 'valor' => '5', 'tipo' => 'integer', 'descripcion' => 'Intentos fallidos antes del bloqueo temporal (15 min)', 'created_at' => now(), 'updated_at' => now()],
            ['clave' => 'auditoria.retencion_dias', 'valor' => '730', 'tipo' => 'integer', 'descripcion' => 'Retención mínima del registro de auditoría en días', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists(nombreTabla('seguridad', 'configuracion'));
    }
};
