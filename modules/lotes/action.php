<?php
$allowed_roles = ['administradora'];
require_once __DIR__ . '/../../includes/role_check.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/modules/lotes/form.php');
    exit;
}

$idProducto = isset($_POST['id_producto']) ? (int) $_POST['id_producto'] : 0;
$codigoLote = trim($_POST['codigo_lote'] ?? '');
$fechaVencimiento = trim($_POST['fecha_vencimiento'] ?? '');
$costoUnitario = trim($_POST['costo_unitario'] ?? '');
$cantidadActual = trim($_POST['cantidad_actual'] ?? '');
$fechaIngreso = trim($_POST['fecha_ingreso'] ?? '');

if ($idProducto <= 0 || $codigoLote === '' || $fechaVencimiento === '' || $costoUnitario === '' || $cantidadActual === '' || $fechaIngreso === '') {
    $_SESSION['lote_error'] = 'Debes completar todos los campos obligatorios.';
    header('Location: ' . BASE_URL . '/modules/lotes/form.php?id_producto=' . $idProducto);
    exit;
}

if (!is_numeric($costoUnitario) || (float) $costoUnitario < 0) {
    $_SESSION['lote_error'] = 'El costo unitario no es válido.';
    header('Location: ' . BASE_URL . '/modules/lotes/form.php?id_producto=' . $idProducto);
    exit;
}

if (!is_numeric($cantidadActual) || (int) $cantidadActual <= 0) {
    $_SESSION['lote_error'] = 'La cantidad actual debe ser mayor que cero.';
    header('Location: ' . BASE_URL . '/modules/lotes/form.php?id_producto=' . $idProducto);
    exit;
}

if ($fechaVencimiento < $fechaIngreso) {
    $_SESSION['lote_error'] = 'La fecha de vencimiento no puede ser anterior a la fecha de ingreso.';
    header('Location: ' . BASE_URL . '/modules/lotes/form.php?id_producto=' . $idProducto);
    exit;
}

try {
    $pdo = getPDO();

    $stmt = $pdo->prepare("SELECT id_producto FROM producto WHERE id_producto = :id_producto LIMIT 1");
    $stmt->execute(['id_producto' => $idProducto]);
    $producto = $stmt->fetch();

    if (!$producto) {
        $_SESSION['lote_error'] = 'El producto seleccionado no existe.';
        header('Location: ' . BASE_URL . '/modules/lotes/form.php');
        exit;
    }

    $sql = "INSERT INTO lote (
                codigo_lote,
                id_producto,
                fecha_vencimiento,
                costo_unitario,
                cantidad_actual,
                fecha_ingreso
            ) VALUES (
                :codigo_lote,
                :id_producto,
                :fecha_vencimiento,
                :costo_unitario,
                :cantidad_actual,
                :fecha_ingreso
            )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'codigo_lote' => $codigoLote,
        'id_producto' => $idProducto,
        'fecha_vencimiento' => $fechaVencimiento,
        'costo_unitario' => (float) $costoUnitario,
        'cantidad_actual' => (int) $cantidadActual,
        'fecha_ingreso' => $fechaIngreso,
    ]);

    $_SESSION['producto_success'] = 'Lote registrado correctamente.';
    header('Location: ' . BASE_URL . '/modules/productos/index.php');
    exit;

} catch (Throwable $e) {
    $_SESSION['lote_error'] = 'No se pudo registrar el lote. Verifica que el código de lote no esté repetido.';
    header('Location: ' . BASE_URL . '/modules/lotes/form.php?id_producto=' . $idProducto);
    exit;
}