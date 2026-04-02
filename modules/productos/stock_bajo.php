<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';

$productos = [];
$error = '';

try {
    $pdo = getPDO();

    $sql = "SELECT
                p.id_producto,
                p.nombre,
                p.presentacion,
                p.stock_minimo,
                COALESCE(SUM(l.cantidad_actual), 0) AS stock_actual
            FROM producto p
            LEFT JOIN lote l ON l.id_producto = p.id_producto
            GROUP BY
                p.id_producto,
                p.nombre,
                p.presentacion,
                p.stock_minimo
            HAVING COALESCE(SUM(l.cantidad_actual), 0) <= p.stock_minimo
            ORDER BY stock_actual ASC, p.nombre ASC";

    $stmt = $pdo->query($sql);
    $productos = $stmt->fetchAll();
} catch (Throwable $e) {
    $error = 'No se pudieron cargar los productos con stock bajo.';
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

        <div class="col-12 col-md-9 col-lg-10">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h1 class="mb-0">Productos con stock bajo</h1>
                        <a href="<?= BASE_URL; ?>/modules/productos/index.php" class="btn btn-outline-secondary">
                            Volver a productos
                        </a>
                    </div>

                    <p class="text-muted">
                        Alerta de productos cuyo stock actual es menor o igual al stock mínimo definido.
                    </p>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($error); ?>
                        </div>
                    <?php elseif (empty($productos)): ?>
                        <div class="alert alert-success">
                            No hay productos con stock bajo en este momento.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Producto</th>
                                        <th>Presentación</th>
                                        <th>Stock mínimo</th>
                                        <th>Stock actual</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($productos as $producto): ?>
                                        <?php
                                        $stockActual = (int) $producto['stock_actual'];
                                        $stockMinimo = (int) $producto['stock_minimo'];

                                        if ($stockActual <= 0) {
                                            $estado = 'Sin stock';
                                            $clase = 'danger';
                                        } else {
                                            $estado = 'Stock bajo';
                                            $clase = 'warning';
                                        }
                                        ?>
                                        <tr>
                                            <td><?= (int) $producto['id_producto']; ?></td>
                                            <td><?= htmlspecialchars($producto['nombre']); ?></td>
                                            <td><?= htmlspecialchars($producto['presentacion'] ?? ''); ?></td>
                                            <td><?= $stockMinimo; ?></td>
                                            <td><?= $stockActual; ?></td>
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

                        <div class="alert alert-warning mt-3 mb-0">
                            Estos productos necesitan reposición o revisión de inventario.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>