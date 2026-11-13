<?php

declare(strict_types=1);

namespace Modules\Seguridad\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Modules\Seguridad\Models\Auditoria;
use Modules\Seguridad\Models\Usuario;
use Throwable;

/**
 * Observer de auditoría de modelos Eloquent (T0.5 — RNF-SEG-03).
 *
 * Registra created / updated / deleted / restored de los modelos que
 * lo declaran, con usuario, valores antes/después, IP y user agent.
 * Las claves sensibles (password_hash, remember_token) nunca se
 * persisten en el registro (RNF-SEG-05).
 *
 * El registro es best-effort: un fallo de auditoría no debe romper la
 * operación de negocio; se deja constancia en el log de seguridad.
 */
class AuditoriaObserver
{
    /** Atributos que jamás se auditan en claro. */
    private const OCULTOS = ['password_hash', 'remember_token', 'password', 'token'];

    public function created(Model $modelo): void
    {
        $this->registrar('created', $modelo, [], $this->atributos($modelo));
    }

    public function updated(Model $modelo): void
    {
        $this->registrar('updated', $modelo, $this->atributos($modelo, true), $this->atributos($modelo));
    }

    public function deleted(Model $modelo): void
    {
        $this->registrar('deleted', $modelo, $this->atributos($modelo, true), []);
    }

    public function restored(Model $modelo): void
    {
        $this->registrar('restored', $modelo, [], $this->atributos($modelo));
    }

    private function registrar(string $evento, Model $modelo, array $antes, array $despues): void
    {
        try {
            Auditoria::create([
                'usuario_id' => $this->usuarioId(),
                'usuario_texto' => $this->usuarioTexto(),
                'evento' => $evento,
                'auditable_type' => $modelo::class,
                'auditable_id' => (string) $modelo->getKey(),
                'valores_anteriores' => $antes ?: null,
                'valores_nuevos' => $despues ?: null,
                'ip' => app()->runningInConsole() ? 'consola' : (string) request()->ip(),
                'user_agent' => app()->runningInConsole() ? 'artisan' : substr((string) request()->userAgent(), 0, 255),
            ]);
        } catch (Throwable $e) {
            // La operación de negocio prevalece; el fallo queda en el log.
            report($e);
        }
    }

    private function atributos(Model $modelo, bool $soloCambiados = false): array
    {
        $cambios = $soloCambiados ? $modelo->getOriginal() : $modelo->getAttributes();

        return collect($cambios)
            ->except(self::OCULTOS)
            ->all();
    }

    private function usuarioId(): ?int
    {
        $usuario = Auth::user();

        return $usuario instanceof Usuario ? $usuario->id : null;
    }

    private function usuarioTexto(): string
    {
        $usuario = Auth::user();

        if ($usuario instanceof Usuario) {
            return $usuario->usuario;
        }

        return 'sistema';
    }
}
