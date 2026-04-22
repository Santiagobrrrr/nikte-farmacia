<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/auth_check.php';
 
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/modules/ventas/index.php');
    exit;
}
 
$nombreCliente = trim($_POST['nombre_cliente'] ?? '');
$metodoPago = trim($_POST['metodo_pago'] ?? 'efectivo');
$productosPost = $_POST['productos'] ?? [];
 
if ($nombreCliente === '') {
    $_SESSION['venta_error'] = 'El nombre del cliente es obligatorio.';
    header('Location: ' . BASE_URL . '/modules/ventas/form.php');
    exit;
}
 
if (empty($productosPost)) {
    $_SESSION['venta_error'] = 'Debes agregar al menos un producto.';
    header('Location: ' . BASE_URL . '/modules/ventas/form.php');
    exit;
}
 
try {
    $pdo = getPDO();
    $pdo->beginTransaction();
 
    // Buscar cliente por nombre o crearlo
    $stmtCliente = $pdo->prepare("SELECT id_cliente FROM cliente WHERE nombre = :nombre LIMIT 1");
    $stmtCliente->execute(['nombre' => $nombreCliente]);
    $clienteExistente = $stmtCliente->fetch();
 
    if ($clienteExistente) {
        $idCliente = (int) $clienteExistente['id_cliente'];
    } else {
        $stmtNuevoCliente = $pdo->prepare("INSERT INTO cliente (nombre) VALUES (:nombre)");
        $stmtNuevoCliente->execute(['nombre' => $nombreCliente]);
        $idCliente = (int) $pdo->lastInsertId();
    }
 
    $totalVenta = 0;
    $detalles = [];
 
    foreach ($productosPost as $item) {
        $idProducto = (int) $item['id_producto'];
        $cantidad = (int) $item['cantidad'];
        $precioUnitario = (float) $item['precio_unitario'];
        $subtotal = (float) $item['subtotal'];
 
        if ($idProducto <= 0 || $cantidad <= 0 || $precioUnitario < 0) {
            throw new Exception('Datos de producto inválidos.');
        }
 
        // Buscar lote con stock disponible (FIFO)
        $stmtLote = $pdo->prepare("
            SELECT id_lote, cantidad_actual 
            FROM lote 
            WHERE id_producto = :id_producto AND cantidad_actual > 0
            ORDER BY fecha_vencimiento ASC
            LIMIT 1
        ");
        $stmtLote->execute(['id_producto' => $idProducto]);
        $lote = $stmtLote->fetch();
 
        if (!$lote) {
            $stmtNombre = $pdo->prepare("SELECT nombre FROM producto WHERE id_producto = :id");
            $stmtNombre->execute(['id' => $idProducto]);
            $prod = $stmtNombre->fetch();
            throw new Exception('Sin stock para: ' . ($prod['nombre'] ?? '#' . $idProducto));
        }
 
        if ($lote['cantidad_actual'] < $cantidad) {
            $stmtNombre = $pdo->prepare("SELECT nombre FROM producto WHERE id_producto = :id");
            $stmtNombre->execute(['id' => $idProducto]);
            $prod = $stmtNombre->fetch();
            throw new Exception('Stock insuficiente para: ' . ($prod['nombre'] ?? '#' . $idProducto) . '. Disponible: ' . $lote['cantidad_actual']);
        }
 
        $totalVenta += $subtotal;
        $detalles[] = [
            'id_producto' => $idProducto,
            'id_lote' => $lote['id_lote'],
            'cantidad' => $cantidad,
            'precio_unitario' => $precioUnitario,
            'subtotal' => $subtotal,
        ];
    }
 
    // Insertar venta
    $stmtVenta = $pdo->prepare("
        INSERT INTO venta (fecha_venta, id_usuario, id_cliente, metodo_pago, total_venta)
        VALUES (NOW(), :id_usuario, :id_cliente, :metodo_pago, :total_venta)
    ");
    $stmtVenta->execute([
        'id_usuario' => $_SESSION['usuario_id'],
        'id_cliente' => $idCliente,
        'metodo_pago' => $metodoPago,
        'total_venta' => $totalVenta,
    ]);
    $idVenta = (int) $pdo->lastInsertId();
 
    // Insertar detalles y descontar stock
    foreach ($detalles as $detalle) {
        $stmtDetalle = $pdo->prepare("
            INSERT INTO detalleventa (id_venta, id_producto, id_lote, cantidad, precio_unitario, subtotal)
            VALUES (:id_venta, :id_producto, :id_lote, :cantidad, :precio_unitario, :subtotal)
        ");
        $stmtDetalle->execute([
            'id_venta' => $idVenta,
            'id_producto' => $detalle['id_producto'],
            'id_lote' => $detalle['id_lote'],
            'cantidad' => $detalle['cantidad'],
            'precio_unitario' => $detalle['precio_unitario'],
            'subtotal' => $detalle['subtotal'],
        ]);
 
        $stmtStock = $pdo->prepare("
            UPDATE lote SET cantidad_actual = cantidad_actual - :cantidad
            WHERE id_lote = :id_lote
        ");
        $stmtStock->execute([
            'cantidad' => $detalle['cantidad'],
            'id_lote' => $detalle['id_lote'],
        ]);
    }
 
    $pdo->commit();
 
    $_SESSION['venta_success'] = 'Venta registrada correctamente.';
    header('Location: ' . BASE_URL . '/modules/ventas/show.php?id=' . $idVenta);
    exit;
 
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['venta_error'] = 'Error: ' . $e->getMessage();
    header('Location: ' . BASE_URL . '/modules/ventas/form.php');
    exit;
}
 