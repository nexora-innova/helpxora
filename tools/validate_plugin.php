<?php

/**
 * HelpXora - Script de Validación del Plugin
 *
 * Valida que el plugin cumpla con los requisitos básicos para el directorio de plugins de GLPI.
 *
 * Uso: php tools/validate_plugin.php
 */

$plugin_dir = dirname(__FILE__) . '/../';
$errors = [];
$warnings = [];

echo "=== Validación del Plugin HelpXora ===\n\n";

$required_files = [
    'setup.php',
    'hook.php',
    'plugin.xml',
    'README.md',
    'LICENSE',
];

foreach ($required_files as $file) {
    if (!file_exists($plugin_dir . $file)) {
        $errors[] = "Archivo requerido faltante: $file";
    } else {
        echo "✓ $file existe\n";
    }
}

$required_dirs = [
    'front',
    'ajax',
    'inc',
    'install',
    'locales',
];

foreach ($required_dirs as $dir) {
    if (!is_dir($plugin_dir . $dir)) {
        $errors[] = "Directorio requerido faltante: $dir";
    } else {
        echo "✓ Directorio $dir existe\n";
    }
}

$logo_found = file_exists($plugin_dir . 'pics/logo.png')
    || file_exists($plugin_dir . 'pics/helpxora.png')
    || file_exists($plugin_dir . 'pics/helpxora.jpg')
    || file_exists($plugin_dir . 'pics/helpxora.jpeg')
    || file_exists($plugin_dir . 'pics/helpxora.svg');
if (!$logo_found) {
    $warnings[] = "No se encontró imagen del plugin en pics/ (logo.png, helpxora.png, .jpg, .jpeg o .svg). Añadir logo para el directorio de plugins.";
}

$locales_mo = glob($plugin_dir . 'locales/*.mo');
if (empty($locales_mo)) {
    $warnings[] = "No se encontraron archivos .mo en locales/. Ejecutar: php tools/po2mo.php";
}

if (file_exists($plugin_dir . 'plugin.xml')) {
    $xml = @simplexml_load_file($plugin_dir . 'plugin.xml');
    if ($xml) {
        $homepage = (string)$xml->homepage;
        if (strpos($homepage, 'example') !== false || strpos($homepage, 'TU_USUARIO') !== false) {
            $warnings[] = "URLs en plugin.xml contienen placeholders (example/TU_USUARIO). Actualizar con la URL real del repositorio.";
        }
    }
}

echo "\n";

if (!empty($errors)) {
    echo "ERRORES ENCONTRADOS:\n";
    foreach ($errors as $error) {
        echo "  ✗ $error\n";
    }
    echo "\n";
}

if (!empty($warnings)) {
    echo "ADVERTENCIAS:\n";
    foreach ($warnings as $warning) {
        echo "  ⚠ $warning\n";
    }
    echo "\n";
}

if (empty($errors) && empty($warnings)) {
    echo "✓ Validación completada sin errores ni advertencias.\n";
    exit(0);
} elseif (empty($errors)) {
    echo "✓ Validación completada con advertencias menores.\n";
    exit(0);
} else {
    echo "✗ Validación falló. Corrige los errores antes de publicar.\n";
    exit(1);
}
