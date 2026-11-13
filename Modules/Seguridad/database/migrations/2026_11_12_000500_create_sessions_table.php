<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla sessions (T0.2): el guardia unificada usa sesiones de base de
 * datos (más trazable en staging multi-instancia que el driver file).
 * La migración original de Laravel (0001_users) se sustituyó por las
 * tablas del módulo Seguridad; la de sesiones se conserva aquí porque
 * el driver SESSION_DRIVER=database la requiere.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sessions')) {
            return;
        }

        Schema::create('sessions', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->foreignId('user_id')->nullable()->index();
            $t->string('ip_address', 45)->nullable();
            $t->text('user_agent')->nullable();
            $t->longText('payload');
            $t->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
