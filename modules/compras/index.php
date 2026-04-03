<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';

$compras = [];
$error = '';
$success = $_SESSION['compra_success'] ?? '';
unset($_SESSION['compra_success']);

$access_error = $_SESSION['access_error'] ?? '';
unset($_SESSION['access_error']);

try {
    $pdo = getPDO();

    $sql = "SELECT
                c.id_compra,
                c.fecha_compra,
                c.total_compra,
                p.nombre AS proveedor,
                u.nombre AS usuario
            FROM compra c
            INNER JOIN proveedor p ON p.id_proveedor = c.id_proveedor
            INNER JOIN usuario u ON u.id_usuario = c.id_usuario
            ORDER BY c.fecha_compra DESC, c.id_compra DESC";

    $stmt = $pdo->query($sql);
    $compras = $stmt->fetchAll();
} catch (Throwable $e) {
    $error = 'No se pudieron cargar las compras.';
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

        <div class="col-12 col-md-9 col-lg-10">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h1 class="mb-0">Compras</h1>

                        <?php if (currentRole() === 'administradora'): ?>
                            <a href="<?= BASE_URL; ?>/modules/compras/form.php" class="btn btn-success">
                                Nueva compra
                            </a>
                        <?php endif; ?>
                    </div>

                    <p class="text-muted">
                        Historial de compras registradas en el sistema.
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
                    <?php elseif (empty($compras)): ?>
                        <div class="alert alert-warning">
                            No hay compras registradas todavía.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Fecha</th>
                                        <th>Proveedor</th>
                                        <th>Usuario</th>
                                        <th>Total compra</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($compras as $compra): ?>
                                        <tr>
                                            <td><?= (int) $compra['id_compra']; ?></td>
                                            <td><?= htmlspecialchars($compra['fecha_compra']); ?></td>
                                            <td><?= htmlspecialchars($compra['proveedor']); ?></td>
                                            <td><?= htmlspecialchars($compra['usuario']); ?></td>
                                            <td>Q<?= number_format((float) $compra['total_compra'], 2); ?></td>
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