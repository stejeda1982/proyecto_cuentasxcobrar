<?php

declare(strict_types=1);

namespace Modules\Seguridad\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * T0.7 — Logging estructurado por solicitud.
 *
 * Asigna un request_id (header X-Request-Id o ULID generado) y enriquece
 * el contexto de todos los canales de log con usuario, método, ruta e
 * IP. El mismo id viaja en la respuesta para soporte y diagnóstico.
 */
class AsignarRequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = (string) ($request->header('X-Request-Id') ?? (string) str()->ulid());

        $contexto = [
            'request_id' => $requestId,
            'metodo' => $request->method(),
            'ruta' => $request->path(),
            'ip' => $request->ip(),
            'usuario' => $request->user()?->getAuthIdentifier(),
        ];

        // Contexto compartido por todos los canales de esta solicitud.
        Log::withContext($contexto);

        $respuesta = $next($request);
        $respuesta->headers->set('X-Request-Id', $requestId);

        return $respuesta;
    }
}
