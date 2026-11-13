# Sistema de Gestión de Cuentas por Cobrar — Fase 0 (Base del Sistema)

Implementación de la **Fase 0 del Plan de Desarrollo (PLN v1)**: los cimientos
transversales sobre los que se construyen las fases 1 a 5. Cubre las tareas
T0.1 a T0.8: andamiaje Laravel con `laravel-modules`, módulo Seguridad con
autenticación LDAP + usuarios propios (guardia unificada), autorización por
Gates y Policies, auditoría de modelos y sesiones, pipeline CI, logging
estructurado y despliegue base.

## Referencias del proyecto

| Documento | Rol |
|---|---|
| SRS v1 (`SRS_Sistema_Gestion_Cuentas_por_Cobrar_v1.docx`) | Requisitos (RF/RNF) y matriz de roles (§2.3) |
| ARQ v1 (`ARQ_Arquitectura_Tecnica_PHP_v1.docx`) | Monolito modular, capa por módulo, LDAP (§7.1) |
| DDB v1 (`DDB_Modulo_Integracion_PostgreSQL_v1.docx`) | Esquemas `integracion` y `cartera` (Fase 1) |
| PLN v1 (`PLN_Plan_Desarrollo_Fases_v1.docx`) | Tareas de la Fase 0 (tabla 5) y criterios §4.6 |
| UIS v1 (`UIS_Especificacion_Interfaz_Usuario_v1.docx`) | Pantallas LOG-01/DAS-01 y matriz de permisos (tabla 5) |

## Estructura

```
fase0-cxc/
├── app/SharedKernel/            # Kernel compartido entre módulos
│   ├── Enums/Rol.php            #   Roles del SRS §2.3
│   ├── Enums/Permiso.php        #   Matriz de permisos (UIS tabla 5)
│   └── ValueObjects/UsuarioId.php
├── Modules/
│   ├── Seguridad/               # Módulo de seguridad (T0.2-T0.5)
│   │   ├── app/Auth/CxcUserProvider.php      # Guardia unificada LDAP+local
│   │   ├── app/Contracts/LdapAuthenticator.php  # Puerto LDAP
│   │   ├── app/Services/LdapRecordAuthenticator.php  # Adaptador LdapRecord
│   │   ├── app/Services/NullLdapAuthenticator.php    # Sin directorio (dev)
│   │   ├── app/Http/Middleware/EnsureRol.php   # rol:supervisor,…
│   │   ├── app/Http/Middleware/AsignarRequestId.php  # T0.7
│   │   ├── app/Observers/AuditoriaObserver.php # T0.5
│   │   ├── app/Listeners/RegistrarAuditoriaLogin.php
│   │   └── database/migrations/ # roles, usuarios, auditoria, configuracion
│   ├── Integracion/             # Esqueleto (Fase 1: conector ERP)
│   └── Cartera/                 # Esqueleto (Fase 2: cartera/aging)
├── tests/Feature/Fase0Test.php  # Suite Pest de la fase (18 tests)
├── .github/workflows/ci.yml     # T0.6: Pint → PHPStan → Pest
├── docker-compose.yml           # T0.8: Nginx/PHP-FPM/PG16/Redis + respaldos
└── deploy/                      # Dockerfile, nginx.conf, respaldo_pg.sh
```

## Puesta en marcha (desarrollo)

```bash
composer install
cp .env.example .env             # DB_CONNECTION=sqlite para desarrollo local
php artisan key:generate
php artisan migrate --seed       # Crea roles y usuarios de prueba

php artisan serve                # http://127.0.0.1:8000
```

Usuarios sembrados (contraseña `CxcFase0!2026`, solo entornos de prueba):

| Usuario | Rol | Menú visible |
|---|---|---|
| `admin.tecnico` | Administrador técnico | Integración ERP, Usuarios y parámetros |
| `supervisor` | Supervisor de cobranza | Panel + cartera equipo + aprobaciones + asignación |
| `especialista` | Especialista comercial | Cartera propia, cobranza, reportes propios |
| `auditor` | Auditor / consulta | Solo lectura |

## Verificación

```bash
vendor/bin/pint --test       # Estilo PSR-12
vendor/bin/phpstan analyse   # Análisis estático (nivel 4)
vendor/bin/pest              # Suite de la fase (SQLite en memoria)
```

Estado verificado: **Pint ✓ · PHPStan 0 errores · Pest 18/18 (43 aserciones)**.

Los tests cubren los criterios de aceptación §4.6 del PLN que son
verificables sin directorio LDAP real:

- usuario técnico local inicia sesión con la guardia unificada (T0.3);
- Gates/Policies deniegan 403 al especialista en rutas de administración (T0.4);
- toda modificación auditable queda en `seguridad.auditoria` con valores
  antes/después y sin datos sensibles en claro (T0.5, RNF-SEG-03/05);
- health check `/up` responde 200 (T0.7).

## Despliegue (staging)

```bash
cp .env.example .env   # configurar DB_PASSWORD y LDAP_*
docker compose up -d --build
docker compose exec app php artisan migrate --force
```

El servicio `backups` ejecuta `pg_dump` diario con verificación de
integridad (`pg_restore --list`) y retención de 14 días (RNF-DIS-03).

## LDAP en staging/producción

La guardia unificada delega en el directorio corporativo cuando existe un
usuario `origen=ldap`. El adaptador real (`LdapRecordAuthenticator`) requiere
`ext-ldap` y estas variables (credenciales nunca en el código, RNF-SEG-05):

```
LDAP_ENABLED=true
LDAP_HOST=ldap.corporativo.example
LDAP_BASE_DN="dc=corp,dc=example"
LDAP_BIND_DN="cn=cxc,ou=services,dc=corp,dc=example"
LDAP_BIND_PASSWORD=***
```

En desarrollo (`LDAP_ENABLED=false`) el puerto resuelve al adaptador nulo y
el login usa solo usuarios locales, tal como prevé la mitigación del riesgo
T0.3 del PLN («comenzar con usuarios propios y añadir LDAP cuando esté
disponible»).

## Desviaciones respecto al PLN (documentadas)

1. **Laravel 13 en lugar de Laravel 11.** A noviembre de 2026, Laravel 11
   está fuera de su ventana de seguridad y Composer bloquea su instalación
   por avisos PKSA. Se usa la versión estable vigente (13.42); la tarea T0.1
   (andamiaje + laravel-modules + SharedKernel) es idéntica en sustancia.
2. **PHPStan sin plugin de Laravel.** `phpstan-laravel` aún no publica
   versión estable compatible con Laravel 13; se analiza con PHPStan puro
   nivel 4 y anotaciones `@property` en los modelos Eloquent.
3. **Entorno de desarrollo sin PostgreSQL.** El sandbox local usa SQLite
   (migraciones portables: esquema `seguridad.` en PostgreSQL,
   `seguridad_` en SQLite). El despliegue docker-compose usa PostgreSQL 16
   conforme al ARQ; el CI provisiona PostgreSQL 16 + Redis como servicios.
4. **ext-ldap ausente en el sandbox.** El adaptador LDAP real está
   implementado y referenciado, pero solo se activa con la extensión
   disponible (imagen Docker y CI la incluyen). Los tests ejercitan la
   guardia con usuarios locales.

## Próximas fases

- **Fase 1** — Integración ERP (T1.1-T1.15): conector API REST, carga batch
  idempotente, bitácora y cola de errores sobre el esquema `integracion`
  del DDB. El módulo `Integracion` ya está creado como esqueleto.
- **Fase 2** — Cartera y Aging; **Fase 3** — Cobranza; **Fase 4** —
  Asignación; **Fase 5** — Reportes y endurecimiento.
