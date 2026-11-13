<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
 * Tabla auditoria del módulo Seguridad (T0.2, T0.5 — RNF-SEG-03).
 *
 * Registro de solo inserción: toda creación, modificación y eliminación
 * lógica de modelos auditables deja usuario, fecha, operación y valores
 * antes/después. Ningún rol de aplicación modifica ni elimina filas
 * (RNF-SEG-06); la purga es un procedimiento administrativo externo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create(nombreTabla('seguridad', 'auditoria'), function (Blueprint $t) {
            $t->id();
            $t->foreignId('usuario_id')->nullable()->constrained(nombreTabla('seguridad', 'usuarios'));
            $t->string('usuario_texto', 120)->nullable(); // usuario del proceso (sistema/ldap uid) si no hay sesión
            $t->string('evento', 40);                     // created | updated | deleted | restored | login | logout | intento_fallido
            $t->string('auditable_type', 190);            // clase del modelo, p. ej. Modules\Seguridad\Models\Usuario
            $t->string('auditable_id', 64);            // id del registro (string: compatible con ULID futuros)
            $t->jsonb('valores_anteriores')->nullable();
            $t->jsonb('valores_nuevos')->nullable();
            $t->string('ip', 45)->nullable();
            $t->string('user_agent', 255)->nullable();
            $t->timestampTz('creado_en', 6)->useCurrent();

            $t->index(['auditable_type', 'auditable_id']);
            $t->index(['usuario_id', 'creado_en']);
            $t->index('creado_en');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(nombreTabla('seguridad', 'auditoria'));
    }
};
