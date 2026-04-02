<?php
$allowed_roles = ['administradora'];
require_once __DIR__ . '/../../includes/role_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';

$error = $_SESSION['producto_error'] ?? '';
unset($_SESSION['producto_error']);

$idProducto = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$modoEdicion = $idProducto > 0;

$producto = [
    'id_producto' => 0,
    'nombre' => '',
    'presentacion' => '',
    'descripcion' => '',
    'precio_venta' => '',
    'stock_minimo' => '',
    'requiere_receta' => 0,
    'uso_terapeutico' => '',
];

if ($modoEdicion) {
    try {
        $pdo = getPDO();

        $sql = "SELECT id_producto, nombre, presentacion, descripcion, precio_venta, stock_minimo, requiere_receta, uso_terapeutico
                FROM producto
                WHERE id_producto = :id_producto
                LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id_producto' => $idProducto]);

        $resultado = $stmt->fetch();

        if (!$resultado) {
            $_SESSION['access_error'] = 'Producto no encontrado.';
            header('Location: ' . BASE_URL . '/modules/productos/index.php');
            exit;
        }

        $producto = $resultado;
    } catch (Throwable $e) {
        $_SESSION['access_error'] = 'No se pudo cargar el producto.';
        header('Location: ' . BASE_URL . '/modules/productos/index.php');
        exit;
    }
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

        <div class="col-12 col-md-9 col-lg-10">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h1 class="mb-0"><?= $modoEdicion ? 'Editar producto' : 'Nuevo producto'; ?></h1>
                        <a href="<?= BASE_URL; ?>/modules/productos/index.php" class="btn btn-secondary">
                            Volver
                        </a>
                    </div>

                    <p class="text-muted">
                        <?= $modoEdicion ? 'Actualiza la información del producto.' : 'Formulario para registrar un producto.'; ?>
                    </p>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form action="<?= BASE_URL; ?>/modules/productos/action.php" method="POST">
                        <input type="hidden" name="id_producto" value="<?= (int) $producto['id_producto']; ?>">

                        <div class="row">
                            <div class="col-12 col-md-6 mb-3">
                                <label for="nombre" class="form-label">Nombre *</label>
                                <input
                                    type="text"
                                    id="nombre"
                                    name="nombre"
                                    class="form-control"
                                    required
                                    value="<?= htmlspecialchars((string) $producto['nombre']); ?>">
                            </div>

                            <div class="col-12 col-md-6 mb-3">
                                <label for="presentacion" class="form-label">Presentación</label>
                                <input
                                    type="text"
                                    id="presentacion"
                                    name="presentacion"
                                    class="form-control"
                                    value="<?= htmlspecialchars((string) ($producto['presentacion'] ?? '')); ?>">
                            </div>

                            <div class="col-12 mb-3">
                                <label for="descripcion" class="form-label">Descripción</label>
                                <textarea
                                    id="descripcion"
                                    name="descripcion"
                                    class="form-control"
                                    rows="3"><?= htmlspecialchars((string) ($producto['descripcion'] ?? '')); ?></textarea>
                            </div>

                            <div class="col-12 col-md-4 mb-3">
                                <label for="precio_venta" class="form-label">Precio de venta *</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    id="precio_venta"
                                    name="precio_venta"
                                    class="form-control"
                                    required
                                    value="<?= htmlspecialchars((string) $producto['precio_venta']); ?>">
                            </div>

                            <div class="col-12 col-md-4 mb-3">
                                <label for="stock_minimo" class="form-label">Stock mínimo *</label>
                                <input
                                    type="number"
                                    min="0"
                                    id="stock_minimo"
                                    name="stock_minimo"
                                    class="form-control"
                                    required
                                    value="<?= htmlspecialchars((string) $producto['stock_minimo']); ?>">
                            </div>

                            <div class="col-12 col-md-4 mb-3">
                                <label for="requiere_receta" class="form-label">Requiere receta</label>
                                <select id="requiere_receta" name="requiere_receta" class="form-select" required>
                                    <option value="0" <?= (int) $producto['requiere_receta'] === 0 ? 'selected' : ''; ?>>No</option>
                                    <option value="1" <?= (int) $producto['requiere_receta'] === 1 ? 'selected' : ''; ?>>Sí</option>
                                </select>
                            </div>

                            <div class="col-12 mb-3">
                                <label for="uso_terapeutico" class="form-label">Uso terapéutico</label>
                                <input
                                    type="text"
                                    id="uso_terapeutico"
                                    name="uso_terapeutico"
                                    class="form-control"
                                    value="<?= htmlspecialchars((string) ($producto['uso_terapeutico'] ?? '')); ?>">
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">
                                <?= $modoEdicion ? 'Actualizar' : 'Guardar'; ?>
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