<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';

$productos = [];
$error = '';

try {
    $pdo = getPDO();

    $sql = "SELECT
                id_producto,
                nombre_producto,
                id_lote,
                codigo_lote,
                fecha_vencimiento,
                cantidad_actual,
                DATEDIFF(fecha_vencimiento, CURDATE()) AS dias_restantes
            FROM productos_por_vencer
            ORDER BY fecha_vencimiento ASC";

    $stmt = $pdo->query($sql);
    $productos = $stmt->fetchAll();
} catch (Throwable $e) {
    $error = 'No se pudieron cargar los productos por vencer. Verifica que exista la vista productos_por_vencer.';
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

        <div class="col-12 col-md-9 col-lg-10">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h1 class="mb-0">Inventario por vencer</h1>
                        <a href="<?= BASE_URL; ?>/modules/productos/index.php" class="btn btn-outline-secondary">
                            Volver a Inventario
                        </a>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <a href="<?= BASE_URL; ?>/modules/productos/index.php" class="btn btn-outline-primary btn-sm">
                            Ver Inventario
                        </a>

                        <a href="<?= BASE_URL; ?>/modules/productos/stock_bajo.php" class="btn btn-outline-warning btn-sm">
                            Stock bajo
                        </a>

                        <a href="<?= BASE_URL; ?>/modules/productos/por_vencer.php" class="btn btn-outline-danger btn-sm">
                            Inventario por vencer
                        </a>

                    </div>
                    <p class="text-muted">
                        Alertas de lotes vencidos o próximos a vencer en los próximos 30 días.
                    </p>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($error); ?>
                        </div>
                    <?php elseif (empty($productos)): ?>
                        <div class="alert alert-success">
                            No hay productos por vencer en los próximos 30 días.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Producto</th>
                                        <th>Lote</th>
                                        <th>Fecha de vencimiento</th>
                                        <th>Cantidad actual</th>
                                        <th>Días restantes</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($productos as $producto): ?>
                                        <?php
                                        $dias = (int) $producto['dias_restantes'];

                                        if ($dias < 0) {
                                            $estado = 'Vencido';
                                            $clase = 'danger';
                                        } elseif ($dias <= 7) {
                                            $estado = 'Por vencer pronto';
                                            $clase = 'warning';
                                        } else {
                                            $estado = 'Por vencer';
                                            $clase = 'info';
                                        }
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars($producto['nombre_producto']); ?></td>
                                            <td><?= htmlspecialchars($producto['codigo_lote']); ?></td>
                                            <td><?= htmlspecialchars($producto['fecha_vencimiento']); ?></td>
                                            <td><?= (int) $producto['cantidad_actual']; ?></td>
                                            <td><?= $dias; ?></td>
                                            <td>
                                                <span class="badge text-bg-<?= $clase; ?>">
                                                    <?= $estado; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            <div class="alert alert-warning mb-0">
                                Revisa productos con estado <strong>Vencido</strong> o <strong>Por vencer pronto</strong> para evitar pérdidas
                                y garantizar la seguridad de los clientes.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>