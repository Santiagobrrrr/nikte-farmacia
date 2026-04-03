<?php
$allowed_roles = ['administradora'];
require_once __DIR__ . '/../../includes/role_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';

$error = $_SESSION['proveedor_error'] ?? '';
unset($_SESSION['proveedor_error']);

$idProveedor = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$modoEdicion = $idProveedor > 0;

$proveedor = [
    'id_proveedor' => 0,
    'nombre' => '',
    'telefono' => '',
    'direccion' => '',
];

if ($modoEdicion) {
    try {
        $pdo = getPDO();

        $sql = "SELECT id_proveedor, nombre, telefono, direccion
                FROM proveedor
                WHERE id_proveedor = :id_proveedor
                LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id_proveedor' => $idProveedor]);

        $resultado = $stmt->fetch();

        if (!$resultado) {
            $_SESSION['access_error'] = 'Proveedor no encontrado.';
            header('Location: ' . BASE_URL . '/modules/proveedores/index.php');
            exit;
        }

        $proveedor = $resultado;
    } catch (Throwable $e) {
        $_SESSION['access_error'] = 'No se pudo cargar el proveedor.';
        header('Location: ' . BASE_URL . '/modules/proveedores/index.php');
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
                        <h1 class="mb-0"><?= $modoEdicion ? 'Editar proveedor' : 'Nuevo proveedor'; ?></h1>
                        <a href="<?= BASE_URL; ?>/modules/proveedores/index.php" class="btn btn-secondary">
                            Volver
                        </a>
                    </div>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form action="<?= BASE_URL; ?>/modules/proveedores/action.php" method="POST">
                        <input type="hidden" name="id_proveedor" value="<?= (int) $proveedor['id_proveedor']; ?>">

                        <div class="row">
                            <div class="col-12 col-md-6 mb-3">
                                <label for="nombre" class="form-label">Nombre *</label>
                                <input type="text"
                                       id="nombre"
                                       name="nombre"
                                       class="form-control"
                                       required
                                       value="<?= htmlspecialchars((string) $proveedor['nombre']); ?>">
                            </div>

                            <div class="col-12 col-md-6 mb-3">
                                <label for="telefono" class="form-label">Teléfono</label>
                                <input type="text"
                                       id="telefono"
                                       name="telefono"
                                       class="form-control"
                                       value="<?= htmlspecialchars((string) ($proveedor['telefono'] ?? '')); ?>">
                            </div>

                            <div class="col-12 mb-3">
                                <label for="direccion" class="form-label">Dirección</label>
                                <textarea id="direccion"
                                          name="direccion"
                                          class="form-control"
                                          rows="3"><?= htmlspecialchars((string) ($proveedor['direccion'] ?? '')); ?></textarea>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">
                                <?= $modoEdicion ? 'Actualizar' : 'Guardar'; ?>
                            </button>

                            <a href="<?= BASE_URL; ?>/modules/proveedores/index.php" class="btn btn-outline-secondary">
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