<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';

$access_error = $_SESSION['access_error'] ?? '';
unset($_SESSION['access_error']);

$error = '';

$stats = [
    'productos_activos' => 0,
    'lotes_vigentes' => 0,
    'stock_bajo' => 0,
    'por_vencer' => 0,
];

$alertasStock = [];
$alertasVencimiento = [];

try {
    $pdo = getPDO();

    $fetchValue = function (string $sql) use ($pdo) {
        $value = $pdo->query($sql)->fetchColumn();
        return $value !== false ? $value : 0;
    };

    $stats['productos_activos'] = (int) $fetchValue("
        SELECT COUNT(*)
        FROM producto
        WHERE activo = 1
    ");

    $stats['lotes_vigentes'] = (int) $fetchValue("
        SELECT COUNT(*)
        FROM lote
        WHERE fecha_vencimiento >= CURDATE()
          AND cantidad_actual > 0
    ");

    $stats['stock_bajo'] = (int) $fetchValue("
        SELECT COUNT(*) FROM (
            SELECT p.id_producto
            FROM producto p
            LEFT JOIN lote l ON l.id_producto = p.id_producto
            WHERE p.activo = 1
            GROUP BY p.id_producto, p.stock_minimo
            HAVING COALESCE(SUM(
                CASE
                    WHEN l.fecha_vencimiento >= CURDATE() THEN l.cantidad_actual
                    ELSE 0
                END
            ), 0) <= p.stock_minimo
        ) AS t
    ");

    $stats['por_vencer'] = (int) $fetchValue("
        SELECT COUNT(*)
        FROM productos_por_vencer
    ");

    $sqlStock = "
        SELECT
            p.nombre,
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
        GROUP BY p.id_producto, p.nombre, p.stock_minimo
        HAVING COALESCE(SUM(
            CASE
                WHEN l.fecha_vencimiento >= CURDATE() THEN l.cantidad_actual
                ELSE 0
            END
        ), 0) <= p.stock_minimo
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
                                <h6 class="text-muted">Productos activos</h6>
                                <h2 class="mb-0"><?= $stats['productos_activos']; ?></h2>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card shadow-sm border-0">
                            <div class="card-body">
                                <h6 class="text-muted">Lotes vigentes</h6>
                                <h2 class="mb-0"><?= $stats['lotes_vigentes']; ?></h2>
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

                <div class="row g-3">
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

            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>