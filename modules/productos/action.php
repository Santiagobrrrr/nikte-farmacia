<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/modules/productos/index.php');
    exit;
}

$nombre = trim($_POST['nombre'] ?? '');
$presentacion = trim($_POST['presentacion'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$precioVenta = trim($_POST['precio_venta'] ?? '');
$stockMinimo = trim($_POST['stock_minimo'] ?? '');
$requiereReceta = isset($_POST['requiere_receta']) ? (int) $_POST['requiere_receta'] : 0;
$usoTerapeutico = trim($_POST['uso_terapeutico'] ?? '');

if ($nombre === '' || $precioVenta === '' || $stockMinimo === '') {
    $_SESSION['producto_error'] = 'Debes completar los campos obligatorios.';
    header('Location: ' . BASE_URL . '/modules/productos/form.php');
    exit;
}

if (!is_numeric($precioVenta) || (float)$precioVenta < 0) {
    $_SESSION['producto_error'] = 'El precio de venta no es válido.';
    header('Location: ' . BASE_URL . '/modules/productos/form.php');
    exit;
}

if (!is_numeric($stockMinimo) || (int)$stockMinimo < 0) {
    $_SESSION['producto_error'] = 'El stock mínimo no es válido.';
    header('Location: ' . BASE_URL . '/modules/productos/form.php');
    exit;
}

try {
    $pdo = getPDO();

    $sql = "INSERT INTO producto (
                nombre,
                descripcion,
                presentacion,
                precio_venta,
                stock_minimo,
                requiere_receta,
                uso_terapeutico
            ) VALUES (
                :nombre,
                :descripcion,
                :presentacion,
                :precio_venta,
                :stock_minimo,
                :requiere_receta,
                :uso_terapeutico
            )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'nombre' => $nombre,
        'descripcion' => $descripcion !== '' ? $descripcion : null,
        'presentacion' => $presentacion !== '' ? $presentacion : null,
        'precio_venta' => (float)$precioVenta,
        'stock_minimo' => (int)$stockMinimo,
        'requiere_receta' => $requiereReceta === 1 ? 1 : 0,
        'uso_terapeutico' => $usoTerapeutico !== '' ? $usoTerapeutico : null,
    ]);

    $_SESSION['producto_success'] = 'Producto guardado correctamente.';
    header('Location: ' . BASE_URL . '/modules/productos/index.php');
    exit;

} catch (Throwable $e) {
    $_SESSION['producto_error'] = 'No se pudo guardar el producto.';
    header('Location: ' . BASE_URL . '/modules/productos/form.php');
    exit;
}