<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Panel de operación — Cuentas por Cobrar</title>
<style>
:root{
  --primary:#0E3A44;--primary-2:#14637A;--accent:#2E8B96;--accent-soft:#C8DDE2;
  --surface:#F1F5F6;--surface-2:#F7FAFB;--line:#E2E8EA;--ink:#16242A;--muted:#6B7B80;
}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;
  background:var(--surface);color:var(--ink);font-size:13.5px}
.layout{display:grid;grid-template-columns:244px 1fr;min-height:100vh}
aside{background:var(--primary);color:#fff;padding:18px 0}
aside .marca{padding:0 18px 14px;font-weight:700;font-size:14px;border-bottom:1px solid rgba(255,255,255,.15);margin-bottom:12px}
aside .seccion{padding:10px 18px 4px;font-size:10px;text-transform:uppercase;letter-spacing:.12em;color:#9fc4cc}
aside .item{padding:7px 18px;font-size:12.5px;color:#d6e4e8}
aside .item.disclaimer{color:#8fb4bd;font-style:italic;font-size:11.5px}
header{background:#fff;border-bottom:1px solid var(--line);padding:12px 22px;display:flex;justify-content:space-between;align-items:center}
header h1{font-size:17px;color:var(--primary)}
.user{display:flex;align-items:center;gap:10px}
.avatar{width:30px;height:30px;border-radius:50%;background:var(--accent-soft);color:var(--primary);
  display:flex;align-items:center;justify-content:center;font-weight:700;font-size:11px}
.user .rol{font-size:11px;color:var(--muted)}
main{padding:22px}
.kpi-row{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:18px}
.kpi{background:#fff;border:1px solid var(--line);border-radius:10px;padding:16px}
.kpi .lbl{font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;margin-bottom:6px}
.kpi .val{font-size:22px;font-weight:700;color:var(--primary)}
.nota{background:#fff;border:1px solid var(--line);border-left:4px solid var(--accent);border-radius:0 10px 10px 0;padding:14px 18px;margin-bottom:18px;font-size:12.5px;color:#1F3A40}
.bye{margin-left:14px;font-size:12px;color:var(--muted)}
.bye button{background:none;border:0;color:var(--accent);cursor:pointer;font-size:12px;text-decoration:underline}
</style>
</head>
<body>
<div class="layout">
  <aside>
    <div class="marca">Cuentas por Cobrar</div>
    @foreach ($menu as $grupo)
      <div class="seccion">{{ $grupo['seccion'] }}</div>
      @foreach ($grupo['items'] as $item)
        <div class="item">{{ $item }}</div>
      @endforeach
    @endforeach
    <div class="item disclaimer">Fase 0 — las pantallas de negocio se habilitan en las fases 1-5.</div>
  </aside>
  <div>
    <header>
      <h1>DAS-01 · Panel de operación</h1>
      <div class="user">
        <div class="avatar">{{ strtoupper(substr($usuario->nombre, 0, 1) ?? 'U') }}</div>
        <div>
          <div>{{ $usuario->nombre }}</div>
          <div class="rol">{{ $rol?->etiqueta() ?? 'Sin rol' }}</div>
        </div>
        <form method="POST" action="{{ route('logout') }}" class="bye">@csrf
          <button type="submit">Cerrar sesión</button>
        </form>
      </div>
    </header>
    <main>
      <div class="nota">
        <strong>Base operable (Hito M0).</strong> Autenticación LDAP/usuarios propios,
        autorización por Gates y Policies, auditoría de modelos y sesiones, CI y despliegue base.
        Este panel confirma la variante de navegación del rol
        «{{ $rol?->etiqueta() ?? 'sin rol' }}» conforme a la tabla 6 de la Especificación UI.
      </div>
      <div class="kpi-row">
        <div class="kpi"><div class="lbl">Cartera total</div><div class="val">—</div></div>
        <div class="kpi"><div class="lbl">Vencida (+30)</div><div class="val">—</div></div>
        <div class="kpi"><div class="lbl">DSO</div><div class="val">—</div></div>
        <div class="kpi"><div class="lbl">CEI</div><div class="val">—</div></div>
      </div>
      <div class="nota">Los indicadores se calculan a partir de la Fase 2 (Cartera y Aging),
      cuando la carga del ERP esté operativa (Fase 1).</div>
    </main>
  </div>
</div>
</body>
</html>
