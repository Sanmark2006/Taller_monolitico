<?php
declare(strict_types=1);

require_once __DIR__ . '/common.php';

$pdo = db();
$pageTitle = 'Sprints';
$flashSuccess = '';
$flashError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nombre = trim((string)($_POST['nombre'] ?? ''));
        $fecha_inicio = (string)($_POST['fecha_inicio'] ?? '');
        $fecha_fin = (string)($_POST['fecha_fin'] ?? '');

        if ($nombre === '') {
            throw new RuntimeException('El nombre del sprint es obligatorio.');
        }
        if (!isValidDate($fecha_inicio) || !isValidDate($fecha_fin)) {
            throw new RuntimeException('Las fechas del sprint son inválidas.');
        }
        if ($fecha_inicio > $fecha_fin) {
            throw new RuntimeException('La fecha inicio no puede ser mayor que la fecha fin.');
        }

        $stmt = $pdo->prepare('INSERT INTO sprints (nombre, fecha_inicio, fecha_fin) VALUES (?, ?, ?)');
        $stmt->execute([$nombre, $fecha_inicio, $fecha_fin]);

        redirect('sprints.php?ok=1');
    } catch (Throwable $e) {
        $flashError = $e->getMessage();
    }
}

if (($_GET['ok'] ?? '') === '1') {
    $flashSuccess = 'Sprint creado correctamente.';
}

$sprints = $pdo->query('SELECT id, nombre, fecha_inicio, fecha_fin FROM sprints ORDER BY fecha_inicio DESC, id DESC')->fetchAll();

require __DIR__ . '/partials_header.php';
?>

<section class="card">
    <h2 class="title-sm">Crear sprint</h2>
    <form method="post" class="top-gap-sm">
        <div class="field">
            <label for="nombre">Nombre</label>
            <input id="nombre" name="nombre" type="text" required>
        </div>

        <div class="field">
            <label for="fecha_inicio">Fecha inicio</label>
            <input id="fecha_inicio" name="fecha_inicio" type="date" required>
        </div>

        <div class="field">
            <label for="fecha_fin">Fecha fin</label>
            <input id="fecha_fin" name="fecha_fin" type="date" required>
        </div>

        <button class="btn btn-primary" type="submit">Crear sprint</button>
    </form>
</section>

<section class="card section-gap">
    <h2 class="title-sm">Listado de sprints</h2>

    <div class="top-gap-sm">
        <table>
            <thead>
            <tr>
                <th>Nombre</th>
                <th>Fechas</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($sprints)): ?>
                <tr><td colspan="2" class="muted">No hay sprints registrados.</td></tr>
            <?php else: ?>
                <?php foreach ($sprints as $s): ?>
                    <tr>
                        <td class="strong"><?php echo h((string)$s['nombre']); ?></td>
                        <td class="muted"><?php echo h((string)$s['fecha_inicio']); ?> - <?php echo h((string)$s['fecha_fin']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/partials_footer.php'; ?>

