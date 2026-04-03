<?php
$allowed_roles = ['administradora'];
require_once __DIR__ . '/../../includes/role_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';

$error = $_SESSION['lote_error'] ?? '';
unset($_SESSION['lote_error']);

$idProductoSeleccionado = isset($_GET['id_producto']) ? (int) $_GET['id_producto'] : 0;
$productos = [];

try {
    $pdo = getPDO();

    $sql = "SELECT id_producto, nombre
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
                        <h1 class="mb-0">Ingresar lote</h1>
                        <a href="<?= BASE_URL; ?>/modules/productos/index.php" class="btn btn-secondary">
                            Volver
                        </a>
                    </div>

                    <p class="text-muted">
                        Registra existencias nuevas para un producto ya existente.
                    </p>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form action="<?= BASE_URL; ?>/modules/lotes/action.php" method="POST">
                        <div class="row">
                            <div class="col-12 col-md-6 mb-3">
                                <label for="id_producto" class="form-label">Producto *</label>
                                <select id="id_producto" name="id_producto" class="form-select" required>
                                    <option value="">Seleccione un producto</option>
                                    <?php foreach ($productos as $producto): ?>
                                        <option
                                            value="<?= (int) $producto['id_producto']; ?>"
                                            <?= $idProductoSeleccionado === (int) $producto['id_producto'] ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($producto['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12 col-md-6 mb-3">
                                <label for="codigo_lote" class="form-label">Código de lote *</label>
                                <input type="text" id="codigo_lote" name="codigo_lote" class="form-control" required>
                            </div>

                            <div class="col-12 col-md-4 mb-3">
                                <label for="fecha_vencimiento" class="form-label">Fecha de vencimiento *</label>
                                <input type="date" id="fecha_vencimiento" name="fecha_vencimiento" class="form-control" required>
                            </div>

                            <div class="col-12 col-md-4 mb-3">
                                <label for="costo_unitario" class="form-label">Costo unitario *</label>
                                <input type="number" step="0.01" min="0" id="costo_unitario" name="costo_unitario" class="form-control" required>
                            </div>

                            <div class="col-12 col-md-4 mb-3">
                                <label for="cantidad_actual" class="form-label">Cantidad actual *</label>
                                <input type="number" min="1" id="cantidad_actual" name="cantidad_actual" class="form-control" required>
                            </div>

                            <div class="col-12 col-md-4 mb-3">
                                <label for="fecha_ingreso" class="form-label">Fecha de ingreso *</label>
                                <input type="date" id="fecha_ingreso" name="fecha_ingreso" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">Guardar lote</button>
                            <a href="<?= BASE_URL; ?>/modules/productos/index.php" class="btn btn-outline-secondary">Cancelar</a>
                        </div>
                    </form>

                    <div class="alert alert-info mt-4 mb-0">
                        Registrar un lote aumenta el stock real del producto, sin duplicar el catálogo.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>