<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';
 
$success = $_SESSION['venta_success'] ?? '';
$error = $_SESSION['venta_error'] ?? '';
unset($_SESSION['venta_success'], $_SESSION['venta_error']);
 
$ventas = [];
 
try {
    $pdo = getPDO();
 
    $sql = "
        SELECT 
            v.id_venta,
            v.fecha_venta,
            v.metodo_pago,
            v.total_venta,
            c.nombre AS nombre_cliente,
            u.nombre AS nombre_usuario
        FROM venta v
        LEFT JOIN cliente c ON c.id_cliente = v.id_cliente
        LEFT JOIN usuario u ON u.id_usuario = v.id_usuario
        ORDER BY v.fecha_venta DESC
    ";
    $ventas = $pdo->query($sql)->fetchAll();
} catch (Throwable $e) {
    $error = 'No se pudo cargar las ventas.';
}
?>
 
<div class="container-fluid py-4">
    <div class="row">
        <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
 
        <div class="col-12 col-md-9 col-lg-10">
            <div class="card shadow-sm">
                <div class="card-body">
 
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h1 class="mb-0">Ventas</h1>
                        <a href="<?= BASE_URL ?>/modules/ventas/form.php" class="btn btn-success">
                            Nueva venta
                        </a>
                    </div>
 
                    <p class="text-muted">Historial de ventas registradas en el sistema.</p>
 
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
 
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Fecha</th>
                                    <th>Cliente</th>
                                    <th>Vendedor</th>
                                    <th>Método de pago</th>
                                    <th>Total</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($ventas)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No hay ventas registradas.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($ventas as $venta): ?>
                                        <tr>
                                            <td><?= $venta['id_venta'] ?></td>
                                            <td><?= date('d/m/Y H:i', strtotime($venta['fecha_venta'])) ?></td>
                                            <td><?= htmlspecialchars($venta['nombre_cliente'] ?? 'Sin cliente') ?></td>
                                            <td><?= htmlspecialchars($venta['nombre_usuario']) ?></td>
                                            <td><?= ucfirst(htmlspecialchars($venta['metodo_pago'])) ?></td>
                                            <td>Q <?= number_format($venta['total_venta'], 2) ?></td>
                                            <td>
                                                <a href="<?= BASE_URL ?>/modules/ventas/show.php?id=<?= $venta['id_venta'] ?>" class="btn btn-sm btn-outline-dark">
                                                    Ver detalle
                                                </a>
                                            </td>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
 
                </div>
            </div>
        </div>
    </div>
</div>
 
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
 