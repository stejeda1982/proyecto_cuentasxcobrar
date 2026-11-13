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
 * Tabla usuarios del módulo Seguridad (T0.2).
 *
 * Da soporte al guardia unificado (T0.3): usuarios corporativos que se
 * autentican contra LDAP (origen='ldap', sin contraseña local) y usuarios
 * propios del sistema (origen='local', con hash de contraseña).
 * La migración por defecto de Laravel (tabla users) se elimina en favor
 * de esta tabla en esquema seguridad.
 */
return new class extends Migration
{
    public function up(): void
    {
        // La tabla users por defecto de Laravel no se usa: el dominio
        // de identidad vive en el módulo Seguridad.
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');

        Schema::create(nombreTabla('seguridad', 'usuarios'), function (Blueprint $t) {
            $t->id();
            $t->string('usuario', 60)->unique();          // uid LDAP o nombre de usuario local
            $t->string('nombre', 120);
            $t->string('correo', 160)->nullable()->unique();
            $t->string('password_hash', 255)->nullable(); // solo usuarios locales
            $t->enum('origen', ['ldap', 'local'])->default('ldap');
            $t->foreignId('rol_id')->constrained(nombreTabla('seguridad', 'roles'));
            $t->boolean('activo')->default(true);
            $t->timestampTz('ultimo_acceso', 6)->nullable();
            $t->timestampTz('desactivado_en', 6)->nullable();
            $t->rememberToken();
            $t->timestampsTz();

            $t->index(['activo', 'origen']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(nombreTabla('seguridad', 'usuarios'));
    }
};
