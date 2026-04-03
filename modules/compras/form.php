<?php
$allowed_roles = ['administradora'];
require_once __DIR__ . '/../../includes/role_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';

$error = $_SESSION['compra_error'] ?? '';
unset($_SESSION['compra_error']);

$old = $_SESSION['compra_old'] ?? [];
unset($_SESSION['compra_old']);

$proveedores = [];
$productos = [];

try {
    $pdo = getPDO();

    $stmt = $pdo->query("SELECT id_proveedor, nombre FROM proveedor ORDER BY nombre ASC");
    $proveedores = $stmt->fetchAll();

    $stmt = $pdo->query("SELECT id_producto, nombre FROM producto ORDER BY nombre ASC");
    $productos = $stmt->fetchAll();
} catch (Throwable $e) {
    $error = 'No se pudieron cargar los datos del formulario.';
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

        <div class="col-12 col-md-9 col-lg-10">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h1 class="mb-0">Nueva compra</h1>
                        <a href="<?= BASE_URL; ?>/modules/compras/index.php" class="btn btn-secondary">
                            Volver
                        </a>
                    </div>

                    <p class="text-muted">
                        Registro básico de compra: proveedor + un producto + un lote.
                    </p>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form action="<?= BASE_URL; ?>/modules/compras/action.php" method="POST">
                        <div class="row">
                            <div class="col-12 col-md-6 mb-3">
                                <label for="fecha_compra" class="form-label">Fecha de compra *</label>
                                <input
                                    type="date"
                                    id="fecha_compra"
                                    name="fecha_compra"
                                    class="form-control"
                                    value="<?= htmlspecialchars($old['fecha_compra'] ?? date('Y-m-d')); ?>"
                                    required>
                            </div>

                            <div class="col-12 col-md-6 mb-3">
                                <label for="id_proveedor" class="form-label">Proveedor *</label>
                                <select id="id_proveedor" name="id_proveedor" class="form-select" required>
                                    <option value="">Seleccione un proveedor</option>
                                    <?php foreach ($proveedores as $proveedor): ?>
                                        <option
                                            value="<?= (int) $proveedor['id_proveedor']; ?>"
                                            <?= ((int) ($old['id_proveedor'] ?? 0) === (int) $proveedor['id_proveedor']) ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($proveedor['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <hr class="my-3">

                            <div class="col-12 col-md-6 mb-3">
                                <label for="id_producto" class="form-label">Producto *</label>
                                <select id="id_producto" name="id_producto" class="form-select" required>
                                    <option value="">Seleccione un producto</option>
                                    <?php foreach ($productos as $producto): ?>
                                        <option
                                            value="<?= (int) $producto['id_producto']; ?>"
                                            <?= ((int) ($old['id_producto'] ?? 0) === (int) $producto['id_producto']) ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($producto['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12 col-md-6 mb-3">
                                <label for="codigo_lote" class="form-label">Código de lote *</label>
                                <input
                                    type="text"
                                    id="codigo_lote"
                                    name="codigo_lote"
                                    class="form-control"
                                    value="<?= htmlspecialchars($old['codigo_lote'] ?? ''); ?>"
                                    required>
                            </div>

                            <div class="col-12 col-md-4 mb-3">
                                <label for="fecha_vencimiento" class="form-label">Fecha de vencimiento *</label>
                                <input
                                    type="date"
                                    id="fecha_vencimiento"
                                    name="fecha_vencimiento"
                                    class="form-control"
                                    value="<?= htmlspecialchars($old['fecha_vencimiento'] ?? ''); ?>"
                                    required>
                            </div>

                            <div class="col-12 col-md-4 mb-3">
                                <label for="costo_unitario" class="form-label">Costo unitario *</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    id="costo_unitario"
                                    name="costo_unitario"
                                    class="form-control"
                                    value="<?= htmlspecialchars($old['costo_unitario'] ?? ''); ?>"
                                    required>
                            </div>

                            <div class="col-12 col-md-4 mb-3">
                                <label for="cantidad" class="form-label">Cantidad *</label>
                                <input
                                    type="number"
                                    min="1"
                                    id="cantidad"
                                    name="cantidad"
                                    class="form-control"
                                    value="<?= htmlspecialchars($old['cantidad'] ?? ''); ?>"
                                    required>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">Guardar compra</button>
                            <a href="<?= BASE_URL; ?>/modules/compras/index.php" class="btn btn-outline-secondary">
                                Cancelar
                            </a>
                        </div>
                    </form>

                    <div class="alert alert-info mt-4 mb-0">
                        Esta es la base inicial del módulo. Más adelante puede ampliarse a compras con varios productos.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>