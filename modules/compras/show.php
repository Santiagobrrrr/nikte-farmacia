<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';

$idCompra = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($idCompra <= 0) {
    $_SESSION['access_error'] = 'Compra no válida.';
    header('Location: ' . BASE_URL . '/modules/compras/index.php');
    exit;
}

$compra = null;
$detalles = [];
$error = '';

try {
    $pdo = getPDO();

    $sqlCompra = "SELECT
                    c.id_compra,
                    c.fecha_compra,
                    c.total_compra,
                    p.nombre AS proveedor,
                    p.telefono AS proveedor_telefono,
                    p.direccion AS proveedor_direccion,
                    u.nombre AS usuario
                  FROM compra c
                  INNER JOIN proveedor p ON p.id_proveedor = c.id_proveedor
                  INNER JOIN usuario u ON u.id_usuario = c.id_usuario
                  WHERE c.id_compra = :id_compra
                  LIMIT 1";

    $stmtCompra = $pdo->prepare($sqlCompra);
    $stmtCompra->execute(['id_compra' => $idCompra]);
    $compra = $stmtCompra->fetch();

    if (!$compra) {
        $_SESSION['access_error'] = 'Compra no encontrada.';
        header('Location: ' . BASE_URL . '/modules/compras/index.php');
        exit;
    }

    $sqlDetalles = "SELECT
                        dc.id_detalle_compra,
                        pr.nombre AS producto,
                        pr.presentacion,
                        l.codigo_lote,
                        l.fecha_ingreso,
                        l.fecha_vencimiento,
                        dc.cantidad,
                        dc.costo_unitario,
                        dc.subtotal
                    FROM detallecompra dc
                    INNER JOIN producto pr ON pr.id_producto = dc.id_producto
                    INNER JOIN lote l ON l.id_lote = dc.id_lote
                    WHERE dc.id_compra = :id_compra
                    ORDER BY dc.id_detalle_compra ASC";

    $stmtDetalles = $pdo->prepare($sqlDetalles);
    $stmtDetalles->execute(['id_compra' => $idCompra]);
    $detalles = $stmtDetalles->fetchAll();

} catch (Throwable $e) {
    $error = 'No se pudo cargar el detalle de la compra.';
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

        <div class="col-12 col-md-9 col-lg-10">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error); ?>
                </div>
            <?php else: ?>
                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h1 class="mb-0">Detalle de compra</h1>
                            <a href="<?= BASE_URL; ?>/modules/compras/index.php" class="btn btn-secondary">
                                Volver a compras
                            </a>
                        </div>

                        <div class="row">
                            <div class="col-12 col-md-4 mb-2">
                                <strong>ID compra:</strong> <?= (int) $compra['id_compra']; ?>
                            </div>
                            <div class="col-12 col-md-4 mb-2">
                                <strong>Fecha:</strong> <?= htmlspecialchars($compra['fecha_compra']); ?>
                            </div>
                            <div class="col-12 col-md-4 mb-2">
                                <strong>Registrado por:</strong> <?= htmlspecialchars($compra['usuario']); ?>
                            </div>
                            <div class="col-12 col-md-4 mb-2">
                                <strong>Proveedor:</strong> <?= htmlspecialchars($compra['proveedor']); ?>
                            </div>
                            <div class="col-12 col-md-4 mb-2">
                                <strong>Teléfono:</strong> <?= htmlspecialchars($compra['proveedor_telefono'] ?? ''); ?>
                            </div>
                            <div class="col-12 col-md-4 mb-2">
                                <strong>Total compra:</strong> Q<?= number_format((float) $compra['total_compra'], 2); ?>
                            </div>
                            <div class="col-12 mb-2">
                                <strong>Dirección proveedor:</strong> <?= htmlspecialchars($compra['proveedor_direccion'] ?? ''); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <h2 class="h4 mb-3">Productos comprados</h2>

                        <?php if (empty($detalles)): ?>
                            <div class="alert alert-warning mb-0">
                                Esta compra no tiene detalles registrados.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Producto</th>
                                            <th>Presentación</th>
                                            <th>Lote</th>
                                            <th>Fecha ingreso</th>
                                            <th>Fecha vencimiento</th>
                                            <th>Cantidad</th>
                                            <th>Costo unitario</th>
                                            <th>Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($detalles as $detalle): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($detalle['producto']); ?></td>
                                                <td><?= htmlspecialchars($detalle['presentacion'] ?? ''); ?></td>
                                                <td><?= htmlspecialchars($detalle['codigo_lote']); ?></td>
                                                <td><?= htmlspecialchars($detalle['fecha_ingreso']); ?></td>
                                                <td><?= htmlspecialchars($detalle['fecha_vencimiento']); ?></td>
                                                <td><?= (int) $detalle['cantidad']; ?></td>
                                                <td>Q<?= number_format((float) $detalle['costo_unitario'], 2); ?></td>
                                                <td>Q<?= number_format((float) $detalle['subtotal'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>