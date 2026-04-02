<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';

$productos = [];
$error = '';

try {
    $pdo = getPDO();

    $sql = "SELECT id_producto, nombre, presentacion, precio_venta, stock_minimo, requiere_receta
            FROM producto
            ORDER BY nombre ASC";

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
                        <a href="#" class="btn btn-success">Nuevo producto</a>
                    </div>

                    <p class="text-muted">
                        Listado real de productos cargado desde la base de datos.
                    </p>

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
                                        <th>Precio de venta</th>
                                        <th>Stock mínimo</th>
                                        <th>Requiere receta</th>
                                        <th width="180">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($productos as $producto): ?>
                                        <tr>
                                            <td><?= (int) $producto['id_producto']; ?></td>
                                            <td><?= htmlspecialchars($producto['nombre']); ?></td>
                                            <td><?= htmlspecialchars($producto['presentacion'] ?? ''); ?></td>
                                            <td>Q<?= number_format((float) $producto['precio_venta'], 2); ?></td>
                                            <td><?= (int) $producto['stock_minimo']; ?></td>
                                            <td>
                                                <?= (int) $producto['requiere_receta'] === 1 ? 'Sí' : 'No'; ?>
                                            </td>
                                            <td>
                                                <a href="#" class="btn btn-sm btn-primary">Editar</a>
                                                <a href="#" class="btn btn-sm btn-danger">Eliminar</a>
                                            </td>
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