<?php
declare(strict_types=1);

require_once __DIR__ . '/common.php';

$pdo = db();
$pageTitle = 'Historias';
$flashSuccess = '';
$flashError = '';
$ESTADOS = estadosMeta();

// --- Acciones (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');

    try {
        if ($action === 'create_story' || $action === 'update_story') {
            $titulo = trim((string)($_POST['titulo'] ?? ''));
            $descripcion = trim((string)($_POST['descripcion'] ?? ''));
            $responsable = trim((string)($_POST['responsable'] ?? ''));
            $estado = (string)($_POST['estado'] ?? 'nueva');
            $puntosRaw = (string)($_POST['puntos'] ?? '0');
            $sprint_idRaw = (string)($_POST['sprint_id'] ?? '0');
            $fecha_creacion = (string)($_POST['fecha_creacion'] ?? date('Y-m-d'));
            $fecha_finalizacion = trim((string)($_POST['fecha_finalizacion'] ?? ''));

            if ($titulo === '' || $descripcion === '' || $responsable === '') {
                throw new RuntimeException('Título, descripción y responsable son obligatorios.');
            }
            if (!isset($ESTADOS[$estado])) {
                throw new RuntimeException('Estado inválido.');
            }
            if (!ctype_digit($puntosRaw)) {
                throw new RuntimeException('Los puntos deben ser un número entero válido.');
            }
            $puntos = (int)$puntosRaw;
            if ($puntos < 0) {
                throw new RuntimeException('Los puntos no pueden ser negativos.');
            }
            if (!ctype_digit($sprint_idRaw) || (int)$sprint_idRaw <= 0) {
                throw new RuntimeException('Selecciona un sprint válido.');
            }
            $sprint_id = (int)$sprint_idRaw;

            if (!isValidDate($fecha_creacion)) {
                throw new RuntimeException('Fecha de creación inválida.');
            }
            $fecha_finalizacion_value = null;
            if ($fecha_finalizacion !== '') {
                if (!isValidDate($fecha_finalizacion)) {
                    throw new RuntimeException('Fecha finalización inválida.');
                }
                $fecha_finalizacion_value = $fecha_finalizacion;
            }

            if ($action === 'create_story') {
                $stmt = $pdo->prepare(
                    'INSERT INTO historias
                     (titulo, descripcion, responsable, estado, puntos, fecha_creacion, fecha_finalizacion, sprint_id)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    $titulo,
                    $descripcion,
                    $responsable,
                    $estado,
                    $puntos,
                    $fecha_creacion,
                    $fecha_finalizacion_value,
                    $sprint_id,
                ]);

                redirect('historias.php?ok=created');
            }

            $idRaw = (string)($_POST['id'] ?? '0');
            if (!ctype_digit($idRaw) || (int)$idRaw <= 0) {
                throw new RuntimeException('ID de historia inválido.');
            }
            $id = (int)$idRaw;

            $stmt = $pdo->prepare(
                'UPDATE historias
                 SET titulo = ?, descripcion = ?, responsable = ?, estado = ?, puntos = ?, fecha_creacion = ?, fecha_finalizacion = ?, sprint_id = ?
                 WHERE id = ?'
            );
            $stmt->execute([
                $titulo,
                $descripcion,
                $responsable,
                $estado,
                $puntos,
                $fecha_creacion,
                $fecha_finalizacion_value,
                $sprint_id,
                $id,
            ]);

            redirect('historias.php?ok=updated');
        }

        if ($action === 'delete_story') {
            $idRaw = (string)($_POST['id'] ?? '0');
            if (!ctype_digit($idRaw) || (int)$idRaw <= 0) {
                throw new RuntimeException('ID de historia inválido.');
            }
            $id = (int)$idRaw;

            $stmt = $pdo->prepare('DELETE FROM historias WHERE id = ?');
            $stmt->execute([$id]);

            redirect('historias.php?ok=deleted');
        }

        throw new RuntimeException('Acción no válida.');
    } catch (Throwable $e) {
        $flashError = $e->getMessage();
    }
}

// --- Mensajes ---
$ok = (string)($_GET['ok'] ?? '');
if ($ok === 'created') {
    $flashSuccess = 'Historia creada correctamente.';
} elseif ($ok === 'updated') {
    $flashSuccess = 'Historia actualizada correctamente.';
} elseif ($ok === 'deleted') {
    $flashSuccess = 'Historia eliminada correctamente.';
}

// --- Datos ---
$sprints = $pdo->query('SELECT id, nombre, fecha_inicio, fecha_fin FROM sprints ORDER BY fecha_inicio DESC, id DESC')->fetchAll();

$rowsHistorias = $pdo->query(
    'SELECT
        h.id,
        h.titulo,
        h.descripcion,
        h.responsable,
        h.estado,
        h.puntos,
        h.fecha_creacion,
        h.fecha_finalizacion,
        h.sprint_id,
        s.nombre AS sprint_nombre,
        s.fecha_inicio AS sprint_inicio,
        s.fecha_fin AS sprint_fin
     FROM historias h
     INNER JOIN sprints s ON s.id = h.sprint_id
     ORDER BY s.fecha_inicio DESC, h.id DESC'
)->fetchAll();

$historiasBySprint = [];
foreach ($rowsHistorias as $r) {
    $sid = (int)$r['sprint_id'];
    if (!isset($historiasBySprint[$sid])) {
        $historiasBySprint[$sid] = [
            'sprint_nombre' => $r['sprint_nombre'],
            'sprint_inicio' => $r['sprint_inicio'],
            'sprint_fin' => $r['sprint_fin'],
            'items' => [],
        ];
    }
    $historiasBySprint[$sid]['items'][] = $r;
}

// --- Modo edición ---
$editStory = null;
$editIdRaw = (string)($_GET['edit_id'] ?? '');
if ($editIdRaw !== '' && ctype_digit($editIdRaw)) {
    $stmt = $pdo->prepare(
        'SELECT id, titulo, descripcion, responsable, estado, puntos, fecha_creacion, fecha_finalizacion, sprint_id
         FROM historias
         WHERE id = ?'
    );
    $stmt->execute([(int)$editIdRaw]);
    $editStory = $stmt->fetch() ?: null;
}

$currentFecha = date('Y-m-d');
$formAction = $editStory ? 'update_story' : 'create_story';
$formTitle = $editStory ? 'Editar historia' : 'Crear nueva historia';
$formSubmitText = $editStory ? 'Guardar cambios' : 'Crear historia';

$form = [
    'id' => $editStory['id'] ?? '',
    'titulo' => $editStory['titulo'] ?? '',
    'descripcion' => $editStory['descripcion'] ?? '',
    'responsable' => $editStory['responsable'] ?? '',
    'estado' => $editStory['estado'] ?? 'nueva',
    'puntos' => isset($editStory['puntos']) ? (string)$editStory['puntos'] : '0',
    'sprint_id' => (string)($editStory['sprint_id'] ?? ($sprints[0]['id'] ?? '')),
    'fecha_creacion' => $editStory['fecha_creacion'] ?? $currentFecha,
    'fecha_finalizacion' => $editStory['fecha_finalizacion'] ?? '',
];
if ($form['fecha_finalizacion'] === null) {
    $form['fecha_finalizacion'] = '';
}

require __DIR__ . '/partials_header.php';
?>

<div class="grid section-gap-top">
    <main>
        <section class="card">
            <div class="row row-between">
                <h2 class="title-sm">Historias por Sprint</h2>
                <a class="btn btn-secondary btn-small" href="historias.php">Refrescar</a>
            </div>

            <p class="subtle top-gap-xs">Verás las historias agrupadas por sprint, con su estado, responsable y puntos.</p>

            <?php if (empty($sprints)): ?>
                <p class="muted top-gap-sm">
                    No hay sprints cargados. Crea un sprint primero en <a href="sprints.php">Sprints</a>.
                </p>
            <?php elseif (empty($historiasBySprint)): ?>
                <p class="subtle top-gap-sm">Aún no hay historias registradas. Usa el formulario para crear la primera.</p>
            <?php else: ?>
                <?php foreach ($historiasBySprint as $sid => $block): ?>
                    <article class="card inner-card">
                        <div class="row row-between">
                            <div>
                                <div class="strong"><?php echo h((string)$block['sprint_nombre']); ?></div>
                                <div class="muted tiny top-gap-2">
                                    <?php echo h((string)$block['sprint_inicio']); ?> - <?php echo h((string)$block['sprint_fin']); ?>
                                </div>
                            </div>
                            <div class="subtle align-right">
                                Historias: <strong><?php echo count($block['items']); ?></strong>
                            </div>
                        </div>

                        <table>
                            <thead>
                            <tr>
                                <th>Título</th>
                                <th>Responsable</th>
                                <th>Puntos</th>
                                <th>Estado</th>
                                <th>Creación</th>
                                <th>Finalización</th>
                                <th>Acciones</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($block['items'] as $item): ?>
                                <?php
                                    $estado = (string)$item['estado'];
                                    $badge = $ESTADOS[$estado] ?? ['label' => $estado, 'class' => 'badge-nueva'];
                                    $descripcion = (string)$item['descripcion'];
                                    $descShort = mb_strimwidth($descripcion, 0, 90, '...');
                                ?>
                                <tr>
                                    <td class="story-col">
                                        <div class="strong"><?php echo h((string)$item['titulo']); ?></div>
                                        <div class="muted tiny top-gap-2"><?php echo h($descShort); ?></div>
                                    </td>
                                    <td><?php echo h((string)$item['responsable']); ?></td>
                                    <td><?php echo h((string)$item['puntos']); ?></td>
                                    <td>
                                        <span class="badge <?php echo h((string)$badge['class']); ?>">
                                            <?php echo h((string)$badge['label']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo h((string)$item['fecha_creacion']); ?></td>
                                    <td><?php echo h((string)($item['fecha_finalizacion'] ?? '')); ?></td>
                                    <td>
                                        <div class="row action-row">
                                            <a class="btn btn-secondary btn-small" href="historias.php?edit_id=<?php echo (int)$item['id']; ?>#formulario">Editar</a>
                                            <form method="post" class="inline-form" onsubmit="return confirm('¿Eliminar esta historia?');">
                                                <input type="hidden" name="action" value="delete_story">
                                                <input type="hidden" name="id" value="<?php echo (int)$item['id']; ?>">
                                                <button class="btn btn-danger btn-small" type="submit">Eliminar</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>

    <aside>
        <section class="card" id="formulario">
            <div class="row row-between">
                <h2 class="title-sm"><?php echo h($formTitle); ?></h2>
                <?php if ($editStory): ?>
                    <a class="btn btn-secondary btn-small" href="historias.php#formulario">Cancelar</a>
                <?php endif; ?>
            </div>

            <form method="post" class="top-gap-sm">
                <input type="hidden" name="action" value="<?php echo h($formAction); ?>">
                <?php if ($editStory): ?>
                    <input type="hidden" name="id" value="<?php echo (int)$form['id']; ?>">
                <?php endif; ?>

                <div class="field">
                    <label for="titulo">Título</label>
                    <input id="titulo" name="titulo" type="text" value="<?php echo h((string)$form['titulo']); ?>" required>
                </div>

                <div class="field">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion" required><?php echo h((string)$form['descripcion']); ?></textarea>
                </div>

                <div class="field">
                    <label for="responsable">Responsable</label>
                    <input id="responsable" name="responsable" type="text" value="<?php echo h((string)$form['responsable']); ?>" required>
                </div>

                <div class="field">
                    <label for="sprint_id">Sprint</label>
                    <select id="sprint_id" name="sprint_id" required <?php echo empty($sprints) ? 'disabled' : ''; ?>>
                        <?php foreach ($sprints as $s): ?>
                            <option value="<?php echo (int)$s['id']; ?>" <?php echo ($form['sprint_id'] === (string)$s['id'] ? 'selected' : ''); ?>>
                                <?php echo h((string)$s['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($sprints)): ?>
                        <div class="subtle top-gap-xs">Primero crea un sprint en <a href="sprints.php">Sprints</a>.</div>
                    <?php endif; ?>
                </div>

                <div class="field">
                    <label for="estado">Estado</label>
                    <select id="estado" name="estado" required>
                        <?php foreach ($ESTADOS as $key => $meta): ?>
                            <option value="<?php echo h($key); ?>" <?php echo ($form['estado'] === $key ? 'selected' : ''); ?>>
                                <?php echo h((string)$meta['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="puntos">Puntos</label>
                    <input id="puntos" name="puntos" type="number" min="0" value="<?php echo h((string)$form['puntos']); ?>" required>
                </div>

                <div class="field">
                    <label for="fecha_creacion">Fecha de creación</label>
                    <input id="fecha_creacion" name="fecha_creacion" type="date" value="<?php echo h((string)$form['fecha_creacion']); ?>" required>
                </div>

                <div class="field">
                    <label for="fecha_finalizacion">Fecha de finalización (opcional)</label>
                    <input id="fecha_finalizacion" name="fecha_finalizacion" type="date" value="<?php echo h((string)$form['fecha_finalizacion']); ?>">
                </div>

                <button class="btn btn-primary" type="submit" <?php echo empty($sprints) ? 'disabled' : ''; ?>>
                    <?php echo h($formSubmitText); ?>
                </button>
            </form>
        </section>
    </aside>
</div>

<?php require __DIR__ . '/partials_footer.php'; ?>

