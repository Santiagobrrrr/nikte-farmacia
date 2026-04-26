<?php
$allowed_roles = ['administradora'];
require_once __DIR__ . '/../../includes/role_check.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/modules/compras/index.php');
    exit;
}

$fechaCompra = trim($_POST['fecha_compra'] ?? '');
$idProveedor = isset($_POST['id_proveedor']) ? (int) $_POST['id_proveedor'] : 0;
$idUsuario = (int) ($_SESSION['usuario_id'] ?? 0);
$items = $_POST['items'] ?? [];

$_SESSION['compra_old'] = [
    'fecha_compra' => $fechaCompra,
    'id_proveedor' => $idProveedor,
    'items' => $items,
];

if ($fechaCompra === '' || $idProveedor <= 0 || $idUsuario <= 0) {
    $_SESSION['compra_error'] = 'Debes completar la información principal de la compra.';
    header('Location: ' . BASE_URL . '/modules/compras/form.php');
    exit;
}

if (!is_array($items) || count($items) === 0) {
    $_SESSION['compra_error'] = 'Debes agregar al menos un producto a la compra.';
    header('Location: ' . BASE_URL . '/modules/compras/form.php');
    exit;
}

$itemsLimpios = [];

foreach ($items as $item) {
    $idProducto = isset($item['id_producto']) ? (int) $item['id_producto'] : 0;
    $codigoLote = trim($item['codigo_lote'] ?? '');
    $fechaVencimiento = trim($item['fecha_vencimiento'] ?? '');
    $costoUnitario = trim($item['costo_unitario'] ?? '');
    $cantidad = trim($item['cantidad'] ?? '');

    $estaVacio =
        $idProducto <= 0 &&
        $codigoLote === '' &&
        $fechaVencimiento === '' &&
        $costoUnitario === '' &&
        $cantidad === '';

    if ($estaVacio) {
        continue;
    }

    if (
        $idProducto <= 0 ||
        $codigoLote === '' ||
        $fechaVencimiento === '' ||
        $costoUnitario === '' ||
        $cantidad === ''
    ) {
        $_SESSION['compra_error'] = 'Todos los productos de la compra deben tener sus campos completos.';
        header('Location: ' . BASE_URL . '/modules/compras/form.php');
        exit;
    }

    if (!is_numeric($costoUnitario) || (float) $costoUnitario < 0) {
        $_SESSION['compra_error'] = 'Uno de los costos unitarios no es válido.';
        header('Location: ' . BASE_URL . '/modules/compras/form.php');
        exit;
    }

    if (!is_numeric($cantidad) || (int) $cantidad <= 0) {
        $_SESSION['compra_error'] = 'Una de las cantidades no es válida.';
        header('Location: ' . BASE_URL . '/modules/compras/form.php');
        exit;
    }

    if ($fechaVencimiento < $fechaCompra) {
        $_SESSION['compra_error'] = 'Una fecha de vencimiento no puede ser anterior a la fecha de compra.';
        header('Location: ' . BASE_URL . '/modules/compras/form.php');
        exit;
    }

    $itemsLimpios[] = [
        'id_producto' => $idProducto,
        'codigo_lote' => $codigoLote,
        'fecha_vencimiento' => $fechaVencimiento,
        'costo_unitario' => (float) $costoUnitario,
        'cantidad' => (int) $cantidad,
        'subtotal' => (float) $costoUnitario * (int) $cantidad,
    ];
}

if (count($itemsLimpios) === 0) {
    $_SESSION['compra_error'] = 'Debes agregar al menos un producto válido a la compra.';
    header('Location: ' . BASE_URL . '/modules/compras/form.php');
    exit;
}

try {
    $pdo = getPDO();
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT id_proveedor FROM proveedor WHERE id_proveedor = :id_proveedor LIMIT 1");
    $stmt->execute(['id_proveedor' => $idProveedor]);
    if (!$stmt->fetch()) {
        throw new Exception('Proveedor no válido.');
    }

    $totalCompra = 0;
    foreach ($itemsLimpios as $item) {
        $totalCompra += $item['subtotal'];
    }

    $sqlCompra = "INSERT INTO compra (
                    fecha_compra,
                    id_proveedor,
                    id_usuario,
                    total_compra
                 ) VALUES (
                    :fecha_compra,
                    :id_proveedor,
                    :id_usuario,
                    :total_compra
                 )";

    $stmt = $pdo->prepare($sqlCompra);
    $stmt->execute([
        'fecha_compra' => $fechaCompra,
        'id_proveedor' => $idProveedor,
        'id_usuario' => $idUsuario,
        'total_compra' => $totalCompra,
    ]);

    $idCompra = (int) $pdo->lastInsertId();

    foreach ($itemsLimpios as $item) {
        $stmt = $pdo->prepare("
            SELECT id_producto
            FROM producto
            WHERE id_producto = :id_producto
              AND activo = 1
            LIMIT 1
        ");
        $stmt->execute(['id_producto' => $item['id_producto']]);

        if (!$stmt->fetch()) {
            throw new Exception('Uno de los productos no es válido o está inactivo.');
        }

        $sqlLote = "INSERT INTO lote (
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

        $stmt = $pdo->prepare($sqlLote);
        $stmt->execute([
            'codigo_lote' => $item['codigo_lote'],
            'id_producto' => $item['id_producto'],
            'fecha_vencimiento' => $item['fecha_vencimiento'],
            'costo_unitario' => $item['costo_unitario'],
            'cantidad_actual' => $item['cantidad'],
            'fecha_ingreso' => $fechaCompra,
        ]);

        $idLote = (int) $pdo->lastInsertId();

        $sqlDetalle = "INSERT INTO detallecompra (
                        id_compra,
                        id_producto,
                        id_lote,
                        cantidad,
                        costo_unitario,
                        subtotal
                      ) VALUES (
                        :id_compra,
                        :id_producto,
                        :id_lote,
                        :cantidad,
                        :costo_unitario,
                        :subtotal
                      )";

        $stmt = $pdo->prepare($sqlDetalle);
        $stmt->execute([
            'id_compra' => $idCompra,
            'id_producto' => $item['id_producto'],
            'id_lote' => $idLote,
            'cantidad' => $item['cantidad'],
            'costo_unitario' => $item['costo_unitario'],
            'subtotal' => $item['subtotal'],
        ]);
    }

    $pdo->commit();

    unset($_SESSION['compra_old']);
    $_SESSION['compra_success'] = 'Compra registrada correctamente.';
    header('Location: ' . BASE_URL . '/modules/compras/index.php');
    exit;

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ((string) $e->getCode() === '23000') {
        $_SESSION['compra_error'] = 'Uno de los códigos de lote ya existe. Debes ingresar códigos diferentes.';
    } else {
        $_SESSION['compra_error'] = 'No se pudo registrar la compra.';
    }

    header('Location: ' . BASE_URL . '/modules/compras/form.php');
    exit;
}