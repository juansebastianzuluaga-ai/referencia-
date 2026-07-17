<?php

/**
 * Script para configurar un nuevo proyecto basado en la plantilla
 *
 * Uso: php scripts/setup-project.php "Nombre del Proyecto"
 */
if ($argc < 2) {
    echo "Error: Debes proporcionar el nombre del proyecto\n";
    echo "Uso: php scripts/setup-project.php \"Nombre del Proyecto\"\n";
    exit(1);
}

$projectName = $argv[1];
$projectSlug = strtolower(str_replace(' ', '-', $projectName));
$projectSnake = strtolower(str_replace(' ', '_', $projectName));

echo "=== Configurando Proyecto: $projectName ===\n\n";

// Archivos a modificar
$files = [
    '.env' => [
        'search' => ['APP_NAME=Laravel', 'VITE_APP_NAME="${APP_NAME}"'],
        'replace' => ["APP_NAME=\"$projectName\"", "VITE_APP_NAME=\"$projectName\""],
    ],
    '.env.example' => [
        'search' => ['APP_NAME=Laravel', 'VITE_APP_NAME="${APP_NAME}"'],
        'replace' => ["APP_NAME=\"$projectName\"", "VITE_APP_NAME=\"$projectName\""],
    ],
    'package.json' => [
        'search' => ['"name": "template-cacsb"'],
        'replace' => ["\"name\": \"$projectSlug\""],
    ],
    'composer.json' => [
        'search' => [
            '"name": "laravel/laravel"',
            '"description": "The skeleton application for the Laravel framework."',
        ],
        'replace' => [
            "\"name\": \"clinica/$projectSlug\"",
            "\"description\": \"$projectName - Sistema de gestión para Clínica Santa Bárbara\"",
        ],
    ],
];

$modified = 0;
$errors = 0;

foreach ($files as $filePath => $config) {
    if (! file_exists($filePath)) {
        echo "⚠️  Archivo no encontrado: $filePath\n";
        $errors++;

        continue;
    }

    $content = file_get_contents($filePath);
    $originalContent = $content;

    foreach ($config['search'] as $index => $search) {
        $replace = $config['replace'][$index];
        $content = str_replace($search, $replace, $content);
    }

    if ($content !== $originalContent) {
        file_put_contents($filePath, $content);
        echo "✅ Modificado: $filePath\n";
        $modified++;
    } else {
        echo "ℹ️  Sin cambios: $filePath\n";
    }
}

echo "\n=== Resumen ===\n";
echo "Archivos modificados: $modified\n";
echo "Errores: $errors\n";

if ($errors > 0) {
    echo "\n⚠️  Hubo errores durante el proceso. Revisa los mensajes arriba.\n";
    exit(1);
}

echo "\n✅ Configuración completada exitosamente.\n";
echo "\nSiguientes pasos:\n";
echo "1. Revisa los archivos modificados\n";
echo "2. Ejecuta: php artisan key:generate\n";
echo "3. Ejecuta: php artisan migrate:fresh --seed\n";
echo "4. Ejecuta: pnpm install\n";
echo "5. Ejecuta: pnpm run build\n";
echo "6. Inicia el servidor: php artisan serve\n";
