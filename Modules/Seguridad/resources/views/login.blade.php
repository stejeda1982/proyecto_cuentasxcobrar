<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Iniciar sesión — Sistema de Gestión de Cuentas por Cobrar</title>
<style>
:root{
  --primary:#0E3A44;--primary-2:#14637A;--accent:#2E8B96;--accent-soft:#C8DDE2;
  --surface:#F1F5F6;--surface-2:#F7FAFB;--line:#E2E8EA;--ink:#16242A;--muted:#6B7B80;
  --err:#D9534F;--ok:#1E8E5A;
}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;
  min-height:100vh;display:flex;align-items:center;justify-content:center;
  background:linear-gradient(135deg,var(--primary) 0%,var(--primary-2) 100%);color:var(--ink)}
.card{background:#fff;border-radius:10px;box-shadow:0 4px 24px rgba(0,0,0,.25);
  width:380px;padding:28px}
.brand{font-size:11px;letter-spacing:.18em;color:var(--accent);font-weight:700;text-transform:uppercase;margin-bottom:6px}
h1{font-size:20px;color:var(--primary);margin-bottom:4px}
.sub{font-size:12.5px;color:var(--muted);margin-bottom:18px}
.tabs{display:flex;border-bottom:2px solid var(--line);margin-bottom:18px}
.tabs a{flex:1;text-align:center;font-size:12.5px;font-weight:600;padding:9px 0;
  color:var(--muted);text-decoration:none;border-bottom:2px solid transparent;margin-bottom:-2px}
.tabs a.active{color:var(--accent);border-bottom-color:var(--accent)}
label{display:block;font-size:12px;font-weight:600;margin-bottom:5px}
.field{margin-bottom:14px}
input{width:100%;padding:9px 11px;font-size:13.5px;border:1px solid var(--line);border-radius:7px}
input:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(46,139,150,.15)}
button{width:100%;padding:11px;background:var(--primary);color:#fff;border:0;border-radius:7px;
  font-size:12.5px;font-weight:600;cursor:pointer;margin-top:6px}
button:hover{background:var(--primary-2)}
button:disabled{opacity:.6;cursor:wait}
.error{background:#FBE0DE;color:#9B2723;border-radius:7px;padding:9px 11px;
  font-size:12px;margin-bottom:14px}
.ldap-error{background:#FBE8D4;color:#A4530E;border-radius:7px;padding:9px 11px;
  font-size:12px;margin-bottom:14px}
.aviso{background:var(--surface-2);border-left:3px solid var(--accent);border-radius:0 7px 7px 0;
  padding:9px 11px;font-size:12px;color:var(--muted);margin-bottom:14px}
.foot{margin-top:18px;font-size:10.5px;color:var(--muted);text-align:center}
</style>
</head>
<body>
<main class="card">
  <div class="brand">Cuentas por Cobrar</div>
  <h1>Iniciar sesión</h1>
  <p class="sub">Sistema de Gestión de Cuentas por Cobrar</p>

  @if($aviso === 'expirada')
    <div class="aviso">Su sesión ha expirado. Inicie sesión nuevamente.</div>
  @endif

  <div class="tabs">
    <a href="{{ url('/login?origen=ldap') }}" class="{{ $origen === 'ldap' ? 'active' : '' }}">Cuenta corporativa (LDAP)</a>
    <a href="{{ url('/login?origen=local') }}" class="{{ $origen === 'local' ? 'active' : '' }}">Usuario del sistema</a>
  </div>

  @if ($errors->has('ldap'))
    <div class="ldap-error">{{ $errors->first('ldap') }}</div>
  @endif

  @if ($errors->any() && ! $errors->has('ldap'))
    <div class="error">{{ $errors->first() }}</div>
  @endif

  <form method="POST" action="{{ route('login.attempt') }}">
    @csrf
    <input type="hidden" name="origen" value="{{ $origen }}">
    <div class="field">
      <label for="usuario">{{ $origen === 'local' ? 'Usuario del sistema' : 'Usuario LDAP' }}</label>
      <input id="usuario" name="usuario" value="{{ old('usuario') }}" autofocus
             autocomplete="off" placeholder="{{ $origen === 'local' ? 'admin.tecnico' : 'jperez' }}">
    </div>
    <div class="field">
      <label for="password">Contraseña</label>
      <input id="password" name="password" type="password" autocomplete="current-password">
    </div>
    <button type="submit">Iniciar sesión</button>
  </form>

  <p class="foot">Conexión cifrada · v1.0 · Acceso restringido a personal autorizado</p>
</main>
</body>
</html>
