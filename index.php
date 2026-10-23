<?php
/**
 * Gestor de Tareas - Aplicación monolítica (PHP + MySQL)
 * Toda la lógica (controlador + vista) vive en este archivo.
 */
session_start();
require_once __DIR__ . '/db.php';

// ---------- Utilidades ----------
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function flash(?string $msg = null, string $tipo = 'ok')
{
    if ($msg !== null) { $_SESSION['flash'] = ['msg' => $msg, 'tipo' => $tipo]; return null; }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function redirigir(): void
{
    $qs = $_POST['volver'] ?? '';
    header('Location: index.php' . ($qs !== '' ? '?' . $qs : ''));
    exit;
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

$PRIORIDADES = ['baja' => 'Baja', 'media' => 'Media', 'alta' => 'Alta'];
$ESTADOS     = ['pendiente' => 'Pendiente', 'en_progreso' => 'En progreso', 'completada' => 'Completada'];

// ---------- Controlador (acciones POST) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        flash('Sesión expirada, intenta de nuevo.', 'error');
        redirigir();
    }

    $accion = $_POST['accion'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);

    try {
        if ($accion === 'guardar') {
            $titulo = trim($_POST['titulo'] ?? '');
            if ($titulo === '' || mb_strlen($titulo) > 150) {
                flash('El título es obligatorio (máx. 150 caracteres).', 'error');
                redirigir();
            }
            $datos = [
                ':titulo'       => $titulo,
                ':descripcion'  => trim($_POST['descripcion'] ?? '') ?: null,
                ':prioridad'    => array_key_exists($_POST['prioridad'] ?? '', $PRIORIDADES) ? $_POST['prioridad'] : 'media',
                ':estado'       => array_key_exists($_POST['estado'] ?? '', $ESTADOS) ? $_POST['estado'] : 'pendiente',
                ':fecha_limite' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['fecha_limite'] ?? '') ? $_POST['fecha_limite'] : null,
                ':categoria_id' => ((int)($_POST['categoria_id'] ?? 0)) ?: null,
            ];
            if ($id > 0) {
                $datos[':id'] = $id;
                db()->prepare('UPDATE tareas SET titulo=:titulo, descripcion=:descripcion, prioridad=:prioridad,
                               estado=:estado, fecha_limite=:fecha_limite, categoria_id=:categoria_id WHERE id=:id')
                    ->execute($datos);
                flash('Tarea actualizada.');
            } else {
                db()->prepare('INSERT INTO tareas (titulo, descripcion, prioridad, estado, fecha_limite, categoria_id)
                               VALUES (:titulo, :descripcion, :prioridad, :estado, :fecha_limite, :categoria_id)')
                    ->execute($datos);
                flash('Tarea creada.');
            }
        } elseif ($accion === 'alternar' && $id > 0) {
            db()->prepare("UPDATE tareas SET estado = IF(estado='completada','pendiente','completada') WHERE id = ?")
                ->execute([$id]);
            flash('Estado actualizado.');
        } elseif ($accion === 'eliminar' && $id > 0) {
            db()->prepare('DELETE FROM tareas WHERE id = ?')->execute([$id]);
            flash('Tarea eliminada.');
        } elseif ($accion === 'categoria') {
            $nombre = trim($_POST['nombre'] ?? '');
            $color  = preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['color'] ?? '') ? $_POST['color'] : '#4f46e5';
            if ($nombre !== '') {
                db()->prepare('INSERT INTO categorias (nombre, color) VALUES (?, ?)')->execute([$nombre, $color]);
                flash('Categoría creada.');
            }
        }
    } catch (PDOException $ex) {
        flash(APP_DEBUG ? 'Error BD: ' . $ex->getMessage() : 'Ocurrió un error al guardar.', 'error');
    }
    redirigir();
}

// ---------- Consultas (GET) ----------
$fEstado    = array_key_exists($_GET['estado'] ?? '', $ESTADOS) ? $_GET['estado'] : '';
$fCategoria = (int)($_GET['categoria'] ?? 0);
$fBuscar    = trim($_GET['q'] ?? '');

$where = [];
$params = [];
if ($fEstado !== '')  { $where[] = 't.estado = :estado';          $params[':estado'] = $fEstado; }
if ($fCategoria > 0)  { $where[] = 't.categoria_id = :cat';       $params[':cat'] = $fCategoria; }
if ($fBuscar !== '')  { $where[] = '(t.titulo LIKE :q OR t.descripcion LIKE :q2)';
                        $params[':q'] = "%$fBuscar%"; $params[':q2'] = "%$fBuscar%"; }

$sql = "SELECT t.*, c.nombre AS categoria, c.color
        FROM tareas t LEFT JOIN categorias c ON c.id = t.categoria_id"
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
     . " ORDER BY (t.estado = 'completada'), FIELD(t.prioridad,'alta','media','baja'),
                 t.fecha_limite IS NULL, t.fecha_limite, t.id DESC";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$tareas = $stmt->fetchAll();

$categorias = db()->query('SELECT * FROM categorias ORDER BY nombre')->fetchAll();
$stats = db()->query("SELECT COUNT(*) total,
                             SUM(estado='pendiente') pendientes,
                             SUM(estado='en_progreso') progreso,
                             SUM(estado='completada') completadas
                      FROM tareas")->fetch();
$avance = $stats['total'] ? round($stats['completadas'] * 100 / $stats['total']) : 0;

$volver = http_build_query(array_filter(['estado' => $fEstado, 'categoria' => $fCategoria ?: '', 'q' => $fBuscar]));
$flash  = flash();
$hoy    = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="topbar">
    <div class="contenedor topbar__in">
        <h1 class="logo">✔ <?= e(APP_NAME) ?></h1>
        <button class="btn btn--primario" data-abrir="modalTarea">+ Nueva tarea</button>
    </div>
</header>

<main class="contenedor">
    <?php if ($flash): ?>
        <div class="alerta alerta--<?= e($flash['tipo']) ?>" role="status"><?= e($flash['msg']) ?></div>
    <?php endif; ?>

    <section class="stats">
        <div class="stat"><span><?= (int)$stats['total'] ?></span>Total</div>
        <div class="stat"><span><?= (int)$stats['pendientes'] ?></span>Pendientes</div>
        <div class="stat"><span><?= (int)$stats['progreso'] ?></span>En progreso</div>
        <div class="stat"><span><?= (int)$stats['completadas'] ?></span>Completadas</div>
        <div class="stat stat--avance">
            <span><?= $avance ?>%</span>Avance
            <div class="barra"><div style="width:<?= $avance ?>%"></div></div>
        </div>
    </section>

    <form class="filtros" method="get">
        <input type="search" name="q" placeholder="Buscar tarea…" value="<?= e($fBuscar) ?>">
        <select name="estado">
            <option value="">Todos los estados</option>
            <?php foreach ($ESTADOS as $k => $v): ?>
                <option value="<?= $k ?>" <?= $fEstado === $k ? 'selected' : '' ?>><?= $v ?></option>
            <?php endforeach; ?>
        </select>
        <select name="categoria">
            <option value="">Todas las categorías</option>
            <?php foreach ($categorias as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $fCategoria === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn">Filtrar</button>
        <?php if ($volver): ?><a class="btn btn--texto" href="index.php">Limpiar</a><?php endif; ?>
        <button type="button" class="btn btn--texto" data-abrir="modalCategoria">+ Categoría</button>
    </form>

    <?php if (!$tareas): ?>
        <p class="vacio">No hay tareas que coincidan. ¡Crea una nueva!</p>
    <?php endif; ?>

    <ul class="lista">
        <?php foreach ($tareas as $t):
            $vencida = $t['fecha_limite'] && $t['fecha_limite'] < $hoy && $t['estado'] !== 'completada'; ?>
            <li class="tarea tarea--<?= e($t['estado']) ?>">
                <form method="post" class="tarea__check">
                    <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                    <input type="hidden" name="volver" value="<?= e($volver) ?>">
                    <input type="hidden" name="accion" value="alternar">
                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                    <button class="check" title="Marcar como completada / pendiente"
                            aria-label="Alternar estado"><?= $t['estado'] === 'completada' ? '✓' : '' ?></button>
                </form>
                <div class="tarea__cuerpo">
                    <h3><?= e($t['titulo']) ?></h3>
                    <?php if ($t['descripcion']): ?><p><?= nl2br(e($t['descripcion'])) ?></p><?php endif; ?>
                    <div class="etiquetas">
                        <span class="tag tag--<?= e($t['prioridad']) ?>"><?= $PRIORIDADES[$t['prioridad']] ?></span>
                        <span class="tag"><?= $ESTADOS[$t['estado']] ?></span>
                        <?php if ($t['categoria']): ?>
                            <span class="tag" style="--c:<?= e($t['color']) ?>"><i class="punto"></i><?= e($t['categoria']) ?></span>
                        <?php endif; ?>
                        <?php if ($t['fecha_limite']): ?>
                            <span class="tag <?= $vencida ? 'tag--vencida' : '' ?>">📅 <?= date('d/m/Y', strtotime($t['fecha_limite'])) ?><?= $vencida ? ' · vencida' : '' ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="tarea__acciones">
                    <button class="btn btn--icono" title="Editar" data-editar='<?= e(json_encode($t, JSON_UNESCAPED_UNICODE)) ?>'>✎</button>
                    <form method="post" data-confirmar="¿Eliminar esta tarea?">
                        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                        <input type="hidden" name="volver" value="<?= e($volver) ?>">
                        <input type="hidden" name="accion" value="eliminar">
                        <input type="hidden" name="id" value="<?= $t['id'] ?>">
                        <button class="btn btn--icono btn--peligro" title="Eliminar">🗑</button>
                    </form>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
</main>

<footer class="pie contenedor">
    <?= e(APP_NAME) ?> · Aplicación monolítica HTML5 + CSS3 + JS + PHP + MySQL · <?= e(APP_AUTOR) ?> · <?= date('Y') ?>
</footer>

<!-- Modal: crear / editar tarea -->
<dialog id="modalTarea" class="modal">
    <form method="post" id="formTarea">
        <h2 id="tituloModal">Nueva tarea</h2>
        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
        <input type="hidden" name="volver" value="<?= e($volver) ?>">
        <input type="hidden" name="accion" value="guardar">
        <input type="hidden" name="id" value="0">

        <label>Título *<input name="titulo" maxlength="150" required></label>
        <label>Descripción<textarea name="descripcion" rows="3"></textarea></label>
        <div class="fila">
            <label>Prioridad
                <select name="prioridad">
                    <?php foreach ($PRIORIDADES as $k => $v): ?><option value="<?= $k ?>" <?= $k === 'media' ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Estado
                <select name="estado">
                    <?php foreach ($ESTADOS as $k => $v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?>
                </select>
            </label>
        </div>
        <div class="fila">
            <label>Fecha límite<input type="date" name="fecha_limite"></label>
            <label>Categoría
                <select name="categoria_id">
                    <option value="">Sin categoría</option>
                    <?php foreach ($categorias as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['nombre']) ?></option><?php endforeach; ?>
                </select>
            </label>
        </div>
        <div class="modal__pie">
            <button type="button" class="btn btn--texto" data-cerrar>Cancelar</button>
            <button class="btn btn--primario">Guardar</button>
        </div>
    </form>
</dialog>

<!-- Modal: nueva categoría -->
<dialog id="modalCategoria" class="modal">
    <form method="post">
        <h2>Nueva categoría</h2>
        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
        <input type="hidden" name="volver" value="<?= e($volver) ?>">
        <input type="hidden" name="accion" value="categoria">
        <div class="fila">
            <label>Nombre<input name="nombre" maxlength="60" required></label>
            <label>Color<input type="color" name="color" value="#4f46e5"></label>
        </div>
        <div class="modal__pie">
            <button type="button" class="btn btn--texto" data-cerrar>Cancelar</button>
            <button class="btn btn--primario">Crear</button>
        </div>
    </form>
</dialog>

<script src="assets/app.js"></script>
</body>
</html>
