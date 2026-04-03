<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';

$access_error = $_SESSION['access_error'] ?? '';
unset($_SESSION['access_error']);

$error = '';

$stats = [
    'productos' => 0,
    'lotes' => 0,
    'usuarios' => 0,
    'clientes' => 0,
    'proveedores' => 0,
    'compras' => 0,
    'ventas' => 0,
    'recetas' => 0,
    'detalle_compras' => 0,
    'detalle_ventas' => 0,
    'stock_bajo' => 0,
    'por_vencer' => 0,
    'ventas_hoy' => 0,
    'monto_ventas_hoy' => 0,
    'compras_hoy' => 0,
];

$alertasStock = [];
$alertasVencimiento = [];

try {
    $pdo = getPDO();

    $fetchValue = function (string $sql) use ($pdo) {
        $value = $pdo->query($sql)->fetchColumn();
        return $value !== false ? $value : 0;
    };

    $stats['productos'] = (int) $fetchValue("SELECT COUNT(*) FROM producto");
    $stats['lotes'] = (int) $fetchValue("SELECT COUNT(*) FROM lote");
    $stats['usuarios'] = (int) $fetchValue("SELECT COUNT(*) FROM usuario");
    $stats['clientes'] = (int) $fetchValue("SELECT COUNT(*) FROM cliente");
    $stats['proveedores'] = (int) $fetchValue("SELECT COUNT(*) FROM proveedor");
    $stats['compras'] = (int) $fetchValue("SELECT COUNT(*) FROM compra");
    $stats['ventas'] = (int) $fetchValue("SELECT COUNT(*) FROM venta");
    $stats['recetas'] = (int) $fetchValue("SELECT COUNT(*) FROM receta");
    $stats['detalle_compras'] = (int) $fetchValue("SELECT COUNT(*) FROM detallecompra");
    $stats['detalle_ventas'] = (int) $fetchValue("SELECT COUNT(*) FROM detalleventa");

    $stats['stock_bajo'] = (int) $fetchValue("
        SELECT COUNT(*) FROM (
            SELECT p.id_producto
            FROM producto p
            LEFT JOIN lote l ON l.id_producto = p.id_producto
            GROUP BY p.id_producto, p.stock_minimo
            HAVING COALESCE(SUM(l.cantidad_actual), 0) <= p.stock_minimo
        ) AS t
    ");

    $stats['por_vencer'] = (int) $fetchValue("SELECT COUNT(*) FROM productos_por_vencer");

    $stats['ventas_hoy'] = (int) $fetchValue("
        SELECT COUNT(*)
        FROM venta
        WHERE DATE(fecha_venta) = CURDATE()
    ");

    $stats['monto_ventas_hoy'] = (float) $fetchValue("
        SELECT COALESCE(SUM(total_venta), 0)
        FROM venta
        WHERE DATE(fecha_venta) = CURDATE()
    ");

    $stats['compras_hoy'] = (int) $fetchValue("
        SELECT COUNT(*)
        FROM compra
        WHERE fecha_compra = CURDATE()
    ");

    $sqlStock = "
        SELECT
            p.nombre,
            p.stock_minimo,
            COALESCE(SUM(l.cantidad_actual), 0) AS stock_actual
        FROM producto p
        LEFT JOIN lote l ON l.id_producto = p.id_producto
        GROUP BY p.id_producto, p.nombre, p.stock_minimo
        HAVING COALESCE(SUM(l.cantidad_actual), 0) <= p.stock_minimo
        ORDER BY stock_actual ASC, p.nombre ASC
        LIMIT 5
    ";
    $alertasStock = $pdo->query($sqlStock)->fetchAll();

    $sqlVencimiento = "
        SELECT
            nombre_producto,
            codigo_lote,
            fecha_vencimiento,
            cantidad_actual,
            DATEDIFF(fecha_vencimiento, CURDATE()) AS dias_restantes
        FROM productos_por_vencer
        ORDER BY fecha_vencimiento ASC
        LIMIT 5
    ";
    $alertasVencimiento = $pdo->query($sqlVencimiento)->fetchAll();

} catch (Throwable $e) {
    $error = 'No se pudo cargar el dashboard.';
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

        <div class="col-12 col-md-9 col-lg-10">
            <?php if (!empty($access_error)): ?>
                <div class="alert alert-warning">
                    <?= htmlspecialchars($access_error); ?>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="mb-1">Dashboard</h1>
                    <p class="text-muted mb-0">
                        Resumen general del sistema y alertas rápidas.
                    </p>
                </div>
                <div class="text-end">
                    <div><strong>Usuario:</strong> <?= htmlspecialchars(currentUserName()); ?></div>
                    <div><strong>Rol:</strong> <?= htmlspecialchars(currentRole()); ?></div>
                </div>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error); ?>
                </div>
            <?php else: ?>

                <div class="row g-3 mb-4">
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card shadow-sm border-0">
                            <div class="card-body">
                                <h6 class="text-muted">Productos</h6>
                                <h2 class="mb-0"><?= $stats['productos']; ?></h2>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card shadow-sm border-0">
                            <div class="card-body">
                                <h6 class="text-muted">Lotes</h6>
                                <h2 class="mb-0"><?= $stats['lotes']; ?></h2>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card shadow-sm border-0">
                            <div class="card-body">
                                <h6 class="text-muted">Stock bajo</h6>
                                <h2 class="mb-0"><?= $stats['stock_bajo']; ?></h2>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card shadow-sm border-0">
                            <div class="card-body">
                                <h6 class="text-muted">Por vencer</h6>
                                <h2 class="mb-0"><?= $stats['por_vencer']; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h6 class="text-muted">Ventas de hoy</h6>
                                <h3 class="mb-1"><?= $stats['ventas_hoy']; ?></h3>
                                <small class="text-muted">Monto: Q<?= number_format((float) $stats['monto_ventas_hoy'], 2); ?></small>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h6 class="text-muted">Compras de hoy</h6>
                                <h3 class="mb-0"><?= $stats['compras_hoy']; ?></h3>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h6 class="text-muted">Usuarios</h6>
                                <h3 class="mb-0"><?= $stats['usuarios']; ?></h3>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h6 class="text-muted">Proveedores</h6>
                                <h3 class="mb-0"><?= $stats['proveedores']; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h6 class="text-muted">Clientes</h6>
                                <h3 class="mb-0"><?= $stats['clientes']; ?></h3>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h6 class="text-muted">Compras</h6>
                                <h3 class="mb-0"><?= $stats['compras']; ?></h3>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h6 class="text-muted">Ventas</h6>
                                <h3 class="mb-0"><?= $stats['ventas']; ?></h3>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h6 class="text-muted">Recetas</h6>
                                <h3 class="mb-0"><?= $stats['recetas']; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-body">
                                <h5 class="mb-3">Alertas de stock bajo</h5>

                                <?php if (empty($alertasStock)): ?>
                                    <div class="alert alert-success mb-0">
                                        No hay productos con stock bajo.
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm align-middle">
                                            <thead>
                                                <tr>
                                                    <th>Producto</th>
                                                    <th>Stock mínimo</th>
                                                    <th>Stock actual</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($alertasStock as $item): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($item['nombre']); ?></td>
                                                        <td><?= (int) $item['stock_minimo']; ?></td>
                                                        <td><?= (int) $item['stock_actual']; ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-body">
                                <h5 class="mb-3">Alertas de vencimiento</h5>

                                <?php if (empty($alertasVencimiento)): ?>
                                    <div class="alert alert-success mb-0">
                                        No hay lotes por vencer en los próximos 30 días.
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm align-middle">
                                            <thead>
                                                <tr>
                                                    <th>Producto</th>
                                                    <th>Lote</th>
                                                    <th>Vence</th>
                                                    <th>Días</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($alertasVencimiento as $item): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($item['nombre_producto']); ?></td>
                                                        <td><?= htmlspecialchars($item['codigo_lote']); ?></td>
                                                        <td><?= htmlspecialchars($item['fecha_vencimiento']); ?></td>
                                                        <td><?= (int) $item['dias_restantes']; ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (currentRole() === 'administradora'): ?>
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="mb-3">Resumen administrativo</h5>
                            <div class="row">
                                <div class="col-12 col-md-4 mb-2">
                                    <strong>Detalle de compras:</strong> <?= $stats['detalle_compras']; ?>
                                </div>
                                <div class="col-12 col-md-4 mb-2">
                                    <strong>Detalle de ventas:</strong> <?= $stats['detalle_ventas']; ?>
                                </div>
                                <div class="col-12 col-md-4 mb-2">
                                    <strong>Estado actual:</strong> Sistema operativo
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>