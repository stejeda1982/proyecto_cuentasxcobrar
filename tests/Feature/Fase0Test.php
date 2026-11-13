<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Modules\Seguridad\Database\Seeders\SeguridadDatabaseSeeder;
use Modules\Seguridad\Models\Auditoria;
use Modules\Seguridad\Models\Configuracion;
use Modules\Seguridad\Models\Rol;
use Modules\Seguridad\Models\Usuario;
use SharedKernel\Enums\Permiso;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(SeguridadDatabaseSeeder::class);
});

/**
 * T0.2 — Migraciones del esquema de seguridad.
 */
describe('T0.2 · Esquema de seguridad', function () {
    it('siembra los seis roles del SRS', function () {
        expect(Rol::count())->toBe(6)
            ->and(Rol::where('slug', 'supervisor')->exists())->toBeTrue()
            ->and(Rol::where('slug', 'auditor')->exists())->toBeTrue();
    });

    it('crea la tabla de auditoría con columnas antes/después', function () {
        expect(Schema::hasColumns('seguridad_auditoria', [
            'usuario_id', 'evento', 'auditable_type', 'auditable_id',
            'valores_anteriores', 'valores_nuevos',
        ]))->toBeTrue();
    });

    it(' carga parámetros base con valores por defecto', function () {
        $expiracion = Configuracion::where('clave', 'sesion.expiracion_min')->first();

        expect($expiracion)->not->toBeNull()
            ->and($expiracion->valorTipado())->toBe(30);
    });
});

/**
 * T0.3 — Guardia unificada: usuarios locales (LDAP se valida en staging).
 */
describe('T0.3 · Guardia unificada', function () {
    it('autentica un usuario técnico local con credenciales correctas', function () {
        $ok = Auth::attempt([
            'usuario' => 'admin.tecnico',
            'password' => 'CxcFase0!2026',
        ]);

        expect($ok)->toBeTrue()
            ->and(Auth::user()->rolSlug())->toBe('adm_tecnico');
    });

    it('rechaza credenciales incorrectas', function () {
        expect(Auth::attempt([
            'usuario' => 'admin.tecnico',
            'password' => ' incorrecta',
        ]))->toBeFalse();
    });

    it('rechaza usuarios inactivos aunque la contraseña sea válida', function () {
        $u = Usuario::where('usuario', 'especialista')->first();
        $u->update(['activo' => false]);

        expect(Auth::attempt([
            'usuario' => 'especialista',
            'password' => 'CxcFase0!2026',
        ]))->toBeFalse();
    });

    it('muestra el formulario de login (LOG-01) a visitantes', function () {
        $resp = $this->get('/login');

        $resp->assertOk()
            ->assertSee('Cuenta corporativa (LDAP)')
            ->assertSee('Usuario del sistema');
    });

    it('redirige /panel a login sin sesión', function () {
        $this->get('/panel')->assertRedirect('/login');
    });

    it('autentica por HTTP y muestra el menú del rol en el panel', function () {
        $usuario = Usuario::where('usuario', 'supervisor')->first();

        $this->actingAs($usuario)
            ->get('/panel')
            ->assertOk()
            ->assertSee('Cartera (todo el equipo)')
            ->assertSee('Asignación');
    });
});

/**
 * T0.4 — Gates y middleware de roles (matriz de permisos).
 */
describe('T0.4 · Gates y roles', function () {
    it('define los permisos de la matriz UI para cada rol', function () {
        $supervisor = Usuario::where('usuario', 'supervisor')->first();
        $auditor = Usuario::where('usuario', 'auditor')->first();

        expect($supervisor->tienePermiso(Permiso::AprobarConciliacion))->toBeTrue()
            ->and($auditor->tienePermiso(Permiso::AprobarConciliacion))->toBeFalse()
            ->and($auditor->tienePermiso(Permiso::VerCarteraConsolidada))->toBeTrue();
    });

    it('resuelve los Gates contra la matriz', function () {
        $especialista = Usuario::where('usuario', 'especialista')->first();
        $tecnico = Usuario::where('usuario', 'admin.tecnico')->first();

        expect(Gate::forUser($especialista)->allows(Permiso::RegistrarAccion->value))->toBeTrue()
            ->and(Gate::forUser($especialista)->allows(Permiso::AdministrarUsuarios->value))->toBeFalse()
            ->and(Gate::forUser($tecnico)->allows(Permiso::AdministrarUsuarios->value))->toBeTrue();
    });

    it('deniega 403 a un especialista en una ruta de administración', function () {
        $especialista = Usuario::where('usuario', 'especialista')->first();

        $this->actingAs($especialista)->get('/admin/usuarios')->assertForbidden();
    });

    it('permite el acceso al administrador técnico', function () {
        $tecnico = Usuario::where('usuario', 'admin.tecnico')->first();

        $this->actingAs($tecnico)
            ->get('/admin/usuarios')
            ->assertOk()
            ->assertSee('ADM-02');
    });

    it('registra en auditoría el acceso denegado', function () {
        $especialista = Usuario::where('usuario', 'especialista')->first();

        $this->actingAs($especialista)->get('/admin/usuarios');

        expect(Auditoria::where('evento', 'acceso_denegado')
            ->where('usuario_texto', 'especialista')
            ->exists())->toBeTrue();
    });
});

/**
 * T0.5 — Auditoría de modelos y sesiones (RNF-SEG-03).
 */
describe('T0.5 · Auditoría', function () {
    it('registra creación de un modelo auditable con valores nuevos', function () {
        $tecnico = Usuario::where('usuario', 'admin.tecnico')->first();

        $nuevo = Configuracion::create([
            'clave' => 'prueba.clave',
            'valor' => '1',
            'tipo' => 'boolean',
            'descripcion' => 'Prueba de auditoría',
            'actualizado_por' => $tecnico->id,
        ]);

        $registro = Auditoria::where('evento', 'created')
            ->where('auditable_type', Configuracion::class)
            ->where('auditable_id', (string) $nuevo->id)
            ->first();

        expect($registro)->not->toBeNull()
            ->and($registro->valores_nuevos['clave'])->toBe('prueba.clave');
    });

    it('nunca persiste atributos sensibles en claro', function () {
        $tecnico = Usuario::where('usuario', 'admin.tecnico')->first();

        // Una actualización de usuario (p. ej. cambio de contraseña).
        $tecnico->update(['password_hash' => bcrypt('OtraClave!2026')]);

        $registros = Auditoria::where('auditable_type', Usuario::class)->get();

        foreach ($registros as $r) {
            $json = json_encode([$r->valores_anteriores, $r->valores_nuevos]);
            expect($json)->not->toContain('password_hash')
                ->and($json)->not->toContain('OtraClave!2026');
        }
    });

    it('registra los eventos de sesión (login, intento fallido)', function () {
        Auth::attempt(['usuario' => 'supervisor', 'password' => 'CxcFase0!2026']);
        Auth::attempt(['usuario' => 'supervisor', 'password' => 'mala']);

        expect(Auditoria::where('evento', 'login')
            ->where('usuario_texto', 'supervisor')->exists())->toBeTrue()
            ->and(Auditoria::where('evento', 'intento_fallido')
                ->where('usuario_texto', 'supervisor')->exists())->toBeTrue();
    });
});

/**
 * T0.7 — Health check del despliegue base.
 */
describe('T0.7 · Health check', function () {
    it('responde /up con 200', function () {
        $this->get('/up')->assertOk();
    });
});
