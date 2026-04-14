<?php
declare(strict_types=1);

require_once __DIR__ . '/common.php';

$pdo = db();
$pageTitle = 'Reporte';
$flashSuccess = '';
$flashError = '';
$ESTADOS = estadosMeta();

$sprints = $pdo->query('SELECT id, nombre, fecha_inicio, fecha_fin FROM sprints ORDER BY fecha_inicio DESC, id DESC')->fetchAll();

$reportSprint = (string)($_GET['sprint_id'] ?? 'all');
$where = '';
$params = [];
if ($reportSprint !== 'all' && ctype_digit($reportSprint)) {
    $where = 'WHERE h.sprint_id = ?';
    $params[] = (int)$reportSprint;
}

$countsEstado = array_fill_keys(array_keys($ESTADOS), 0);
$stmt = $pdo->prepare('SELECT estado, COUNT(*) AS c FROM historias h ' . $where . ' GROUP BY estado');
$stmt->execute($params);
foreach ($stmt->fetchAll() as $row) {
    $estado = (string)$row['estado'];
    if (isset($countsEstado[$estado])) {
        $countsEstado[$estado] = (int)$row['c'];
    }
}

$pendientes = (int)$countsEstado['nueva'] + (int)$countsEstado['activa'];
$finalizadas = (int)$countsEstado['finalizada'];
$impedimentos = (int)$countsEstado['impedimento'];

$stmt = $pdo->prepare(
    'SELECT h.responsable, h.estado, COUNT(*) AS c
     FROM historias h
     ' . $where . '
     GROUP BY h.responsable, h.estado
     ORDER BY h.responsable ASC'
);
$stmt->execute($params);
$rowsResp = $stmt->fetchAll();

$respMatrix = [];
foreach ($rowsResp as $row) {
    $resp = (string)$row['responsable'];
    $estado = (string)$row['estado'];
    if (!isset($respMatrix[$resp])) {
        $respMatrix[$resp] = array_fill_keys(array_keys($ESTADOS), 0);
    }
    if (isset($respMatrix[$resp][$estado])) {
        $respMatrix[$resp][$estado] = (int)$row['c'];
    }
}

$orderResponsables = array_keys($respMatrix);
sort($orderResponsables, SORT_NATURAL | SORT_FLAG_CASE);

require __DIR__ . '/partials_header.php';
?>

<section class="card">
    <div class="row row-between">
        <h2 class="title-sm">Reporte</h2>
        <form method="get" class="inline-form">
            <div class="report-select-wrap">
                <label for="sprint_id">Sprint</label>
                <select id="sprint_id" name="sprint_id" onchange="this.form.submit()">
                    <option value="all" <?php echo ($reportSprint === 'all' ? 'selected' : ''); ?>>Todos</option>
                    <?php foreach ($sprints as $s): ?>
                        <option value="<?php echo (int)$s['id']; ?>" <?php echo ($reportSprint === (string)$s['id'] ? 'selected' : ''); ?>>
                            <?php echo h((string)$s['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>

    <div class="top-gap-sm">
        <table>
            <thead>
            <tr>
                <th>Categoría</th>
                <th>Cantidad</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>Pendientes (Nueva + Activa)</td>
                <td><strong><?php echo $pendientes; ?></strong></td>
            </tr>
            <tr>
                <td>Finalizadas</td>
                <td><strong><?php echo $finalizadas; ?></strong></td>
            </tr>
            <tr>
                <td>Con impedimentos</td>
                <td><strong><?php echo $impedimentos; ?></strong></td>
            </tr>
            </tbody>
        </table>
    </div>

    <div class="top-gap-sm">
        <table>
            <thead>
            <tr>
                <th>Responsable</th>
                <th>Pendientes (Nueva + Activa)</th>
                <th>Finalizada</th>
                <th>Impedimento</th>
                <th>Total</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($respMatrix)): ?>
                <tr><td colspan="5" class="muted">No hay datos de historias para el reporte.</td></tr>
            <?php else: ?>
                <?php foreach ($orderResponsables as $resp): ?>
                    <?php
                        $m = $respMatrix[$resp];
                        $pend = (int)$m['nueva'] + (int)$m['activa'];
                        $total = $pend + (int)$m['finalizada'] + (int)$m['impedimento'];
                    ?>
                    <tr>
                        <td><?php echo h($resp); ?></td>
                        <td><?php echo $pend; ?></td>
                        <td><?php echo (int)$m['finalizada']; ?></td>
                        <td><?php echo (int)$m['impedimento']; ?></td>
                        <td><strong><?php echo $total; ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/partials_footer.php'; ?>

