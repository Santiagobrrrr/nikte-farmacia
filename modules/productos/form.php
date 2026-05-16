<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';

$idProducto = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$producto = [
    'id_producto' => '',
    'nombre' => '',
    'descripcion' => '',
    'presentacion' => '',
    'precio_venta' => '',
    'margen_ganancia' => '0',
    'stock_minimo' => '0',
    'requiere_receta' => '0',
    'uso_terapeutico' => '',
];

$error = $_SESSION['producto_error'] ?? '';
unset($_SESSION['producto_error']);

try {
    if ($idProducto > 0) {
        $pdo = getPDO();

        $stmt = $pdo->prepare("
            SELECT
                id_producto,
                nombre,
                descripcion,
                presentacion,
                precio_venta,
                margen_ganancia,
                stock_minimo,
                requiere_receta,
                uso_terapeutico
            FROM producto
            WHERE id_producto = :id_producto
            LIMIT 1
        ");

        $stmt->execute(['id_producto' => $idProducto]);
        $resultado = $stmt->fetch();

        if ($resultado) {
            $producto = $resultado;
        } else {
            $_SESSION['producto_error'] = 'El producto seleccionado no existe.';
            header('Location: ' . BASE_URL . '/modules/productos/index.php');
            exit;
        }
    }
} catch (Throwable $e) {
    $error = 'No se pudo cargar el producto.';
}

$titulo = $idProducto > 0 ? 'Editar producto' : 'Nuevo producto';
?>

<div class="container-fluid py-4">
    <div class="row">
        <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

        <div class="col-12 col-md-9 col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="mb-1"><?= htmlspecialchars($titulo); ?></h1>
                    <p class="text-muted mb-0">
                        Registra la información principal del producto y su margen deseado.
                    </p>
                </div>

                <a href="<?= BASE_URL; ?>/modules/productos/index.php" class="btn btn-secondary">
                    Volver
                </a>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <form method="POST" action="<?= BASE_URL; ?>/modules/productos/action.php">
                        <input type="hidden" name="id_producto" value="<?= (int) ($producto['id_producto'] ?? 0); ?>">

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Nombre *</label>
                                <input
                                    type="text"
                                    name="nombre"
                                    class="form-control"
                                    value="<?= htmlspecialchars($producto['nombre'] ?? ''); ?>"
                                    required>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Presentación</label>
                                <input
                                    type="text"
                                    name="presentacion"
                                    class="form-control"
                                    value="<?= htmlspecialchars($producto['presentacion'] ?? ''); ?>"
                                    placeholder="Ejemplo: Tabletas 500 mg">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Descripción</label>
                                <textarea
                                    name="descripcion"
                                    class="form-control"
                                    rows="2"><?= htmlspecialchars($producto['descripcion'] ?? ''); ?></textarea>
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label">Precio de venta</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    name="precio_venta"
                                    class="form-control"
                                    value="<?= htmlspecialchars($producto['precio_venta'] ?? '0'); ?>"
                                    required>
                                <small class="text-muted">
                                    Precio actual al que se venderá el producto.
                                </small>
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label">Margen de ganancia deseado (%)</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    name="margen_ganancia"
                                    class="form-control"
                                    value="<?= htmlspecialchars($producto['margen_ganancia'] ?? '0'); ?>"
                                    placeholder="Ejemplo: 30">
                                <small class="text-muted">
                                    Se usará para sugerir precios según el costo del lote.
                                </small>
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label">Stock mínimo</label>
                                <input
                                    type="number"
                                    min="0"
                                    name="stock_minimo"
                                    class="form-control"
                                    value="<?= htmlspecialchars($producto['stock_minimo'] ?? '0'); ?>"
                                    required>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">¿Requiere receta?</label>
                                <select name="requiere_receta" class="form-select">
                                    <option value="0" <?= (int) ($producto['requiere_receta'] ?? 0) === 0 ? 'selected' : ''; ?>>
                                        No
                                    </option>
                                    <option value="1" <?= (int) ($producto['requiere_receta'] ?? 0) === 1 ? 'selected' : ''; ?>>
                                        Sí
                                    </option>
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Uso terapéutico</label>
                                <input
                                    type="text"
                                    name="uso_terapeutico"
                                    class="form-control"
                                    value="<?= htmlspecialchars($producto['uso_terapeutico'] ?? ''); ?>"
                                    placeholder="Ejemplo: Antibiótico, analgésico, antihistamínico">
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-success">
                                Guardar producto
                            </button>

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