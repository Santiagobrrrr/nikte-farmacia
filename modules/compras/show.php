<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';

$idCompra = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$compra = null;
$detalles = [];
$error = '';

if ($idCompra > 0) {
    try {
        $pdo = getPDO();

        $stmt = $pdo->prepare("
            SELECT
                c.id_compra,
                c.fecha_compra,
                c.total_compra,
                p.nombre AS proveedor,
                u.nombre AS usuario
            FROM compra c
            INNER JOIN proveedor p ON p.id_proveedor = c.id_proveedor
            INNER JOIN usuario u ON u.id_usuario = c.id_usuario
            WHERE c.id_compra = :id_compra
            LIMIT 1
        ");

        $stmt->execute([
            'id_compra' => $idCompra
        ]);

        $compra = $stmt->fetch();

        if ($compra) {
            $stmtDetalles = $pdo->prepare("
                SELECT
                    dc.cantidad,
                    dc.costo_unitario,
                    dc.subtotal,
                    pr.nombre AS producto,
                    pr.presentacion,
                    l.codigo_lote,
                    l.fecha_vencimiento,
                    l.cantidad_actual
                FROM detallecompra dc
                INNER JOIN producto pr ON pr.id_producto = dc.id_producto
                INNER JOIN lote l ON l.id_lote = dc.id_lote
                WHERE dc.id_compra = :id_compra
                ORDER BY dc.id_detalle_compra ASC
            ");

            $stmtDetalles->execute([
                'id_compra' => $idCompra
            ]);

            $detalles = $stmtDetalles->fetchAll();
        }

    } catch (Throwable $e) {
        $error = 'No se pudo cargar el detalle de la compra.';
    }
}

if (!$compra && empty($error)) {
    $error = 'La compra seleccionada no existe.';
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

        <div class="col-12 col-md-9 col-lg-10">
            <div class="card shadow-sm">
                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h1 class="mb-1">
                                Detalle de compra #<?= (int) $idCompra; ?>
                            </h1>
                            <p class="text-muted mb-0">
                                Información de la compra registrada.
                            </p>
                        </div>

                        <a href="<?= BASE_URL; ?>/modules/compras/index.php" class="btn btn-secondary">
                            Volver
                        </a>
                    </div>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($error); ?>
                        </div>
                    <?php else: ?>

                        <div class="row g-3 mb-4">
                            <div class="col-12 col-md-3">
                                <div class="border rounded p-3 h-100">
                                    <small class="text-muted">Fecha</small>
                                    <div class="fw-bold">
                                        <?= htmlspecialchars($compra['fecha_compra']); ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-md-3">
                                <div class="border rounded p-3 h-100">
                                    <small class="text-muted">Proveedor</small>
                                    <div class="fw-bold">
                                        <?= htmlspecialchars($compra['proveedor']); ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-md-3">
                                <div class="border rounded p-3 h-100">
                                    <small class="text-muted">Usuario</small>
                                    <div class="fw-bold">
                                        <?= htmlspecialchars($compra['usuario']); ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-md-3">
                                <div class="border rounded p-3 h-100">
                                    <small class="text-muted">Total compra</small>
                                    <div class="fw-bold text-success">
                                        Q<?= number_format((float) $compra['total_compra'], 2); ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <h4 class="mb-3">Productos comprados</h4>

                        <?php if (empty($detalles)): ?>
                            <div class="alert alert-warning">
                                Esta compra no tiene productos registrados.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Producto</th>
                                            <th>Lote</th>
                                            <th>Vencimiento</th>
                                            <th class="text-end">Cantidad comprada</th>
                                            <th class="text-end">Existencia actual</th>
                                            <th class="text-end">Costo unitario</th>
                                            <th class="text-end">Subtotal</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php foreach ($detalles as $detalle): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($detalle['producto']); ?></strong>

                                                    <?php if (!empty($detalle['presentacion'])): ?>
                                                        <br>
                                                        <small class="text-muted">
                                                            <?= htmlspecialchars($detalle['presentacion']); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </td>

                                                <td>
                                                    <?= htmlspecialchars($detalle['codigo_lote']); ?>
                                                </td>

                                                <td>
                                                    <?= htmlspecialchars($detalle['fecha_vencimiento']); ?>
                                                </td>

                                                <td class="text-end">
                                                    <?= (int) $detalle['cantidad']; ?>
                                                </td>

                                                <td class="text-end">
                                                    <?= (int) $detalle['cantidad_actual']; ?>
                                                </td>

                                                <td class="text-end">
                                                    Q<?= number_format((float) $detalle['costo_unitario'], 2); ?>
                                                </td>

                                                <td class="text-end">
                                                    Q<?= number_format((float) $detalle['subtotal'], 2); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>

                                    <tfoot>
                                        <tr>
                                            <td colspan="6" class="text-end fw-bold">
                                                Total
                                            </td>
                                            <td class="text-end fw-bold">
                                                Q<?= number_format((float) $compra['total_compra'], 2); ?>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        <?php endif; ?>

                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>