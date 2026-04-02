<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';

$error = $_SESSION['producto_error'] ?? '';
unset($_SESSION['producto_error']);
?>

<div class="container-fluid py-4">
    <div class="row">
        <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

        <div class="col-12 col-md-9 col-lg-10">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h1 class="mb-0">Nuevo producto</h1>
                        <a href="<?= BASE_URL; ?>/modules/productos/index.php" class="btn btn-secondary">
                            Volver
                        </a>
                    </div>

                    <p class="text-muted">
                        Formulario para registrar un producto.
                    </p>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form action="<?= BASE_URL; ?>/modules/productos/action.php" method="POST">
                        <div class="row">
                            <div class="col-12 col-md-6 mb-3">
                                <label for="nombre" class="form-label">Nombre *</label>
                                <input type="text" id="nombre" name="nombre" class="form-control" required>
                            </div>

                            <div class="col-12 col-md-6 mb-3">
                                <label for="presentacion" class="form-label">Presentación</label>
                                <input type="text" id="presentacion" name="presentacion" class="form-control">
                            </div>

                            <div class="col-12 mb-3">
                                <label for="descripcion" class="form-label">Descripción</label>
                                <textarea id="descripcion" name="descripcion" class="form-control" rows="3"></textarea>
                            </div>

                            <div class="col-12 col-md-4 mb-3">
                                <label for="precio_venta" class="form-label">Precio de venta *</label>
                                <input type="number" step="0.01" min="0" id="precio_venta" name="precio_venta" class="form-control" required>
                            </div>

                            <div class="col-12 col-md-4 mb-3">
                                <label for="stock_minimo" class="form-label">Stock mínimo *</label>
                                <input type="number" min="0" id="stock_minimo" name="stock_minimo" class="form-control" required>
                            </div>

                            <div class="col-12 col-md-4 mb-3">
                                <label for="requiere_receta" class="form-label">Requiere receta</label>
                                <select id="requiere_receta" name="requiere_receta" class="form-select" required>
                                    <option value="0">No</option>
                                    <option value="1">Sí</option>
                                </select>
                            </div>

                            <div class="col-12 mb-3">
                                <label for="uso_terapeutico" class="form-label">Uso terapéutico</label>
                                <input type="text" id="uso_terapeutico" name="uso_terapeutico" class="form-control">
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">Guardar</button>
                            <a href="<?= BASE_URL; ?>/modules/productos/index.php" class="btn btn-outline-secondary">
                                Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>