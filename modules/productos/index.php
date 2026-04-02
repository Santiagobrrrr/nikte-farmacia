<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';

$productos = [];
$error = '';
$success = $_SESSION['producto_success'] ?? '';
unset($_SESSION['producto_success']);

try {
    $pdo = getPDO();

    $sql = "SELECT
                p.id_producto,
                p.nombre,
                p.presentacion,
                p.precio_venta,
                p.stock_minimo,
                p.requiere_receta,
                COALESCE(SUM(l.cantidad_actual), 0) AS stock_actual
            FROM producto p
            LEFT JOIN lote l ON l.id_producto = p.id_producto
            GROUP BY
                p.id_producto,
                p.nombre,
                p.presentacion,
                p.precio_venta,
                p.stock_minimo,
                p.requiere_receta
            ORDER BY p.nombre ASC";

    $stmt = $pdo->query($sql);
    $productos = $stmt->fetchAll();
} catch (Throwable $e) {
    $error = 'No se pudieron cargar los productos.';
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

        <div class="col-12 col-md-9 col-lg-10">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h1 class="mb-0">Productos</h1>

                        <?php if (currentRole() === 'administradora'): ?>
                            <a href="<?= BASE_URL; ?>/modules/productos/form.php" class="btn btn-success">
                                Nuevo producto
                            </a>
                        <?php endif; ?>
                    </div>

                    <p class="text-muted">
                        Listado de productos con stock real calculado desde los lotes registrados.
                    </p>

                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success">
                            <?= htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($error); ?>
                        </div>
                    <?php elseif (empty($productos)): ?>
                        <div class="alert alert-warning">
                            No hay productos registrados todavía.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Presentación</th>
                                        <th>Precio</th>
                                        <th>Stock mínimo</th>
                                        <th>Stock actual</th>
                                        <th>Estado</th>
                                        <th>Receta</th>
                                        <?php if (currentRole() === 'administradora'): ?>
                                            <th width="180">Acciones</th>
                                        <?php endif; ?>
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
                                        } elseif ($stockActual <= $stockMinimo) {
                                            $estado = 'Stock bajo';
                                            $clase = 'warning';
                                        } else {
                                            $estado = 'En stock';
                                            $clase = 'success';
                                        }
                                        ?>
                                        <tr>
                                            <td><?= (int) $producto['id_producto']; ?></td>
                                            <td><?= htmlspecialchars($producto['nombre']); ?></td>
                                            <td><?= htmlspecialchars($producto['presentacion'] ?? ''); ?></td>
                                            <td>Q<?= number_format((float) $producto['precio_venta'], 2); ?></td>
                                            <td><?= $stockMinimo; ?></td>
                                            <td><?= $stockActual; ?></td>
                                            <td>
                                                <span class="badge text-bg-<?= $clase; ?>">
                                                    <?= $estado; ?>
                                                </span>
                                            </td>
                                            <td><?= (int) $producto['requiere_receta'] === 1 ? 'Sí' : 'No'; ?></td>

                                            <?php if (currentRole() === 'administradora'): ?>
                                                <td>
                                                    <a href="<?= BASE_URL; ?>/modules/productos/form.php?id=<?= (int) $producto['id_producto']; ?>" class="btn btn-sm btn-primary">
                                                        Editar
                                                    </a>
                                                </td>
                                            <?php endif; ?>
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
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>