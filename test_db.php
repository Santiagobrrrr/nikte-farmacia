<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo = getPDO();
    echo 'Conexión exitosa a la base de datos.';
} catch (Throwable $e) {
    echo 'Error de conexión: ' . $e->getMessage();
}