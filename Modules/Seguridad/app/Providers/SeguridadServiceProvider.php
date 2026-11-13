<?php

declare(strict_types=1);

namespace Modules\Seguridad\Providers;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Modules\Seguridad\Auth\CxcUserProvider;
use Modules\Seguridad\Contracts\LdapAuthenticator;
use Modules\Seguridad\Http\Middleware\EnsureRol;
use Modules\Seguridad\Listeners\RegistrarAuditoriaLogin;
use Modules\Seguridad\Models\Configuracion;
use Modules\Seguridad\Models\Usuario;
use Modules\Seguridad\Observers\AuditoriaObserver;
use Modules\Seguridad\Services\LdapRecordAuthenticator;
use Modules\Seguridad\Services\NullLdapAuthenticator;
use Nwidart\Modules\Support\ModuleServiceProvider;
use SharedKernel\Enums\Permiso;

class SeguridadServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Seguridad';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'seguridad';

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        // Puerto LDAP: adaptador real si ext-ldap está disponible, nulo si no.
        $this->app->bind(LdapAuthenticator::class, function () {
            if (extension_loaded('ldap') && env('LDAP_ENABLED', false)) {
                return $this->app->make(LdapRecordAuthenticator::class);
            }

            return $this->app->make(NullLdapAuthenticator::class);
        });
    }

    public function boot(): void
    {
        parent::boot(); // vistas, migraciones, traducciones y rutas del módulo

        $this->registerUserProvider();
        $this->registerGates();
        $this->registerMiddleware();
        $this->registerListeners();
        $this->registerObservers();
    }

    /**
     * Guardia unificada (T0.3): driver 'cxc' sobre el modelo Usuario.
     */
    private function registerUserProvider(): void
    {
        Auth::provider('cxc', function ($app, array $config) {
            return new CxcUserProvider(
                $app['hash'],
                $config['model'],
                $app->make(LdapAuthenticator::class),
            );
        });
    }

    /**
     * Gates y Policies base (T0.4 — RNF-SEG-02).
     *
     * Cada permiso del SharedKernel se define como un Gate que resuelve
     * la matriz Permiso::porRol(rol del usuario). El usuario sin rol
     * conocido no tiene permisos.
     */
    private function registerGates(): void
    {
        foreach (Permiso::cases() as $permiso) {
            Gate::define($permiso->value, function ($usuario) use ($permiso) {
                if (! $usuario instanceof Usuario) {
                    return false;
                }

                return $usuario->tienePermiso($permiso);
            });
        }

        // Gate compuesto de acceso a la administración (técnicos).
        Gate::define('administrar', fn ($usuario) => $usuario instanceof Usuario
            && ($usuario->tienePermiso(Permiso::AdministrarUsuarios)
                || $usuario->tienePermiso(Permiso::AdministrarParametros)));
    }

    private function registerMiddleware(): void
    {
        // Alias «rol» para rutas: ->middleware('rol:supervisor,adm_cartera')
        $this->app->make('router')->aliasMiddleware('rol', EnsureRol::class);
    }

    /**
     * Auditoría de eventos de autenticación (T0.5): login, logout,
     * intentos fallidos y bloqueos (RNF-SEG-01/03).
     */
    private function registerListeners(): void
    {
        Event::listen(
            Login::class,
            [RegistrarAuditoriaLogin::class, 'handleLogin']
        );
        Event::listen(
            Failed::class,
            [RegistrarAuditoriaLogin::class, 'handleFailed']
        );
        Event::listen(
            Logout::class,
            [RegistrarAuditoriaLogin::class, 'handleLogout']
        );
        Event::listen(
            Lockout::class,
            [RegistrarAuditoriaLogin::class, 'handleLockout']
        );
    }

    /**
     * Observers de auditoría (T0.5): todo modelo auditable registra
     * created/updated/deleted con valores antes/después (RNF-SEG-03).
     */
    private function registerObservers(): void
    {
        Configuracion::observe(AuditoriaObserver::class);
        Usuario::observe(AuditoriaObserver::class);
    }
}
