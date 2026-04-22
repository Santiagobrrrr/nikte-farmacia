<?php
$allowed_roles = ['administradora'];
require_once __DIR__ . '/../../includes/role_check.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/modules/productos/index.php');
    exit;
}

$idProducto = isset($_POST['id_producto']) ? (int) $_POST['id_producto'] : 0;
$nombre = trim($_POST['nombre'] ?? '');
$presentacion = trim($_POST['presentacion'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$precioVenta = trim($_POST['precio_venta'] ?? '');
$stockMinimo = trim($_POST['stock_minimo'] ?? '');
$requiereReceta = isset($_POST['requiere_receta']) ? (int) $_POST['requiere_receta'] : 0;
$usoTerapeutico = trim($_POST['uso_terapeutico'] ?? '');
$activo = isset($_POST['activo']) ? (int) $_POST['activo'] : 1;

if ($nombre === '' || $precioVenta === '' || $stockMinimo === '') {
    $_SESSION['producto_error'] = 'Debes completar los campos obligatorios.';
    $redirect = $idProducto > 0
        ? BASE_URL . '/modules/productos/form.php?id=' . $idProducto
        : BASE_URL . '/modules/productos/form.php';
    header('Location: ' . $redirect);
    exit;
}

if (!is_numeric($precioVenta) || (float)$precioVenta < 0) {
    $_SESSION['producto_error'] = 'El precio de venta no es válido.';
    $redirect = $idProducto > 0
        ? BASE_URL . '/modules/productos/form.php?id=' . $idProducto
        : BASE_URL . '/modules/productos/form.php';
    header('Location: ' . $redirect);
    exit;
}

if (!is_numeric($stockMinimo) || (int)$stockMinimo < 0) {
    $_SESSION['producto_error'] = 'El stock mínimo no es válido.';
    $redirect = $idProducto > 0
        ? BASE_URL . '/modules/productos/form.php?id=' . $idProducto
        : BASE_URL . '/modules/productos/form.php';
    header('Location: ' . $redirect);
    exit;
}

$activo = $activo === 0 ? 0 : 1;

try {
    $pdo = getPDO();

    if ($idProducto > 0) {
        $sql = "UPDATE producto
                SET nombre = :nombre,
                    descripcion = :descripcion,
                    presentacion = :presentacion,
                    precio_venta = :precio_venta,
                    stock_minimo = :stock_minimo,
                    requiere_receta = :requiere_receta,
                    uso_terapeutico = :uso_terapeutico,
                    activo = :activo
                WHERE id_producto = :id_producto";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'nombre' => $nombre,
            'descripcion' => $descripcion !== '' ? $descripcion : null,
            'presentacion' => $presentacion !== '' ? $presentacion : null,
            'precio_venta' => (float) $precioVenta,
            'stock_minimo' => (int) $stockMinimo,
            'requiere_receta' => $requiereReceta === 1 ? 1 : 0,
            'uso_terapeutico' => $usoTerapeutico !== '' ? $usoTerapeutico : null,
            'activo' => $activo,
            'id_producto' => $idProducto,
        ]);

        $_SESSION['producto_success'] = 'Producto actualizado correctamente.';
    } else {
        $sql = "INSERT INTO producto (
                    nombre,
                    descripcion,
                    presentacion,
                    precio_venta,
                    stock_minimo,
                    requiere_receta,
                    uso_terapeutico,
                    activo
                ) VALUES (
                    :nombre,
                    :descripcion,
                    :presentacion,
                    :precio_venta,
                    :stock_minimo,
                    :requiere_receta,
                    :uso_terapeutico,
                    :activo
                )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'nombre' => $nombre,
            'descripcion' => $descripcion !== '' ? $descripcion : null,
            'presentacion' => $presentacion !== '' ? $presentacion : null,
            'precio_venta' => (float) $precioVenta,
            'stock_minimo' => (int) $stockMinimo,
            'requiere_receta' => $requiereReceta === 1 ? 1 : 0,
            'uso_terapeutico' => $usoTerapeutico !== '' ? $usoTerapeutico : null,
            'activo' => $activo,
        ]);

        $_SESSION['producto_success'] = 'Producto guardado correctamente.';
    }

    header('Location: ' . BASE_URL . '/modules/productos/index.php');
    exit;

} catch (Throwable $e) {
    $_SESSION['producto_error'] = 'No se pudo guardar la información del producto.';
    $redirect = $idProducto > 0
        ? BASE_URL . '/modules/productos/form.php?id=' . $idProducto
        : BASE_URL . '/modules/productos/form.php';
    header('Location: ' . $redirect);
    exit;
}   