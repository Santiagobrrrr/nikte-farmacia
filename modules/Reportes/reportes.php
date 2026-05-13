<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';

$fechaActual = date('d/m/Y');
$error = '';

$stockBajo = [];
$porVencer = [];
$compras = [];
$ventas = [];

$totalCompras = 0;
$totalVentas = 0;

try {
    $pdo = getPDO();

    $stockBajo = $pdo->query("
        SELECT
            p.id_producto,
            p.nombre,
            p.presentacion,
            p.stock_minimo,
            COALESCE(SUM(
                CASE
                    WHEN l.fecha_vencimiento >= CURDATE() THEN l.cantidad_actual
                    ELSE 0
                END
            ), 0) AS stock_actual
        FROM producto p
        LEFT JOIN lote l ON l.id_producto = p.id_producto
        WHERE p.activo = 1
        GROUP BY p.id_producto, p.nombre, p.presentacion, p.stock_minimo
        HAVING stock_actual <= p.stock_minimo
        ORDER BY stock_actual ASC, p.nombre ASC
    ")->fetchAll();

    $porVencer = $pdo->query("
        SELECT
            p.nombre AS nombre_producto,
            p.presentacion,
            l.codigo_lote,
            l.fecha_ingreso,
            l.fecha_vencimiento,
            l.cantidad_actual,
            DATEDIFF(l.fecha_vencimiento, CURDATE()) AS dias_restantes
        FROM lote l
        INNER JOIN producto p ON p.id_producto = l.id_producto
        WHERE l.fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
          AND l.cantidad_actual > 0
        ORDER BY l.fecha_vencimiento ASC
    ")->fetchAll();

    $compras = $pdo->query("
        SELECT
            c.id_compra,
            c.fecha_compra,
            pr.nombre AS proveedor,
            u.nombre AS usuario,
            c.total_compra
        FROM compra c
        LEFT JOIN proveedor pr ON pr.id_proveedor = c.id_proveedor
        LEFT JOIN usuario u ON u.id_usuario = c.id_usuario
        ORDER BY c.fecha_compra DESC, c.id_compra DESC
    ")->fetchAll();

    $ventas = $pdo->query("
        SELECT
            v.id_venta,
            v.fecha_venta,
            v.metodo_pago,
            v.total_venta,
            c.nombre AS cliente,
            u.nombre AS usuario
        FROM venta v
        LEFT JOIN cliente c ON c.id_cliente = v.id_cliente
        LEFT JOIN usuario u ON u.id_usuario = v.id_usuario
        ORDER BY v.fecha_venta DESC, v.id_venta DESC
    ")->fetchAll();

    foreach ($compras as $compra) {
        $totalCompras += (float) $compra['total_compra'];
    }

    foreach ($ventas as $venta) {
        $totalVentas += (float) $venta['total_venta'];
    }

} catch (Throwable $e) {
    $error = 'No se pudieron cargar los reportes.';
}

require_once __DIR__ . '/../../includes/header.php';
?>

<style>
    .report-header-print {
        display: none;
        text-align: center;
        margin-bottom: 1rem;
    }

    @media print {
        body * {
            visibility: hidden;
        }

        .tab-pane.active,
        .tab-pane.active * {
            visibility: visible;
        }

        .tab-pane.active {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
        }

        .report-header-print {
            display: block !important;
        }

        .no-print,
        .no-print * {
            display: none !important;
        }

        .row > .d-none.d-md-block,
        .col-12.d-md-none {
            display: none !important;
        }

        .report-content {
            width: 100% !important;
            max-width: 100% !important;
            flex: 0 0 100% !important;
        }

        .card {
            border: none !important;
            box-shadow: none !important;
        }
    }
</style>

<div class="container-fluid py-4">
    <div class="row">
        <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

        <div class="col-12 col-md-9 col-lg-10 report-content">
            <div class="d-flex justify-content-between align-items-center mb-3 no-print">
                <div>
                    <h1 class="mb-1">Reportes</h1>
                    <p class="text-muted mb-0">
                        Consultas generales del sistema para inventario, compras y ventas.
                    </p>
                </div>

                <button type="button" onclick="window.print()" class="btn btn-success">
                    Imprimir reporte activo
                </button>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="row g-3 mb-3 no-print">
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <h6 class="text-muted mb-1">Stock bajo</h6>
                            <h3 class="mb-0"><?= count($stockBajo); ?></h3>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <h6 class="text-muted mb-1">Por vencer</h6>
                            <h3 class="mb-0"><?= count($porVencer); ?></h3>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <h6 class="text-muted mb-1">Total compras</h6>
                            <h3 class="mb-0">Q<?= number_format($totalCompras, 2); ?></h3>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <h6 class="text-muted mb-1">Total ventas</h6>
                            <h3 class="mb-0">Q<?= number_format($totalVentas, 2); ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <ul class="nav nav-tabs no-print" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active"
                                    data-bs-toggle="tab"
                                    data-bs-target="#stock"
                                    type="button"
                                    role="tab">
                                Stock bajo
                            </button>
                        </li>

                        <li class="nav-item" role="presentation">
                            <button class="nav-link"
                                    data-bs-toggle="tab"
                                    data-bs-target="#vencer"
                                    type="button"
                                    role="tab">
                                Por vencer
                            </button>
                        </li>

                        <li class="nav-item" role="presentation">
                            <button class="nav-link"
                                    data-bs-toggle="tab"
                                    data-bs-target="#compras"
                                    type="button"
                                    role="tab">
                                Compras
                            </button>
                        </li>

                        <li class="nav-item" role="presentation">
                            <button class="nav-link"
                                    data-bs-toggle="tab"
                                    data-bs-target="#ventas"
                                    type="button"
                                    role="tab">
                                Ventas
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content mt-4">
                        <div class="tab-pane fade show active" id="stock" role="tabpanel">
                            <div class="report-header-print">
                                <h3><?= htmlspecialchars(APP_NAME); ?></h3>
                                <p>Reporte de stock bajo</p>
                                <p>Fecha: <?= htmlspecialchars($fechaActual); ?></p>
                            </div>

                            <h4 class="mb-3">Reporte de stock bajo</h4>

                            <div class="table-responsive">
                                <table class="table table-bordered table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Producto</th>
                                            <th>Presentación</th>
                                            <th>Stock mínimo</th>
                                            <th>Stock actual</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($stockBajo)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">
                                                    No hay productos con stock bajo.
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($stockBajo as $item): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($item['nombre']); ?></td>
                                                    <td><?= htmlspecialchars($item['presentacion'] ?? ''); ?></td>
                                                    <td><?= (int) $item['stock_minimo']; ?></td>
                                                    <td><?= (int) $item['stock_actual']; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="vencer" role="tabpanel">
                            <div class="report-header-print">
                                <h3><?= htmlspecialchars(APP_NAME); ?></h3>
                                <p>Reporte de productos por vencer</p>
                                <p>Fecha: <?= htmlspecialchars($fechaActual); ?></p>
                            </div>

                            <h4 class="mb-3">Reporte de productos por vencer</h4>

                            <div class="table-responsive">
                                <table class="table table-bordered table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Producto</th>
                                            <th>Presentación</th>
                                            <th>Lote</th>
                                            <th>Vencimiento</th>
                                            <th>Días</th>
                                            <th>Cantidad</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($porVencer)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center text-muted">
                                                    No hay productos próximos a vencer.
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($porVencer as $item): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($item['nombre_producto']); ?></td>
                                                    <td><?= htmlspecialchars($item['presentacion'] ?? ''); ?></td>
                                                    <td><?= htmlspecialchars($item['codigo_lote']); ?></td>
                                                    <td><?= date('d/m/Y', strtotime($item['fecha_vencimiento'])); ?></td>
                                                    <td>
                                                        <?php if ((int) $item['dias_restantes'] < 0): ?>
                                                            <span class="badge bg-danger">
                                                                Vencido
                                                            </span>
                                                        <?php else: ?>
                                                            <?= (int) $item['dias_restantes']; ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= (int) $item['cantidad_actual']; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="compras" role="tabpanel">
                            <div class="report-header-print">
                                <h3><?= htmlspecialchars(APP_NAME); ?></h3>
                                <p>Reporte de compras</p>
                                <p>Fecha: <?= htmlspecialchars($fechaActual); ?></p>
                            </div>

                            <h4 class="mb-3">Reporte de compras</h4>

                            <div class="table-responsive">
                                <table class="table table-bordered table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Fecha</th>
                                            <th>Proveedor</th>
                                            <th>Usuario</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($compras)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted">
                                                    No hay compras registradas.
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($compras as $item): ?>
                                                <tr>
                                                    <td><?= (int) $item['id_compra']; ?></td>
                                                    <td><?= date('d/m/Y', strtotime($item['fecha_compra'])); ?></td>
                                                    <td><?= htmlspecialchars($item['proveedor'] ?? ''); ?></td>
                                                    <td><?= htmlspecialchars($item['usuario'] ?? ''); ?></td>
                                                    <td>Q<?= number_format((float) $item['total_compra'], 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="4" class="text-end fw-bold">Total</td>
                                            <td class="fw-bold">Q<?= number_format($totalCompras, 2); ?></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="ventas" role="tabpanel">
                            <div class="report-header-print">
                                <h3><?= htmlspecialchars(APP_NAME); ?></h3>
                                <p>Reporte de ventas</p>
                                <p>Fecha: <?= htmlspecialchars($fechaActual); ?></p>
                            </div>

                            <h4 class="mb-3">Reporte de ventas</h4>

                            <div class="table-responsive">
                                <table class="table table-bordered table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Fecha</th>
                                            <th>Cliente</th>
                                            <th>Usuario</th>
                                            <th>Método</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($ventas)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center text-muted">
                                                    No hay ventas registradas.
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($ventas as $item): ?>
                                                <tr>
                                                    <td><?= (int) $item['id_venta']; ?></td>
                                                    <td><?= date('d/m/Y H:i', strtotime($item['fecha_venta'])); ?></td>
                                                    <td><?= htmlspecialchars($item['cliente'] ?? 'Consumidor final'); ?></td>
                                                    <td><?= htmlspecialchars($item['usuario'] ?? ''); ?></td>
                                                    <td><?= ucfirst(htmlspecialchars($item['metodo_pago'] ?? '')); ?></td>
                                                    <td>Q<?= number_format((float) $item['total_venta'], 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="5" class="text-end fw-bold">Total</td>
                                            <td class="fw-bold">Q<?= number_format($totalVentas, 2); ?></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>