<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/modules/productos/index.php');
    exit;
}

$idProducto = isset($_POST['id_producto']) ? (int) $_POST['id_producto'] : 0;

$nombre = trim($_POST['nombre'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$presentacion = trim($_POST['presentacion'] ?? '');
$precioVenta = isset($_POST['precio_venta']) ? (float) $_POST['precio_venta'] : 0;
$margenGanancia = isset($_POST['margen_ganancia']) ? (float) $_POST['margen_ganancia'] : 0;
$stockMinimo = isset($_POST['stock_minimo']) ? (int) $_POST['stock_minimo'] : 0;
$requiereReceta = isset($_POST['requiere_receta']) ? (int) $_POST['requiere_receta'] : 0;
$usoTerapeutico = trim($_POST['uso_terapeutico'] ?? '');

if ($nombre === '') {
    $_SESSION['producto_error'] = 'El nombre del producto es obligatorio.';
    header('Location: ' . BASE_URL . '/modules/productos/form.php' . ($idProducto > 0 ? '?id=' . $idProducto : ''));
    exit;
}

if ($precioVenta < 0) {
    $_SESSION['producto_error'] = 'El precio de venta no puede ser negativo.';
    header('Location: ' . BASE_URL . '/modules/productos/form.php' . ($idProducto > 0 ? '?id=' . $idProducto : ''));
    exit;
}

if ($margenGanancia < 0) {
    $_SESSION['producto_error'] = 'El margen de ganancia no puede ser negativo.';
    header('Location: ' . BASE_URL . '/modules/productos/form.php' . ($idProducto > 0 ? '?id=' . $idProducto : ''));
    exit;
}

if ($stockMinimo < 0) {
    $_SESSION['producto_error'] = 'El stock mínimo no puede ser negativo.';
    header('Location: ' . BASE_URL . '/modules/productos/form.php' . ($idProducto > 0 ? '?id=' . $idProducto : ''));
    exit;
}

try {
    $pdo = getPDO();

    if ($idProducto > 0) {
        $sql = "
            UPDATE producto SET
                nombre = :nombre,
                descripcion = :descripcion,
                presentacion = :presentacion,
                precio_venta = :precio_venta,
                margen_ganancia = :margen_ganancia,
                stock_minimo = :stock_minimo,
                requiere_receta = :requiere_receta,
                uso_terapeutico = :uso_terapeutico
            WHERE id_producto = :id_producto
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'nombre' => $nombre,
            'descripcion' => $descripcion !== '' ? $descripcion : null,
            'presentacion' => $presentacion !== '' ? $presentacion : null,
            'precio_venta' => $precioVenta,
            'margen_ganancia' => $margenGanancia,
            'stock_minimo' => $stockMinimo,
            'requiere_receta' => $requiereReceta,
            'uso_terapeutico' => $usoTerapeutico !== '' ? $usoTerapeutico : null,
            'id_producto' => $idProducto,
        ]);

        $_SESSION['producto_success'] = 'Producto actualizado correctamente.';
    } else {
        $sql = "
            INSERT INTO producto (
                nombre,
                descripcion,
                presentacion,
                precio_venta,
                margen_ganancia,
                stock_minimo,
                requiere_receta,
                uso_terapeutico,
                activo
            ) VALUES (
                :nombre,
                :descripcion,
                :presentacion,
                :precio_venta,
                :margen_ganancia,
                :stock_minimo,
                :requiere_receta,
                :uso_terapeutico,
                1
            )
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'nombre' => $nombre,
            'descripcion' => $descripcion !== '' ? $descripcion : null,
            'presentacion' => $presentacion !== '' ? $presentacion : null,
            'precio_venta' => $precioVenta,
            'margen_ganancia' => $margenGanancia,
            'stock_minimo' => $stockMinimo,
            'requiere_receta' => $requiereReceta,
            'uso_terapeutico' => $usoTerapeutico !== '' ? $usoTerapeutico : null,
        ]);

        $_SESSION['producto_success'] = 'Producto registrado correctamente.';
    }

    header('Location: ' . BASE_URL . '/modules/productos/index.php');
    exit;

} catch (Throwable $e) {
    $_SESSION['producto_error'] = 'No se pudo guardar el producto.';
    header('Location: ' . BASE_URL . '/modules/productos/form.php' . ($idProducto > 0 ? '?id=' . $idProducto : ''));
    exit;
}