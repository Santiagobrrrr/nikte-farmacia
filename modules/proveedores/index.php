<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';

$proveedores = [];
$error = '';
$success = $_SESSION['proveedor_success'] ?? '';
unset($_SESSION['proveedor_success']);

$access_error = $_SESSION['access_error'] ?? '';
unset($_SESSION['access_error']);

try {
    $pdo = getPDO();

    $sql = "SELECT id_proveedor, nombre, telefono, direccion
            FROM proveedor
            ORDER BY nombre ASC";

    $stmt = $pdo->query($sql);
    $proveedores = $stmt->fetchAll();
} catch (Throwable $e) {
    $error = 'No se pudieron cargar los proveedores.';
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

        <div class="col-12 col-md-9 col-lg-10">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h1 class="mb-0">Proveedores</h1>

                        <?php if (currentRole() === 'administradora'): ?>
                            <a href="<?= BASE_URL; ?>/modules/proveedores/form.php" class="btn btn-success">
                                Nuevo proveedor
                            </a>
                        <?php endif; ?>
                    </div>

                    <p class="text-muted">
                        Listado de proveedores registrados en el sistema.
                    </p>

                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success">
                            <?= htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($access_error)): ?>
                        <div class="alert alert-warning">
                            <?= htmlspecialchars($access_error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($error); ?>
                        </div>
                    <?php elseif (empty($proveedores)): ?>
                        <div class="alert alert-warning">
                            No hay proveedores registrados todavía.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Teléfono</th>
                                        <th>Dirección</th>
                                        <th width="160">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($proveedores as $proveedor): ?>
                                        <tr>
                                            <td><?= (int) $proveedor['id_proveedor']; ?></td>
                                            <td><?= htmlspecialchars($proveedor['nombre']); ?></td>
                                            <td><?= htmlspecialchars($proveedor['telefono'] ?? ''); ?></td>
                                            <td><?= htmlspecialchars($proveedor['direccion'] ?? ''); ?></td>
                                            <td>
                                                <?php if (currentRole() === 'administradora'): ?>
                                                    <a href="<?= BASE_URL; ?>/modules/proveedores/form.php?id=<?= (int) $proveedor['id_proveedor']; ?>" class="btn btn-sm btn-primary">
                                                        Editar
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">Solo lectura</span>
                                                <?php endif; ?>
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