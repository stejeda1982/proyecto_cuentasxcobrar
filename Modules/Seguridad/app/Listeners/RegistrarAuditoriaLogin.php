<?php

declare(strict_types=1);

namespace Modules\Seguridad\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Modules\Seguridad\Models\Auditoria;
use Modules\Seguridad\Models\Usuario;
use Throwable;

/**
 * Listener de auditoría de autenticación (T0.5 — RNF-SEG-01/03).
 *
 * Registra los eventos de sesión del guardia unificada: logins exitosos,
 * intentos fallidos, bloqueos por fuerza bruta y cierres de sesión.
 * Los eventos se escriben de forma síncrona pero con costo mínimo
 * (una inserción); el listener de colas asíncronas llega con Horizon
 * en la Fase 1, y este contrato no cambia.
 */
class RegistrarAuditoriaLogin
{
    public function handleLogin(Login $evento): void
    {
        $this->registrar('login', $evento->user);
    }

    public function handleFailed(Failed $evento): void
    {
        $this->registrar('intento_fallido', $evento->user, [
            'usuario_intento' => $evento->credentials['usuario'] ?? null,
        ]);
    }

    public function handleLockout(Lockout $evento): void
    {
        $this->registrar('bloqueo', null, [
            'usuario_intento' => $evento->request->input('usuario'),
        ]);
    }

    public function handleLogout(Logout $evento): void
    {
        $this->registrar('logout', $evento->user);
    }

    private function registrar(string $evento, ?object $usuario, array $extra = []): void
    {
        // T0.7: canal de seguridad estructurado (JSON con request_id).
        Log::channel('seguridad')->info($evento, array_filter([
            'usuario' => $usuario->usuario ?? ($extra['usuario_intento'] ?? null),
        ] + $extra));

        try {
            Auditoria::create([
                'usuario_id' => $usuario->id ?? null,
                'usuario_texto' => $usuario->usuario ?? ($extra['usuario_intento'] ?? 'anónimo'),
                'evento' => $evento,
                'auditable_type' => Usuario::class,
                'auditable_id' => (string) ($usuario->id ?? 'sesion'),
                'valores_nuevos' => $extra ?: null,
                'ip' => Request::ip(),
                'user_agent' => substr((string) Request::userAgent(), 0, 255),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
