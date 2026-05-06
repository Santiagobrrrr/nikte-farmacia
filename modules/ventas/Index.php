<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';

$success = $_SESSION['venta_success'] ?? '';
$error = $_SESSION['venta_error'] ?? '';
unset($_SESSION['venta_success'], $_SESSION['venta_error']);

$ventas = [];
$totalFiltrado = 0;
$cantidadVentas = 0;

$busqueda = trim($_GET['q'] ?? '');
$fechaDesde = trim($_GET['fecha_desde'] ?? '');
$fechaHasta = trim($_GET['fecha_hasta'] ?? '');
$metodoPago = trim($_GET['metodo_pago'] ?? '');

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
        WHERE 1 = 1
    ";

    $params = [];

    if ($busqueda !== '') {
        $sql .= " AND (
                    c.nombre LIKE :busqueda
                    OR u.nombre LIKE :busqueda
                    OR v.id_venta LIKE :busqueda
                  )";
        $params['busqueda'] = '%' . $busqueda . '%';
    }

    if ($fechaDesde !== '') {
        $sql .= " AND DATE(v.fecha_venta) >= :fecha_desde";
        $params['fecha_desde'] = $fechaDesde;
    }

    if ($fechaHasta !== '') {
        $sql .= " AND DATE(v.fecha_venta) <= :fecha_hasta";
        $params['fecha_hasta'] = $fechaHasta;
    }

    if ($metodoPago !== '') {
        $sql .= " AND v.metodo_pago = :metodo_pago";
        $params['metodo_pago'] = $metodoPago;
    }

    $sql .= " ORDER BY v.fecha_venta DESC, v.id_venta DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $ventas = $stmt->fetchAll();

    foreach ($ventas as $venta) {
        $totalFiltrado += (float) $venta['total_venta'];
    }

    $cantidadVentas = count($ventas);

} catch (Throwable $e) {
    $error = 'No se pudieron cargar las ventas.';
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

        <div class="col-12 col-md-9 col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="mb-1">Ventas</h1>
                    <p class="text-muted mb-0">
                        Historial de ventas registradas en el sistema.
                    </p>
                </div>

                <a href="<?= BASE_URL; ?>/modules/ventas/form.php" class="btn btn-success">
                    Nueva venta
                </a>
            </div>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="row g-3 mb-3">
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <h6 class="text-muted mb-1">Ventas encontradas</h6>
                            <h3 class="mb-0"><?= (int) $cantidadVentas; ?></h3>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <h6 class="text-muted mb-1">Total vendido</h6>
                            <h3 class="mb-0">Q<?= number_format($totalFiltrado, 2); ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body">
                    <form method="GET" action="<?= BASE_URL; ?>/modules/ventas/index.php" class="row g-2 align-items-end">
                        <div class="col-12 col-md-4">
                            <label class="form-label">Buscar</label>
                            <input
                                type="text"
                                name="q"
                                class="form-control"
                                placeholder="Cliente, vendedor o número de venta"
                                value="<?= htmlspecialchars($busqueda); ?>">
                        </div>

                        <div class="col-12 col-md-2">
                            <label class="form-label">Desde</label>
                            <input
                                type="date"
                                name="fecha_desde"
                                class="form-control"
                                value="<?= htmlspecialchars($fechaDesde); ?>">
                        </div>

                        <div class="col-12 col-md-2">
                            <label class="form-label">Hasta</label>
                            <input
                                type="date"
                                name="fecha_hasta"
                                class="form-control"
                                value="<?= htmlspecialchars($fechaHasta); ?>">
                        </div>

                        <div class="col-12 col-md-2">
                            <label class="form-label">Método de pago</label>
                            <select name="metodo_pago" class="form-select">
                                <option value="">Todos</option>
                                <option value="efectivo" <?= $metodoPago === 'efectivo' ? 'selected' : ''; ?>>Efectivo</option>
                                <option value="tarjeta" <?= $metodoPago === 'tarjeta' ? 'selected' : ''; ?>>Tarjeta</option>
                                <option value="transferencia" <?= $metodoPago === 'transferencia' ? 'selected' : ''; ?>>Transferencia</option>
                            </select>
                        </div>

                        <div class="col-6 col-md-1">
                            <button type="submit" class="btn btn-primary w-100">
                                Filtrar
                            </button>
                        </div>

                        <div class="col-6 col-md-1">
                            <a href="<?= BASE_URL; ?>/modules/ventas/index.php" class="btn btn-outline-secondary w-100">
                                Limpiar
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <?php if (empty($ventas)): ?>
                        <div class="alert alert-warning mb-0">
                            No hay ventas registradas con los filtros seleccionados.
                        </div>
                    <?php else: ?>
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
                                        <th width="160">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ventas as $venta): ?>
                                        <tr>
                                            <td><?= (int) $venta['id_venta']; ?></td>

                                            <td>
                                                <?= date('d/m/Y H:i', strtotime($venta['fecha_venta'])); ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars($venta['nombre_cliente'] ?? 'Consumidor final'); ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars($venta['nombre_usuario'] ?? ''); ?>
                                            </td>

                                            <td>
                                                <span class="badge text-bg-light border">
                                                    <?= ucfirst(htmlspecialchars($venta['metodo_pago'])); ?>
                                                </span>
                                            </td>

                                            <td class="fw-semibold">
                                                Q<?= number_format((float) $venta['total_venta'], 2); ?>
                                            </td>

                                            <td>
                                                <a href="<?= BASE_URL; ?>/modules/ventas/show.php?id=<?= (int) $venta['id_venta']; ?>"
                                                   class="btn btn-sm btn-outline-dark">
                                                    Ver comprobante
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>

                                <tfoot>
                                    <tr>
                                        <td colspan="5" class="text-end fw-bold">
                                            Total mostrado:
                                        </td>
                                        <td colspan="2" class="fw-bold">
                                            Q<?= number_format($totalFiltrado, 2); ?>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>