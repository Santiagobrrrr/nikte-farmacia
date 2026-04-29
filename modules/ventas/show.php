<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';

$idVenta = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$success = $_SESSION['venta_success'] ?? '';
unset($_SESSION['venta_success']);

$venta = null;
$detalles = [];

if ($idVenta > 0) {
    try {
        $pdo = getPDO();

        $stmt = $pdo->prepare("
            SELECT
                v.id_venta,
                v.fecha_venta,
                v.metodo_pago,
                v.total_venta,
                c.nombre AS nombre_cliente,
                u.nombre AS nombre_usuario
            FROM venta v
            LEFT JOIN cliente c ON c.id_cliente = v.id_cliente
            LEFT JOIN usuario u ON u.id_usuario = v.id_usuario
            WHERE v.id_venta = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $idVenta]);
        $venta = $stmt->fetch();

        if ($venta) {
            $stmtDet = $pdo->prepare("
                SELECT
                    dv.cantidad,
                    dv.precio_unitario,
                    dv.subtotal,
                    p.nombre AS nombre_producto,
                    p.presentacion,
                    l.codigo_lote
                FROM detalleventa dv
                INNER JOIN producto p ON p.id_producto = dv.id_producto
                LEFT JOIN lote l ON l.id_lote = dv.id_lote
                WHERE dv.id_venta = :id
                ORDER BY dv.id_detalle_venta ASC
            ");
            $stmtDet->execute(['id' => $idVenta]);
            $detalles = $stmtDet->fetchAll();
        }

    } catch (Throwable $e) {
        $venta = null;
    }
}

if (!$venta) {
    header('Location: ' . BASE_URL . '/modules/ventas/index.php');
    exit;
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

        <div class="col-12 col-md-9 col-lg-10">
            <div class="card shadow-sm border-0">
                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h1 class="mb-1">Detalle de venta #<?= $idVenta ?></h1>
                            <p class="text-muted mb-0">Resumen de la transacción registrada.</p>
                        </div>
                        <a href="<?= BASE_URL ?>/modules/ventas/index.php" class="btn btn-secondary">Volver</a>
                    </div>

                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>

                    <div class="row mb-4">
                        <div class="col-12 col-md-3">
                            <p class="mb-1 text-muted small">Fecha</p>
                            <p class="fw-semibold"><?= date('d/m/Y H:i', strtotime($venta['fecha_venta'])) ?></p>
                        </div>
                        <div class="col-12 col-md-3">
                            <p class="mb-1 text-muted small">Cliente</p>
                            <p class="fw-semibold"><?= htmlspecialchars($venta['nombre_cliente'] ?? 'Sin cliente') ?></p>
                        </div>
                        <div class="col-12 col-md-3">
                            <p class="mb-1 text-muted small">Vendedor</p>
                            <p class="fw-semibold"><?= htmlspecialchars($venta['nombre_usuario']) ?></p>
                        </div>
                        <div class="col-12 col-md-3">
                            <p class="mb-1 text-muted small">Método de pago</p>
                            <p class="fw-semibold"><?= ucfirst(htmlspecialchars($venta['metodo_pago'])) ?></p>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Producto</th>
                                    <th>Presentación</th>
                                    <th>Lote</th>
                                    <th>Cantidad</th>
                                    <th>Precio unitario</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($detalles as $d): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($d['nombre_producto']) ?></td>
                                        <td><?= htmlspecialchars($d['presentacion'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($d['codigo_lote'] ?? '-') ?></td>
                                        <td><?= (int) $d['cantidad'] ?></td>
                                        <td>Q <?= number_format((float) $d['precio_unitario'], 2) ?></td>
                                        <td>Q <?= number_format((float) $d['subtotal'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5" class="text-end fw-bold">Total:</td>
                                    <td class="fw-bold">Q <?= number_format((float) $venta['total_venta'], 2) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>