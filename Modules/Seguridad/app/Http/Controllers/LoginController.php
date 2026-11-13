<?php

declare(strict_types=1);

namespace Modules\Seguridad\Http\Controllers;

use Illuminate\Cache\RateLimiter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Modules\Seguridad\Exceptions\LdapNoDisponibleException;

/**
 * Inicio de sesión (T0.3) según la especificación LOG-01:
 * pestañas «Cuenta corporativa (LDAP)» y «Usuario del sistema»,
 * mensajes literales de validación y bloqueo tras 5 intentos.
 */
class LoginController extends Controller
{
    /**
     * Muestra el formulario (LOG-01). Con sesión ya activa redirige a
     * la pantalla de inicio del rol ( tabla 4 de la especificación UI).
     */
    public function show(Request $request)
    {
        if (Auth::check()) {
            return redirect('/panel');
        }

        return view('seguridad::login', [
            'origen' => $request->query('origen', 'ldap'),
            'aviso' => $request->query('aviso'),
        ]);
    }

    /**
     * Autentica con la guardia unificada. Rate limit 5 intentos/10 min
     * por usuario+IP (LOG-01, bloqueo temporal).
     */
    public function login(Request $request): RedirectResponse
    {
        $credenciales = $request->validate([
            'usuario' => ['required', 'string', 'min:3', 'max:60'],
            'password' => ['required', 'string'],
        ], [
            'usuario.required' => 'Ingrese su usuario.',
            'password.required' => 'Ingrese su contraseña.',
        ]);

        $throttleKey = strtolower($request->input('usuario')).'|'.$request->ip();

        if ($this->limiter()->tooManyAttempts($throttleKey, $this->maxAttempts())) {
            $this->ldapDisponible($request);

            return $this->bloqueado();
        }

        try {
            $ok = Auth::attempt(
                ['usuario' => $credenciales['usuario'], 'password' => $credenciales['password']],
                $request->boolean('recordar'),
            );
        } catch (LdapNoDisponibleException) {
            // El directorio cayó: no se consume intento, se informa.
            return back()->withInput($request->only('usuario'))
                ->withErrors(['ldap' => 'El directorio corporativo no responde. Intente más tarde o use un usuario del sistema.']);
        }

        if (! $ok) {
            $this->limiter()->hit($throttleKey, $this->decaySeconds());

            throw ValidationException::withMessages([
                'usuario' => 'Usuario o contraseña incorrectos.',
            ]);
        }

        $this->limiter()->clear($throttleKey);
        $request->session()->regenerate();

        return redirect()->intended('/panel');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    private function bloqueado(): RedirectResponse
    {
        throw ValidationException::withMessages([
            'usuario' => 'Demasiados intentos fallidos. Espere 15 minutos e inténtelo nuevamente.',
        ]);
    }

    private function maxAttempts(): int
    {
        return (int) config('seguridad.login.intentos_max', 5);
    }

    private function decaySeconds(): int
    {
        return 15 * 60;
    }

    private function limiter()
    {
        return app(RateLimiter::class);
    }

    /**
     * En bloqueo no hay contacto con el directorio: evita abrir
     * conexiones LDAP para usuarios ya bloqueados.
     */
    private function ldapDisponible(Request $request): void
    {
        Log::debug('Login bloqueado por rate limit', ['usuario' => $request->input('usuario')]);
    }
}
