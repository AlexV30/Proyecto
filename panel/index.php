<?php
// ════════════════════════════════════════════════════
//  Campo Fresco — Panel de Administración
// ════════════════════════════════════════════════════
session_name('cf_panel');
session_start();
set_time_limit(300); // 5 min para backups largos

const PANEL_PASS = 'admin123';
const DB_HOST    = 'localhost';
const DB_NAME    = 'campofrescodb';
const DB_USER    = 'campofreso';
const DB_PASS    = 'CampoFreso2026!';
const OPS        = '/home/valle/backup/panel_ops.sh';

// ─── Base de datos ───────────────────────────────────
function db(): PDO {
    static $pdo;
    if (!$pdo) $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    return $pdo;
}

// ─── Helpers ────────────────────────────────────────
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES); }
function cmd(string $args): string {
    return shell_exec('sudo -u valle '.OPS.' '.$args.' 2>&1') ?? '';
}

// ─── Auth ────────────────────────────────────────────
$auth_error = '';
if (isset($_POST['do_login'])) {
    if ($_POST['pass'] === PANEL_PASS) {
        $_SESSION['panel'] = true;
        header('Location: '.$_SERVER['PHP_SELF']); exit;
    }
    $auth_error = 'Contraseña incorrecta';
}
if (isset($_POST['do_logout'])) {
    session_destroy();
    header('Location: '.$_SERVER['PHP_SELF']); exit;
}
$logged = !empty($_SESSION['panel']);

// ─── Sección activa ──────────────────────────────────
$s   = $_GET['s'] ?? 'usuarios';
$msg = ''; $msg_ok = true;

// ─── Acciones ────────────────────────────────────────
if ($logged) {

    // ── USUARIOS ──
    if ($s === 'usuarios') {
        if (isset($_POST['crear_usuario'])) {
            $nom  = trim($_POST['nombre']);
            $mail = trim($_POST['email']);
            $pw   = trim($_POST['password']);
            if ($nom && $mail && $pw) {
                try {
                    db()->prepare('INSERT INTO usuarios (nombre,email,password) VALUES (?,?,?)')->execute([$nom,$mail,password_hash($pw,PASSWORD_BCRYPT)]);
                    $msg = 'Usuario '.$mail.' creado correctamente';
                } catch (Exception $e) { $msg = 'Error: '.$e->getMessage(); $msg_ok=false; }
            } else { $msg = 'Rellena todos los campos'; $msg_ok=false; }
        }
        if (isset($_POST['borrar_usuario'])) {
            db()->prepare('DELETE FROM usuarios WHERE id=?')->execute([(int)$_POST['id']]);
            $msg = 'Usuario eliminado';
        }
        if (isset($_POST['cambiar_pass'])) {
            $pw = trim($_POST['nueva_pass']);
            if ($pw) {
                db()->prepare('UPDATE usuarios SET password=? WHERE id=?')->execute([password_hash($pw,PASSWORD_BCRYPT),(int)$_POST['id']]);
                $msg = 'Contraseña actualizada';
            } else { $msg = 'La contraseña no puede estar vacía'; $msg_ok=false; }
        }
    }

    // ── PRODUCTOS ──
    if ($s === 'productos') {
        if (isset($_POST['crear_producto'])) {
            db()->prepare('INSERT INTO productos (nombre,descripcion,precio,categoria) VALUES (?,?,?,?)')->execute([
                trim($_POST['nombre']), trim($_POST['descripcion']),
                (float)$_POST['precio'], $_POST['categoria']
            ]);
            $msg = 'Producto creado';
        }
        if (isset($_POST['borrar_producto'])) {
            db()->prepare('DELETE FROM productos WHERE id=?')->execute([(int)$_POST['id']]);
            $msg = 'Producto eliminado';
        }
        if (isset($_POST['editar_producto'])) {
            db()->prepare('UPDATE productos SET nombre=?,descripcion=?,precio=?,categoria=? WHERE id=?')->execute([
                trim($_POST['nombre']), trim($_POST['descripcion']),
                (float)$_POST['precio'], $_POST['categoria'], (int)$_POST['id']
            ]);
            $msg = 'Producto actualizado';
        }
    }

    // ── MENSAJES ──
    if ($s === 'mensajes') {
        if (isset($_POST['marcar_leido'])) {
            db()->prepare('UPDATE mensajes SET leido=1 WHERE id=?')->execute([(int)$_POST['id']]);
            $msg = 'Mensaje marcado como leído';
        }
        if (isset($_POST['borrar_mensaje'])) {
            db()->prepare('DELETE FROM mensajes WHERE id=?')->execute([(int)$_POST['id']]);
            $msg = 'Mensaje eliminado';
        }
    }

    // ── PEDIDOS ──
    if ($s === 'pedidos') {
        if (isset($_POST['borrar_pedido'])) {
            db()->prepare('DELETE FROM pedidos WHERE id=?')->execute([(int)$_POST['id']]);
            $msg = 'Pedido eliminado';
        }
    }

    // ── BACKUP ──
    if ($s === 'backup') {
        if (isset($_POST['hacer_backup'])) {
            // Lanzar en segundo plano para no bloquear el panel
            exec('nohup sudo -u valle /home/valle/backup/panel_ops.sh backup > /tmp/cf_backup.log 2>&1 &');
            $_SESSION['backup_running'] = time();
            header('Location: ?s=backup&iniciado=1'); exit;
        }
        if (isset($_POST['restaurar']) && !empty($_POST['archivo'])) {
            $nombre = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_POST['archivo']);
            $out = cmd('restore '.escapeshellarg($nombre));
            $msg = trim($out) ?: 'Restauración completada';
            $msg_ok = !str_contains($out, 'Error');
        }
    }
}

// ─── Datos para las secciones ────────────────────────
$usuarios = $productos = $backups = $mensajes = $pedidos = [];
$log_backup = $log_deploy = '';
$n_mensajes = $n_pedidos = 0;
if ($logged) {
    if ($s === 'usuarios')  $usuarios  = db()->query('SELECT id,nombre,email,fecha_registro FROM usuarios ORDER BY id')->fetchAll();
    if ($s === 'productos') $productos = db()->query('SELECT id,nombre,descripcion,precio,categoria FROM productos ORDER BY id')->fetchAll();
    if ($s === 'mensajes')  $mensajes  = db()->query('SELECT * FROM mensajes ORDER BY leido ASC, fecha DESC')->fetchAll();
    if ($s === 'pedidos')   $pedidos   = db()->query('SELECT * FROM pedidos ORDER BY fecha DESC')->fetchAll();
    $n_mensajes = (int)db()->query('SELECT COUNT(*) FROM mensajes WHERE leido=0')->fetchColumn();
    $n_pedidos  = (int)db()->query('SELECT COUNT(*) FROM pedidos')->fetchColumn();
    if ($s === 'backup') {
        $raw = cmd('list');
        if (trim($raw)) {
            foreach (explode("\n", trim($raw)) as $linea) {
                if ($linea) { [$nombre,$size] = explode('|', $linea.'|'); $backups[] = ['nombre'=>trim($nombre),'size'=>trim($size)]; }
            }
        }
    }
    if ($s === 'logs') {
        $log_backup = cmd('log_backup');
        $log_deploy = cmd('log_deploy');
    }
}

// ─── Estadísticas dashboard ──────────────────────────
$stats = [];
if ($logged && $s === 'usuarios') $stats = ['total_usuarios' => db()->query('SELECT COUNT(*) FROM usuarios')->fetchColumn()];
if ($logged && $s === 'productos') $stats = ['total_productos' => db()->query('SELECT COUNT(*) FROM productos')->fetchColumn()];

// ─── Editar producto (GET) ────────────────────────────
$edit_prod = null;
if ($logged && $s === 'productos' && isset($_GET['editar'])) {
    $edit_prod = db()->prepare('SELECT * FROM productos WHERE id=?');
    $edit_prod->execute([(int)$_GET['editar']]);
    $edit_prod = $edit_prod->fetch();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Campo Fresco — Panel Admin</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',sans-serif;background:#0f1117;color:#e2e8f0;min-height:100vh}

/* LOGIN */
.login-wrap{display:flex;align-items:center;justify-content:center;min-height:100vh;background:linear-gradient(135deg,#0f1117,#1a2332)}
.login-box{background:#1e2535;border:1px solid #2d3748;border-radius:16px;padding:48px 40px;width:360px;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.5)}
.login-box .logo{font-size:48px;margin-bottom:12px}
.login-box h1{font-size:22px;font-weight:700;color:#e2e8f0;margin-bottom:4px}
.login-box p{color:#64748b;font-size:14px;margin-bottom:28px}
.login-box input[type=password]{width:100%;padding:12px 16px;background:#0f1117;border:1px solid #2d3748;border-radius:8px;color:#e2e8f0;font-size:15px;outline:none;margin-bottom:12px}
.login-box input:focus{border-color:#22c55e}
.login-box .btn-login{width:100%;padding:12px;background:#22c55e;border:none;border-radius:8px;color:#0f1117;font-size:15px;font-weight:700;cursor:pointer}
.login-box .btn-login:hover{background:#16a34a}
.msg{padding:10px 14px;border-radius:8px;margin:12px 0;font-size:14px}
.msg.err{background:rgba(239,68,68,.15);border:1px solid #ef4444;color:#fca5a5}
.msg.ok{background:rgba(34,197,94,.15);border:1px solid #22c55e;color:#86efac}

/* LAYOUT */
.layout{display:flex;min-height:100vh}

/* SIDEBAR */
.sidebar{width:230px;background:#13192a;border-right:1px solid #1e2d3d;display:flex;flex-direction:column;padding:24px 0;position:fixed;top:0;left:0;height:100vh}
.sidebar .brand{padding:0 20px 24px;border-bottom:1px solid #1e2d3d;margin-bottom:16px}
.sidebar .brand span{font-size:22px;font-weight:800;color:#22c55e;display:block}
.sidebar .brand small{font-size:11px;color:#475569;text-transform:uppercase;letter-spacing:.5px}
.sidebar a{display:flex;align-items:center;gap:10px;padding:11px 20px;color:#94a3b8;text-decoration:none;font-size:14px;font-weight:500;transition:.15s}
.sidebar a:hover{background:#1e2d3d;color:#e2e8f0}
.sidebar a.active{background:#172033;color:#22c55e;border-right:3px solid #22c55e}
.sidebar a .icon{font-size:18px;width:24px;text-align:center}
.sidebar .bottom{margin-top:auto;padding:16px 20px;border-top:1px solid #1e2d3d}
.btn-logout{width:100%;padding:9px;background:transparent;border:1px solid #334155;border-radius:8px;color:#64748b;font-size:13px;cursor:pointer}
.btn-logout:hover{border-color:#ef4444;color:#ef4444;background:rgba(239,68,68,.08)}

/* MAIN */
.main{margin-left:230px;flex:1;padding:32px}
.page-title{font-size:22px;font-weight:700;color:#f1f5f9;margin-bottom:24px}
.page-title small{font-size:13px;color:#64748b;font-weight:400;margin-left:8px}

/* CARDS */
.card{background:#1e2535;border:1px solid #2d3748;border-radius:12px;overflow:hidden;margin-bottom:24px}
.card-head{padding:16px 20px;border-bottom:1px solid #2d3748;display:flex;align-items:center;justify-content:space-between}
.card-head h3{font-size:15px;font-weight:600;color:#e2e8f0}
.card-body{padding:20px}

/* TABLE */
table{width:100%;border-collapse:collapse;font-size:14px}
th{padding:10px 14px;text-align:left;color:#64748b;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;border-bottom:1px solid #2d3748}
td{padding:12px 14px;border-bottom:1px solid #1a2332;color:#cbd5e1;vertical-align:middle}
tr:last-child td{border-bottom:none}
tr:hover td{background:rgba(255,255,255,.02)}

/* BADGES */
.badge{display:inline-block;padding:3px 8px;border-radius:20px;font-size:11px;font-weight:600}
.badge-frutas{background:rgba(251,146,60,.15);color:#fb923c}
.badge-verduras{background:rgba(34,197,94,.15);color:#22c55e}
.badge-basicos{background:rgba(148,163,184,.15);color:#94a3b8}

/* BUTTONS */
.btn{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:7px;border:none;font-size:13px;font-weight:500;cursor:pointer;text-decoration:none;transition:.15s}
.btn-primary{background:#22c55e;color:#0f1117}
.btn-primary:hover{background:#16a34a}
.btn-danger{background:rgba(239,68,68,.15);color:#ef4444;border:1px solid rgba(239,68,68,.3)}
.btn-danger:hover{background:rgba(239,68,68,.25)}
.btn-warn{background:rgba(251,191,36,.15);color:#fbbf24;border:1px solid rgba(251,191,36,.3)}
.btn-warn:hover{background:rgba(251,191,36,.25)}
.btn-sm{padding:5px 10px;font-size:12px}
.btn-blue{background:rgba(59,130,246,.15);color:#60a5fa;border:1px solid rgba(59,130,246,.3)}
.btn-blue:hover{background:rgba(59,130,246,.25)}

/* FORMS */
.form-row{display:grid;gap:12px;margin-bottom:16px}
.form-row.cols-2{grid-template-columns:1fr 1fr}
.form-row.cols-3{grid-template-columns:1fr 1fr 1fr}
.form-group label{display:block;font-size:12px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px}
.form-group input,.form-group select,.form-group textarea{width:100%;padding:9px 12px;background:#0f1117;border:1px solid #2d3748;border-radius:7px;color:#e2e8f0;font-size:14px;outline:none}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{border-color:#22c55e}
.form-group textarea{resize:vertical;min-height:70px}
.form-group select option{background:#1e2535}

/* BACKUP LIST */
.backup-item{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-radius:8px;background:#13192a;border:1px solid #1e2d3d;margin-bottom:8px}
.backup-item .bname{font-size:13px;color:#94a3b8;font-family:monospace}
.backup-item .bsize{font-size:12px;color:#475569;margin:0 12px}

/* LOGS */
.log-box{background:#0a0e17;border:1px solid #1e2d3d;border-radius:8px;padding:16px;font-family:monospace;font-size:12px;color:#64748b;max-height:350px;overflow-y:auto;white-space:pre-wrap;word-break:break-all;line-height:1.6}

/* MODAL */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:100;align-items:center;justify-content:center}
.modal-overlay.show{display:flex}
.modal{background:#1e2535;border:1px solid #2d3748;border-radius:12px;padding:28px;max-width:420px;width:90%}
.modal h3{font-size:17px;font-weight:700;margin-bottom:8px;color:#f1f5f9}
.modal p{color:#94a3b8;font-size:14px;margin-bottom:20px}
.modal .actions{display:flex;gap:10px;justify-content:flex-end}

/* INFO GRID */
.info-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:24px}
.info-card{background:#13192a;border:1px solid #1e2d3d;border-radius:10px;padding:16px;text-align:center}
.info-card .val{font-size:28px;font-weight:800;color:#22c55e}
.info-card .lbl{font-size:12px;color:#475569;margin-top:4px}
</style>
</head>
<body>

<?php if (!$logged): ?>
<!-- ════ LOGIN ════ -->
<div class="login-wrap">
  <div class="login-box">
    <div class="logo">🌿</div>
    <h1>Campo Fresco</h1>
    <p>Panel de Administración</p>
    <?php if ($auth_error): ?><div class="msg err"><?= h($auth_error) ?></div><?php endif; ?>
    <form method="post">
      <input type="password" name="pass" placeholder="Contraseña del panel" autofocus>
      <button type="submit" name="do_login" class="btn-login">Entrar</button>
    </form>
  </div>
</div>

<?php else: ?>
<!-- ════ PANEL ════ -->
<div class="layout">

  <!-- SIDEBAR -->
  <nav class="sidebar">
    <div class="brand">
      <span>🌿 Campo Fresco</span>
      <small>Panel Admin</small>
    </div>
    <a href="?s=usuarios" class="<?= $s==='usuarios'?'active':'' ?>"><span class="icon">👥</span> Usuarios</a>
    <a href="?s=productos" class="<?= $s==='productos'?'active':'' ?>"><span class="icon">🛒</span> Productos</a>
    <a href="?s=mensajes" class="<?= $s==='mensajes'?'active':'' ?>">
      <span class="icon">💬</span> Mensajes
      <?php if ($n_mensajes > 0): ?><span style="background:#ef4444;color:#fff;border-radius:20px;padding:1px 7px;font-size:11px;margin-left:auto"><?= $n_mensajes ?></span><?php endif; ?>
    </a>
    <a href="?s=pedidos" class="<?= $s==='pedidos'?'active':'' ?>">
      <span class="icon">📦</span> Pedidos
      <?php if ($n_pedidos > 0): ?><span style="background:#22c55e;color:#0f1117;border-radius:20px;padding:1px 7px;font-size:11px;margin-left:auto"><?= $n_pedidos ?></span><?php endif; ?>
    </a>
    <a href="?s=backup" class="<?= $s==='backup'?'active':'' ?>"><span class="icon">💾</span> Copias de Seguridad</a>
    <a href="?s=logs" class="<?= $s==='logs'?'active':'' ?>"><span class="icon">📋</span> Registros</a>
    <div class="bottom">
      <form method="post"><button type="submit" name="do_logout" class="btn-logout">⬅ Salir del panel</button></form>
    </div>
  </nav>

  <!-- MAIN -->
  <div class="main">

    <?php if ($msg): ?>
    <div class="msg <?= $msg_ok?'ok':'err' ?>"><?= h($msg) ?></div>
    <?php endif; ?>

    <!-- ══════ USUARIOS ══════ -->
    <?php if ($s === 'usuarios'): ?>
    <div class="page-title">👥 Usuarios <small><?= count($usuarios) ?> registrados</small></div>

    <div class="card">
      <div class="card-head"><h3>Lista de usuarios</h3></div>
      <div class="card-body" style="padding:0">
        <table>
          <thead><tr><th>#</th><th>Nombre</th><th>Email</th><th>Fecha registro</th><th>Acciones</th></tr></thead>
          <tbody>
          <?php foreach ($usuarios as $u): ?>
          <tr>
            <td><?= h((string)$u['id']) ?></td>
            <td><?= h($u['nombre']) ?></td>
            <td><?= h($u['email']) ?></td>
            <td><?= h($u['fecha_registro'] ?? '-') ?></td>
            <td>
              <button class="btn btn-blue btn-sm" onclick="openPassModal(<?= $u['id'] ?>, '<?= h($u['email']) ?>')">🔑 Contraseña</button>
              <button class="btn btn-danger btn-sm" onclick="openDelModal(<?= $u['id'] ?>, '<?= h($u['email']) ?>', 'usuario')">🗑 Borrar</button>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$usuarios): ?><tr><td colspan="5" style="text-align:center;color:#475569;padding:24px">No hay usuarios</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-head"><h3>➕ Crear nuevo usuario</h3></div>
      <div class="card-body">
        <form method="post">
          <div class="form-row cols-3">
            <div class="form-group"><label>Nombre</label><input type="text" name="nombre" required placeholder="Alex"></div>
            <div class="form-group"><label>Email</label><input type="email" name="email" required placeholder="alex@email.com"></div>
            <div class="form-group"><label>Contraseña</label><input type="password" name="password" required placeholder="••••••••"></div>
          </div>
          <button type="submit" name="crear_usuario" class="btn btn-primary">➕ Crear usuario</button>
        </form>
      </div>
    </div>

    <!-- Modal cambiar contraseña -->
    <div class="modal-overlay" id="modalPass">
      <div class="modal">
        <h3>🔑 Cambiar contraseña</h3>
        <p id="modalPassLabel">Usuario</p>
        <form method="post">
          <input type="hidden" name="id" id="passUserId">
          <div class="form-group" style="margin-bottom:16px"><label>Nueva contraseña</label><input type="password" name="nueva_pass" required placeholder="••••••••"></div>
          <div class="actions">
            <button type="button" class="btn" onclick="document.getElementById('modalPass').classList.remove('show')" style="background:#1a2332;color:#94a3b8">Cancelar</button>
            <button type="submit" name="cambiar_pass" class="btn btn-primary">Guardar</button>
          </div>
        </form>
      </div>
    </div>

    <!-- ══════ PRODUCTOS ══════ -->
    <?php elseif ($s === 'productos'): ?>
    <div class="page-title">🛒 Productos <small><?= count($productos) ?> en catálogo</small></div>

    <?php if ($edit_prod): ?>
    <div class="card">
      <div class="card-head"><h3>✏️ Editando: <?= h($edit_prod['nombre']) ?></h3></div>
      <div class="card-body">
        <form method="post">
          <input type="hidden" name="id" value="<?= $edit_prod['id'] ?>">
          <div class="form-row cols-3">
            <div class="form-group"><label>Nombre</label><input type="text" name="nombre" value="<?= h($edit_prod['nombre']) ?>" required></div>
            <div class="form-group"><label>Precio (€)</label><input type="number" name="precio" step="0.01" value="<?= h((string)$edit_prod['precio']) ?>" required></div>
            <div class="form-group"><label>Categoría</label>
              <select name="categoria">
                <option value="frutas" <?= $edit_prod['categoria']==='frutas'?'selected':'' ?>>Frutas</option>
                <option value="verduras" <?= $edit_prod['categoria']==='verduras'?'selected':'' ?>>Verduras</option>
                <option value="basicos" <?= $edit_prod['categoria']==='basicos'?'selected':'' ?>>Básicos</option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group"><label>Descripción</label><textarea name="descripcion"><?= h($edit_prod['descripcion'] ?? '') ?></textarea></div>
          </div>
          <div style="display:flex;gap:10px">
            <button type="submit" name="editar_producto" class="btn btn-primary">💾 Guardar cambios</button>
            <a href="?s=productos" class="btn" style="background:#1a2332;color:#94a3b8">Cancelar</a>
          </div>
        </form>
      </div>
    </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-head"><h3>Lista de productos</h3></div>
      <div class="card-body" style="padding:0">
        <table>
          <thead><tr><th>#</th><th>Nombre</th><th>Descripción</th><th>Precio</th><th>Categoría</th><th>Acciones</th></tr></thead>
          <tbody>
          <?php foreach ($productos as $p): ?>
          <tr>
            <td><?= h((string)$p['id']) ?></td>
            <td><?= h($p['nombre']) ?></td>
            <td style="color:#64748b;font-size:13px"><?= h(mb_substr($p['descripcion'] ?? '', 0, 50)) ?><?= strlen($p['descripcion'] ?? '')>50?'…':'' ?></td>
            <td style="color:#22c55e;font-weight:600"><?= number_format((float)$p['precio'],2) ?> €</td>
            <td><span class="badge badge-<?= h($p['categoria']) ?>"><?= h($p['categoria']) ?></span></td>
            <td>
              <a href="?s=productos&editar=<?= $p['id'] ?>" class="btn btn-blue btn-sm">✏️ Editar</a>
              <button class="btn btn-danger btn-sm" onclick="openDelModal(<?= $p['id'] ?>, '<?= h($p['nombre']) ?>', 'producto')">🗑 Borrar</button>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$productos): ?><tr><td colspan="6" style="text-align:center;color:#475569;padding:24px">No hay productos</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-head"><h3>➕ Añadir producto</h3></div>
      <div class="card-body">
        <form method="post">
          <div class="form-row cols-3">
            <div class="form-group"><label>Nombre</label><input type="text" name="nombre" required placeholder="Manzanas ecológicas"></div>
            <div class="form-group"><label>Precio (€)</label><input type="number" name="precio" step="0.01" min="0" required placeholder="2.50"></div>
            <div class="form-group"><label>Categoría</label>
              <select name="categoria">
                <option value="frutas">Frutas</option>
                <option value="verduras">Verduras</option>
                <option value="basicos">Básicos</option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group"><label>Descripción</label><textarea name="descripcion" placeholder="Descripción del producto..."></textarea></div>
          </div>
          <button type="submit" name="crear_producto" class="btn btn-primary">➕ Añadir producto</button>
        </form>
      </div>
    </div>

    <!-- ══════ MENSAJES ══════ -->
    <?php elseif ($s === 'mensajes'): ?>
    <div class="page-title">💬 Mensajes de contacto <small><?= $n_mensajes ?> sin leer</small></div>

    <div class="card">
      <div class="card-body" style="padding:0">
        <?php if (empty($mensajes)): ?>
        <p style="text-align:center;color:#475569;padding:28px">No hay mensajes todavía</p>
        <?php else: ?>
        <table>
          <thead><tr><th>Estado</th><th>Nombre</th><th>Email</th><th>Asunto</th><th>Fecha</th><th>Acciones</th></tr></thead>
          <tbody>
          <?php foreach ($mensajes as $m): ?>
          <tr style="<?= !$m['leido'] ? 'background:rgba(34,197,94,.04)' : 'opacity:.65' ?>">
            <td><?= $m['leido'] ? '<span style="color:#475569;font-size:12px">Leído</span>' : '<span style="color:#22c55e;font-weight:700;font-size:12px">● Nuevo</span>' ?></td>
            <td style="font-weight:600"><?= h($m['nombre']) ?></td>
            <td style="font-size:13px;color:#94a3b8"><?= h($m['email']) ?></td>
            <td><span class="badge badge-basicos"><?= h($m['asunto']) ?></span></td>
            <td style="font-size:12px;color:#64748b"><?= h($m['fecha']) ?></td>
            <td>
              <button class="btn btn-blue btn-sm" onclick='openMsgModal(<?= $m['id'] ?>, <?= json_encode($m['nombre']) ?>, <?= json_encode($m['email']) ?>, <?= json_encode($m['asunto']) ?>, <?= json_encode($m['mensaje']) ?>)'>👁 Ver</button>
              <?php if (!$m['leido']): ?>
              <form method="post" style="display:inline">
                <input type="hidden" name="id" value="<?= $m['id'] ?>">
                <button type="submit" name="marcar_leido" class="btn btn-primary btn-sm">✓ Leído</button>
              </form>
              <?php endif; ?>
              <button class="btn btn-danger btn-sm" onclick='openDelModal(<?= $m['id'] ?>, <?= json_encode($m['nombre']) ?>, "mensaje")'>🗑</button>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
    </div>

    <!-- Modal ver mensaje -->
    <div class="modal-overlay" id="modalMsg">
      <div class="modal" style="max-width:520px">
        <h3 id="modalMsgTitle">Mensaje</h3>
        <div style="margin:12px 0;font-size:13px;color:#64748b" id="modalMsgMeta"></div>
        <div class="log-box" id="modalMsgBody" style="max-height:220px;font-family:inherit;font-size:14px;color:#e2e8f0"></div>
        <div class="actions" style="margin-top:16px">
          <button class="btn" onclick="document.getElementById('modalMsg').classList.remove('show')" style="background:#1a2332;color:#94a3b8">Cerrar</button>
        </div>
      </div>
    </div>

    <!-- ══════ PEDIDOS ══════ -->
    <?php elseif ($s === 'pedidos'): ?>
    <div class="page-title">📦 Pedidos <small><?= count($pedidos ?? []) ?> realizados</small></div>

    <div class="info-grid" style="margin-bottom:24px">
      <div class="info-card">
        <div class="val"><?= $n_pedidos ?></div>
        <div class="lbl">Total pedidos</div>
      </div>
      <div class="info-card">
        <div class="val">
          <?php
            $total_fact = db()->query('SELECT COALESCE(SUM(total),0) FROM pedidos')->fetchColumn();
            echo number_format((float)$total_fact, 2);
          ?> €
        </div>
        <div class="lbl">Facturación total</div>
      </div>
    </div>

    <div class="card">
      <div class="card-head"><h3>Historial de pedidos</h3></div>
      <div class="card-body" style="padding:0">
        <?php if (empty($pedidos)): ?>
        <p style="text-align:center;color:#475569;padding:28px">No hay pedidos todavía</p>
        <?php else: ?>
        <table>
          <thead><tr><th>#</th><th>Usuario</th><th>Email</th><th>Productos</th><th>Total</th><th>Fecha</th><th>Acción</th></tr></thead>
          <tbody>
          <?php foreach ($pedidos as $p):
              $prods = json_decode($p['productos'], true) ?? [];
              $resumen = implode(', ', array_map(function($x){ return ($x['nombre'] ?? '?'); }, $prods));
          ?>
          <tr>
            <td><?= h((string)$p['id']) ?></td>
            <td style="font-weight:600"><?= h($p['usuario_nombre']) ?></td>
            <td style="font-size:13px;color:#94a3b8"><?= h($p['usuario_email']) ?></td>
            <td style="font-size:13px;color:#64748b;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= h($resumen) ?>"><?= h(mb_substr($resumen,0,60)).(mb_strlen($resumen)>60?'…':'') ?></td>
            <td style="color:#22c55e;font-weight:600"><?= number_format((float)$p['total'],2) ?> €</td>
            <td style="font-size:12px;color:#64748b"><?= h($p['fecha']) ?></td>
            <td>
              <button class="btn btn-blue btn-sm" onclick="openPedidoModal(<?= $p['id'] ?>, <?= json_encode($p['usuario_nombre']) ?>, <?= json_encode($p['usuario_email']) ?>, <?= json_encode($resumen) ?>, <?= json_encode($p['total']) ?>, <?= json_encode($p['fecha']) ?>)">👁 Ver</button>
              <button class="btn btn-danger btn-sm" onclick="openDelModal(<?= $p['id'] ?>, <?= json_encode('#'.$p['id'].' de '.$p['usuario_nombre']) ?>, 'pedido')">🗑</button>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
    </div>

    <!-- Modal ver pedido -->
    <div class="modal-overlay" id="modalPedido">
      <div class="modal" style="max-width:520px">
        <h3 id="modalPedidoTitle">Pedido</h3>
        <div style="margin:12px 0;font-size:13px;color:#64748b" id="modalPedidoMeta"></div>
        <div class="log-box" id="modalPedidoBody" style="max-height:200px;font-family:inherit;font-size:14px;color:#e2e8f0"></div>
        <div class="actions" style="margin-top:16px">
          <button class="btn" onclick="document.getElementById('modalPedido').classList.remove('show')" style="background:#1a2332;color:#94a3b8">Cerrar</button>
        </div>
      </div>
    </div>

    <!-- ══════ BACKUP ══════ -->
    <?php elseif ($s === 'backup'):
        $backup_iniciado = isset($_GET['iniciado']);
        $backup_running  = !empty($_SESSION['backup_running']) && (time() - $_SESSION['backup_running']) < 60;
        if ($backup_iniciado && !$backup_running) unset($_SESSION['backup_running']);
    ?>
    <?php if ($backup_iniciado && $backup_running): ?>
    <meta http-equiv="refresh" content="35;url=?s=backup">
    <?php endif; ?>

    <div class="page-title">💾 Copias de Seguridad <small><?= count($backups) ?> copias disponibles</small></div>

    <div class="card">
      <div class="card-head"><h3>Hacer copia de seguridad ahora</h3></div>
      <div class="card-body">
        <p style="color:#94a3b8;font-size:14px;margin-bottom:16px">Guarda los archivos web y la base de datos en el disco HDD. Las copias automáticas se hacen L-V a las 23:00h.</p>
        <?php if ($backup_iniciado && $backup_running): ?>
        <div class="msg ok" style="display:flex;align-items:center;gap:10px">
          <span style="font-size:20px">⏳</span>
          <div>
            <strong>Backup en proceso...</strong><br>
            <span style="font-size:13px">Esta página se refrescará sola en 35 segundos. No hagas nada.</span>
          </div>
        </div>
        <?php else: ?>
        <form method="post">
          <input type="hidden" name="hacer_backup" value="1">
          <button type="submit" class="btn btn-primary">💾 Hacer copia ahora</button>
        </form>
        <?php if ($msg): ?>
        <div style="margin-top:12px"><div class="msg <?= $msg_ok?'ok':'err' ?>"><?= h($msg) ?></div></div>
        <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-head"><h3>Copias disponibles</h3> </div>
      <div class="card-body">
        <?php if (!$backups): ?>
        <p style="color:#475569;text-align:center;padding:16px">No hay copias de seguridad todavía</p>
        <?php else: ?>
        <?php foreach ($backups as $b):
            // Convertir "2026-05-19_12-30-45" a "19/05/2026 a las 12:30:45"
            $partes = explode('_', $b['nombre']);
            $fecha_legible = isset($partes[0]) ? date('d/m/Y', strtotime($partes[0])) : $b['nombre'];
            $hora_legible  = isset($partes[1]) ? str_replace('-', ':', $partes[1]) : '';
        ?>
        <div class="backup-item">
          <div>
            <div style="font-size:15px;font-weight:600;color:#e2e8f0;margin-bottom:3px">
              📅 <?= h($fecha_legible) ?><?= $hora_legible ? ' &nbsp;🕐 '.h($hora_legible) : '' ?>
            </div>
            <div class="bname"><?= h($b['nombre']) ?>/</div>
          </div>
          <div style="display:flex;align-items:center;gap:12px">
            <span class="bsize"><?= h($b['size']) ?></span>
            <button class="btn btn-warn btn-sm" onclick="openRestoreModal('<?= h($b['nombre']) ?>')">⬆️ Restaurar</button>
          </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Modal restaurar -->
    <div class="modal-overlay" id="modalRestore">
      <div class="modal">
        <h3>⚠️ Restaurar copia de seguridad</h3>
        <p id="modalRestoreLabel">¿Seguro? Se sobreescribirán los archivos web y la base de datos con la copia seleccionada.</p>
        <form method="post">
          <input type="hidden" name="archivo" id="restoreArchivo">
          <div class="actions">
            <button type="button" class="btn" onclick="document.getElementById('modalRestore').classList.remove('show')" style="background:#1a2332;color:#94a3b8">Cancelar</button>
            <button type="submit" name="restaurar" class="btn btn-warn">⬆️ Restaurar</button>
          </div>
        </form>
      </div>
    </div>

    <!-- ══════ LOGS ══════ -->
    <?php elseif ($s === 'logs'): ?>
    <div class="page-title">📋 Registros del sistema</div>

    <div class="card">
      <div class="card-head"><h3>💾 Log de Backups</h3></div>
      <div class="card-body">
        <div class="log-box"><?= h($log_backup) ?></div>
      </div>
    </div>

    <div class="card">
      <div class="card-head"><h3>🚀 Log de Auto-deploy (GitHub → Servidor)</h3></div>
      <div class="card-body">
        <div class="log-box"><?= h($log_deploy) ?></div>
      </div>
    </div>

    <?php endif; ?>

  </div><!-- /main -->
</div><!-- /layout -->

<!-- Modal borrar genérico -->
<div class="modal-overlay" id="modalDel">
  <div class="modal">
    <h3 id="modalDelTitle">Confirmar eliminación</h3>
    <p id="modalDelLabel">¿Estás seguro?</p>
    <form method="post" id="modalDelForm">
      <input type="hidden" name="id" id="delId">
      <input type="hidden" name="del_type" id="delType">
      <div class="actions">
        <button type="button" class="btn" onclick="document.getElementById('modalDel').classList.remove('show')" style="background:#1a2332;color:#94a3b8">Cancelar</button>
        <button type="submit" id="delBtn" class="btn btn-danger">Eliminar</button>
      </div>
    </form>
  </div>
</div>

<?php endif; ?>

<script>
function openDelModal(id, nombre, tipo) {
    document.getElementById('delId').value = id;
    document.getElementById('modalDelLabel').textContent = '¿Eliminar "' + nombre + '"? Esta acción no se puede deshacer.';
    if (tipo === 'usuario') document.getElementById('delBtn').name = 'borrar_usuario';
    else if (tipo === 'producto') document.getElementById('delBtn').name = 'borrar_producto';
    else if (tipo === 'pedido') document.getElementById('delBtn').name = 'borrar_pedido';
    else document.getElementById('delBtn').name = 'borrar_mensaje';
    document.getElementById('modalDel').classList.add('show');
}
function openMsgModal(id, nombre, email, asunto, mensaje) {
    document.getElementById('modalMsgTitle').textContent = '💬 Mensaje de ' + nombre;
    document.getElementById('modalMsgMeta').textContent = email + ' · Asunto: ' + asunto;
    document.getElementById('modalMsgBody').textContent = mensaje;
    document.getElementById('modalMsg').classList.add('show');
}
function openPedidoModal(id, nombre, email, productos, total, fecha) {
    var m = document.getElementById('modalPedido');
    if (!m) return;
    document.getElementById('modalPedidoTitle').textContent = '📦 Pedido #' + id + ' de ' + nombre;
    document.getElementById('modalPedidoMeta').textContent = email + ' · ' + fecha + ' · Total: ' + parseFloat(total).toFixed(2) + ' €';
    document.getElementById('modalPedidoBody').textContent = productos;
    m.classList.add('show');
}
function openPassModal(id, email) {
    document.getElementById('passUserId').value = id;
    document.getElementById('modalPassLabel').textContent = 'Usuario: ' + email;
    document.getElementById('modalPass').classList.add('show');
}
function openRestoreModal(nombre) {
    document.getElementById('restoreArchivo').value = nombre;
    document.getElementById('modalRestoreLabel').textContent = '¿Restaurar la copia "' + nombre.replace('backup_','') + '"? Se sobreescribirán los archivos web y la base de datos.';
    document.getElementById('modalRestore').classList.add('show');
}
// Cerrar modales al hacer click fuera
document.querySelectorAll('.modal-overlay').forEach(m => {
    m.addEventListener('click', e => { if (e.target === m) m.classList.remove('show'); });
});
</script>
</body>
</html>
