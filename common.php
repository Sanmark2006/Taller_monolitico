<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function isValidDate(?string $date): bool
{
    if ($date === null || $date === '') {
        return false;
    }
    return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $date);
}

function estadosMeta(): array
{
    return [
        'nueva' => ['label' => 'Nueva', 'class' => 'badge-nueva'],
        'activa' => ['label' => 'Activa', 'class' => 'badge-activa'],
        'finalizada' => ['label' => 'Finalizada', 'class' => 'badge-finalizada'],
        'impedimento' => ['label' => 'Impedimento', 'class' => 'badge-impedimento'],
    ];
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

