<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo h($pageTitle ?? 'Gestor de Historias'); ?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <header>
        <div class="row row-between">
            <h1>Gestor de Historias de Usuario por Sprint</h1>
            <div class="subtle">Base: <strong>gestor_historias_db</strong></div>
        </div>
    </header>

    <nav class="nav nav-gap-bottom">
        <a class="btn btn-secondary" href="index.html">Inicio</a>
        <a class="btn btn-primary" href="historias.php">Historias</a>
        <a class="btn btn-secondary" href="sprints.php">Sprints</a>
        <a class="btn btn-secondary" href="reporte.php">Reporte</a>
    </nav>

    <?php if (!empty($flashSuccess)): ?>
        <div class="notice"><?php echo h($flashSuccess); ?></div>
    <?php endif; ?>
    <?php if (!empty($flashError)): ?>
        <div class="notice error"><?php echo h($flashError); ?></div>
    <?php endif; ?>

