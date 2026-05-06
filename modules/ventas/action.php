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
    $nombreCliente = 'Consumidor final';
}

$metodosPermitidos = ['efectivo', 'tarjeta', 'transferencia'];
if (!in_array($metodoPago, $metodosPermitidos, true)) {
    $metodoPago = 'efectivo';
}

$_SESSION['venta_old'] = [
    'nombre_cliente' => $nombreCliente === 'Consumidor final' ? '' : $nombreCliente,
    'metodo_pago' => $metodoPago,
    'productos' => $productosPost,
];

if (!is_array($productosPost) || empty($productosPost)) {
    $_SESSION['venta_error'] = 'Debes agregar al menos un producto.';
    header('Location: ' . BASE_URL . '/modules/ventas/form.php');
    exit;
}

$productosAgrupados = [];

foreach ($productosPost as $item) {
    $idProducto = (int) ($item['id_producto'] ?? 0);
    $cantidad = (int) ($item['cantidad'] ?? 0);

    if ($idProducto <= 0 || $cantidad <= 0) {
        $_SESSION['venta_error'] = 'Uno de los productos enviados no es válido.';
        header('Location: ' . BASE_URL . '/modules/ventas/form.php');
        exit;
    }

    if (!isset($productosAgrupados[$idProducto])) {
        $productosAgrupados[$idProducto] = 0;
    }

    $productosAgrupados[$idProducto] += $cantidad;
}

try {
    $pdo = getPDO();
    $pdo->beginTransaction();

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

    $detallesFinales = [];
    $totalVenta = 0;

    foreach ($productosAgrupados as $idProducto => $cantidadSolicitada) {
        $stmtProducto = $pdo->prepare("
            SELECT id_producto, nombre, precio_venta, activo
            FROM producto
            WHERE id_producto = :id_producto
            LIMIT 1
        ");
        $stmtProducto->execute(['id_producto' => $idProducto]);
        $producto = $stmtProducto->fetch();

        if (!$producto || (int) $producto['activo'] !== 1) {
            throw new Exception('Uno de los productos no existe o está inactivo.');
        }

        $precioUnitario = (float) $producto['precio_venta'];

        $stmtStock = $pdo->prepare("
            SELECT COALESCE(SUM(cantidad_actual), 0) AS stock_disponible
            FROM lote
            WHERE id_producto = :id_producto
              AND cantidad_actual > 0
              AND fecha_vencimiento >= CURDATE()
        ");
        $stmtStock->execute(['id_producto' => $idProducto]);
        $stockDisponible = (int) $stmtStock->fetchColumn();

        if ($stockDisponible < $cantidadSolicitada) {
            throw new Exception('Stock insuficiente para: ' . $producto['nombre'] . '. Disponible: ' . $stockDisponible);
        }

        $stmtLotes = $pdo->prepare("
            SELECT id_lote, codigo_lote, cantidad_actual
            FROM lote
            WHERE id_producto = :id_producto
              AND cantidad_actual > 0
              AND fecha_vencimiento >= CURDATE()
            ORDER BY fecha_vencimiento ASC, id_lote ASC
        ");
        $stmtLotes->execute(['id_producto' => $idProducto]);
        $lotes = $stmtLotes->fetchAll();

        $cantidadPendiente = $cantidadSolicitada;

        foreach ($lotes as $lote) {
            if ($cantidadPendiente <= 0) {
                break;
            }

            $disponibleLote = (int) $lote['cantidad_actual'];
            $cantidadTomada = min($cantidadPendiente, $disponibleLote);

            if ($cantidadTomada <= 0) {
                continue;
            }

            $subtotal = $cantidadTomada * $precioUnitario;
            $totalVenta += $subtotal;

            $detallesFinales[] = [
                'id_producto' => $idProducto,
                'id_lote' => (int) $lote['id_lote'],
                'cantidad' => $cantidadTomada,
                'precio_unitario' => $precioUnitario,
                'subtotal' => $subtotal,
            ];

            $cantidadPendiente -= $cantidadTomada;
        }

        if ($cantidadPendiente > 0) {
            throw new Exception('No se pudo completar la salida del producto: ' . $producto['nombre']);
        }
    }

    $stmtVenta = $pdo->prepare("
        INSERT INTO venta (fecha_venta, id_usuario, id_cliente, metodo_pago, total_venta)
        VALUES (NOW(), :id_usuario, :id_cliente, :metodo_pago, :total_venta)
    ");
    $stmtVenta->execute([
        'id_usuario' => (int) $_SESSION['usuario_id'],
        'id_cliente' => $idCliente,
        'metodo_pago' => $metodoPago,
        'total_venta' => $totalVenta,
    ]);

    $idVenta = (int) $pdo->lastInsertId();

    foreach ($detallesFinales as $detalle) {
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

        $stmtStockUpdate = $pdo->prepare("
            UPDATE lote
            SET cantidad_actual = cantidad_actual - :cantidad
            WHERE id_lote = :id_lote
        ");
        $stmtStockUpdate->execute([
            'cantidad' => $detalle['cantidad'],
            'id_lote' => $detalle['id_lote'],
        ]);
    }

    $pdo->commit();

    unset($_SESSION['venta_old']);
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