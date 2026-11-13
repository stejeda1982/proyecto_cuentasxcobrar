<?php

declare(strict_types=1);

namespace Modules\Seguridad\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\Seguridad\Models\Auditoria;
use Modules\Seguridad\Models\Usuario;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de roles (T0.4 — RNF-SEG-02).
 *
 * Uso: ->middleware('rol:supervisor') o 'rol:supervisor,adm_cartera'.
 * Un usuario sin ninguno de los roles requeridos recibe 403, que la UI
 * muestra como ERR-01 «No tiene permisos para acceder a este recurso».
 * El acceso denegado queda registrado en auditoría (RNF-SEG-03).
 */
class EnsureRol
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $usuario = Auth::user();

        if (! $usuario instanceof Usuario || ! in_array($usuario->rolSlug(), $roles, true)) {
            $auditoriaUsuarioId = null;
            $auditoriaUsuarioTexto = 'anónimo';

            if ($usuario instanceof Usuario) {
                $auditoriaUsuarioId = (int) $usuario->getKey();
                $auditoriaUsuarioTexto = $usuario->usuario;
            }

            Auditoria::create([
                'usuario_id' => $auditoriaUsuarioId,
                'usuario_texto' => $auditoriaUsuarioTexto,
                'evento' => 'acceso_denegado',
                'auditable_type' => self::class,
                'auditable_id' => (string) $request->route()?->getName(),
                'valores_nuevos' => ['ruta' => $request->path(), 'roles_requeridos' => $roles],
                'ip' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
            ]);

            Log::channel('seguridad')->warning('acceso_denegado', [
                'ruta' => $request->path(),
                'roles_requeridos' => $roles,
            ]);

            abort(403, 'No tiene permisos para acceder a este recurso.');
        }

        return $next($request);
    }
}
