<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';

$idProducto = isset($_GET['id_producto']) ? (int) $_GET['id_producto'] : 0;

if ($idProducto <= 0) {
    $_SESSION['access_error'] = 'Producto no válido.';
    header('Location: ' . BASE_URL . '/modules/productos/index.php');
    exit;
}

$producto = null;
$lotes = [];
$error = '';

try {
    $pdo = getPDO();

    $sqlProducto = "SELECT
                    p.id_producto,
                    p.nombre,
                    p.presentacion,
                    p.stock_minimo,
                    COALESCE(SUM(
                        CASE
                            WHEN l.fecha_vencimiento >= CURDATE() THEN l.cantidad_actual
                            ELSE 0
                        END
                    ), 0) AS stock_actual
                FROM producto p
                LEFT JOIN lote l ON l.id_producto = p.id_producto
                WHERE p.id_producto = :id_producto
                GROUP BY p.id_producto, p.nombre, p.presentacion, p.stock_minimo
                LIMIT 1";

    $stmtProducto = $pdo->prepare($sqlProducto);
    $stmtProducto->execute(['id_producto' => $idProducto]);
    $producto = $stmtProducto->fetch();

    if (!$producto) {
        $_SESSION['access_error'] = 'Producto no encontrado.';
        header('Location: ' . BASE_URL . '/modules/productos/index.php');
        exit;
    }

    $sqlLotes = "SELECT
                    id_lote,
                    codigo_lote,
                    fecha_ingreso,
                    fecha_vencimiento,
                    costo_unitario,
                    cantidad_actual,
                    DATEDIFF(fecha_vencimiento, CURDATE()) AS dias_restantes
                 FROM lote
                 WHERE id_producto = :id_producto
                 ORDER BY fecha_vencimiento ASC, fecha_ingreso ASC";

    $stmtLotes = $pdo->prepare($sqlLotes);
    $stmtLotes->execute(['id_producto' => $idProducto]);
    $lotes = $stmtLotes->fetchAll();

} catch (Throwable $e) {
    $error = 'No se pudo cargar el detalle de lotes.';
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

        <div class="col-12 col-md-9 col-lg-10">
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h1 class="mb-0">Lotes del producto</h1>
                        <a href="<?= BASE_URL; ?>/modules/productos/index.php" class="btn btn-secondary">
                            Volver a inventario
                        </a>
                    </div>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($error); ?>
                        </div>
                    <?php elseif ($producto): ?>
                        <div class="row">
                            <div class="col-12 col-md-6 mb-2">
                                <strong>Producto:</strong> <?= htmlspecialchars($producto['nombre']); ?>
                            </div>
                            <div class="col-12 col-md-6 mb-2">
                                <strong>Presentación:</strong> <?= htmlspecialchars($producto['presentacion'] ?? ''); ?>
                            </div>
                            <div class="col-12 col-md-4 mb-2">
                                <strong>Stock mínimo:</strong> <?= (int) $producto['stock_minimo']; ?>
                            </div>
                            <div class="col-12 col-md-4 mb-2">
                                <strong>Stock actual:</strong> <?= (int) $producto['stock_actual']; ?>
                            </div>
                            <div class="col-12 col-md-4 mb-2">
                                <?php if (currentRole() === 'administradora'): ?>
                                    <a href="<?= BASE_URL; ?>/modules/lotes/form.php?id_producto=<?= (int) $producto['id_producto']; ?>" class="btn btn-success btn-sm">
                                        Agregar lote
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (empty($error)): ?>
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h2 class="h4 mb-3">Detalle de lotes</h2>

                        <?php if (empty($lotes)): ?>
                            <div class="alert alert-warning mb-0">
                                Este producto todavía no tiene lotes registrados.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID lote</th>
                                            <th>Código lote</th>
                                            <th>Fecha ingreso</th>
                                            <th>Fecha vencimiento</th>
                                            <th>Costo unitario</th>
                                            <th>Cantidad actual</th>
                                            <th>Días restantes</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($lotes as $lote): ?>
                                            <?php
                                            $dias = (int) $lote['dias_restantes'];

                                            if ($dias < 0) {
                                                $estado = 'Vencido';
                                                $clase = 'danger';
                                            } elseif ($dias <= 7) {
                                                $estado = 'Por vencer pronto';
                                                $clase = 'warning';
                                            } elseif ($dias <= 30) {
                                                $estado = 'Por vencer';
                                                $clase = 'info';
                                            } else {
                                                $estado = 'Vigente';
                                                $clase = 'success';
                                            }
                                            ?>
                                            <tr>
                                                <td><?= (int) $lote['id_lote']; ?></td>
                                                <td><?= htmlspecialchars($lote['codigo_lote']); ?></td>
                                                <td><?= htmlspecialchars($lote['fecha_ingreso']); ?></td>
                                                <td><?= htmlspecialchars($lote['fecha_vencimiento']); ?></td>
                                                <td>Q<?= number_format((float) $lote['costo_unitario'], 2); ?></td>
                                                <td><?= (int) $lote['cantidad_actual']; ?></td>
                                                <td><?= $dias; ?></td>
                                                <td>
                                                    <span class="badge text-bg-<?= $clase; ?>">
                                                        <?= $estado; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>