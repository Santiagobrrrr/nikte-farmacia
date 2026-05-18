<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';

$fechaActual = date('d/m/Y');
$error = '';

$stockBajo = [];
$porVencer = [];
$compras = [];
$ventas = [];

$totalCompras = 0;
$totalVentas = 0;

try {
    $pdo = getPDO();

    /*
    |--------------------------------------------------------------------------
    | FILTROS DE FECHAS
    |--------------------------------------------------------------------------
    */

    $tipoFiltro = $_GET['filtro'] ?? 'hoy';

    $fechaInicio = null;
    $fechaFin = null;

    switch ($tipoFiltro) {
        case 'hoy':
            $fechaInicio = date('Y-m-d');
            $fechaFin = date('Y-m-d');
        break;

        case '7dias':
            $fechaInicio = date('Y-m-d', strtotime('-7 days'));
            $fechaFin = date('Y-m-d');
        break;

        case '30dias':
            $fechaInicio = date('Y-m-d', strtotime('-30 days'));
            $fechaFin = date('Y-m-d');
        break;

        case 'personalizado':
            $fechaInicio = $_GET['fecha_inicio'] ?? '';
            $fechaFin = $_GET['fecha_fin'] ?? '';
        break;

        default:
            $fechaInicio = date('Y-m-d');
            $fechaFin = date('Y-m-d');
        break;
    }

    /*
    |--------------------------------------------------------------------------
    | STOCK BAJO
    |--------------------------------------------------------------------------
    */

    $stockBajo = $pdo->query("
        SELECT
            p.id_producto,
            p.nombre,
            p.presentacion,
            p.stock_minimo,
            COALESCE(SUM(
                CASE
                    WHEN l.fecha_vencimiento >= CURDATE()
                    THEN l.cantidad_actual
                    ELSE 0
                END
            ), 0) AS stock_actual
        FROM producto p
        LEFT JOIN lote l ON l.id_producto = p.id_producto
        WHERE p.activo = 1
        GROUP BY p.id_producto, p.nombre, p.presentacion, p.stock_minimo
        HAVING stock_actual <= p.stock_minimo
        ORDER BY stock_actual ASC, p.nombre ASC
    ")->fetchAll();

    /*
    |--------------------------------------------------------------------------
    | PRODUCTOS POR VENCER
    |--------------------------------------------------------------------------
    */

    $stmtPorVencer = $pdo->prepare("
        SELECT
            p.nombre AS nombre_producto,
            p.presentacion,
            l.codigo_lote,
            l.fecha_ingreso,
            l.fecha_vencimiento,
            l.cantidad_actual,
            DATEDIFF(l.fecha_vencimiento, CURDATE()) AS dias_restantes
        FROM lote l
        INNER JOIN producto p
            ON p.id_producto = l.id_producto
        WHERE l.fecha_vencimiento
            BETWEEN :fecha_inicio AND :fecha_fin
        AND l.cantidad_actual > 0
        ORDER BY l.fecha_vencimiento ASC
    ");

    $stmtPorVencer->execute([
        'fecha_inicio' => $fechaInicio,
        'fecha_fin' => $fechaFin
    ]);

    $porVencer = $stmtPorVencer->fetchAll();

    /*
    |--------------------------------------------------------------------------
    | COMPRAS
    |--------------------------------------------------------------------------
    */

    $stmtCompras = $pdo->prepare("
        SELECT
            c.id_compra,
            c.fecha_compra,
            pr.nombre AS proveedor,
            u.nombre AS usuario,
            c.total_compra
        FROM compra c
        LEFT JOIN proveedor pr
            ON pr.id_proveedor = c.id_proveedor
        LEFT JOIN usuario u
            ON u.id_usuario = c.id_usuario
        WHERE DATE(c.fecha_compra)
            BETWEEN :fecha_inicio AND :fecha_fin
        ORDER BY c.fecha_compra DESC
    ");

    $stmtCompras->execute([
        'fecha_inicio' => $fechaInicio,
        'fecha_fin' => $fechaFin
    ]);

    $compras = $stmtCompras->fetchAll();

    /*
    |--------------------------------------------------------------------------
    | VENTAS
    |--------------------------------------------------------------------------
    */

    $stmtVentas = $pdo->prepare("
        SELECT
            v.id_venta,
            v.fecha_venta,
            v.metodo_pago,
            v.total_venta,
            c.nombre AS cliente,
            u.nombre AS usuario
        FROM venta v
        LEFT JOIN cliente c
            ON c.id_cliente = v.id_cliente
        LEFT JOIN usuario u
            ON u.id_usuario = v.id_usuario
        WHERE DATE(v.fecha_venta)
            BETWEEN :fecha_inicio AND :fecha_fin
        ORDER BY v.fecha_venta DESC
    ");

    $stmtVentas->execute([
        'fecha_inicio' => $fechaInicio,
        'fecha_fin' => $fechaFin
    ]);

    $ventas = $stmtVentas->fetchAll();

    /*
    |--------------------------------------------------------------------------
    | TOTALES
    |--------------------------------------------------------------------------
    */

    foreach ($compras as $compra) {
        $totalCompras += (float) $compra['total_compra'];
    }

    foreach ($ventas as $venta) {
        $totalVentas += (float) $venta['total_venta'];
    }

} catch (Throwable $e) {
    $error = 'No se pudieron cargar los reportes.';
}

require_once __DIR__ . '/../../includes/header.php';
?>

<style>
body{
    background: #f4fff6;
}

.nav-tabs .nav-link.active{
    background: #198754;
    color: white;
    border-color: #198754;
}

.card{
    border-radius: 14px;
}

.report-header-print{
    display: none;
}

@media print{

    body *{
        visibility: hidden;
    }

    .tab-pane.active,
    .tab-pane.active *{
        visibility: visible;
    }

    .tab-pane.active{
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
    }

    .report-header-print{
        display: block !important;
        text-align: center;
        margin-bottom: 20px;
    }

    .no-print{
        display: none !important;
    }
}
</style>

<div class="container-fluid py-4">
<div class="row">

<?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

<div class="col-12 col-md-9 col-lg-10">

<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <div>
        <h1 class="mb-1">Reportes</h1>
        <p class="text-muted mb-0">
            Reportes del sistema filtrados por fecha.
        </p>
    </div>

    <button onclick="window.print()" class="btn btn-success">
        Imprimir reporte activo
    </button>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger">
        <?= htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<!-- FILTROS -->
<div class="card shadow-sm border-0 mb-4 no-print">
    <div class="card-body">

        <form method="GET">

            <div class="row g-3 align-items-end">

                <div class="col-md-3">
                    <label class="form-label">
                        Tipo de filtro
                    </label>

                    <select
                        name="filtro"
                        id="filtro"
                        class="form-select">

                        <option value="hoy"
                            <?= $tipoFiltro === 'hoy' ? 'selected' : '' ?>>
                            Hoy
                        </option>

                        <option value="7dias"
                            <?= $tipoFiltro === '7dias' ? 'selected' : '' ?>>
                            Últimos 7 días
                        </option>

                        <option value="30dias"
                            <?= $tipoFiltro === '30dias' ? 'selected' : '' ?>>
                            Últimos 30 días
                        </option>

                        <option value="personalizado"
                            <?= $tipoFiltro === 'personalizado' ? 'selected' : '' ?>>
                            Personalizado
                        </option>

                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">
                        Fecha inicio
                    </label>

                    <input
                        type="date"
                        name="fecha_inicio"
                        class="form-control"
                        value="<?= htmlspecialchars($fechaInicio); ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label">
                        Fecha fin
                    </label>

                    <input
                        type="date"
                        name="fecha_fin"
                        class="form-control"
                        value="<?= htmlspecialchars($fechaFin); ?>">
                </div>

                <div class="col-md-3">
                    <button type="submit" class="btn btn-success w-100">
                        Filtrar reportes
                    </button>
                </div>

            </div>

        </form>

    </div>
</div>

<!-- RESUMEN -->
<div class="row g-3 mb-4">

    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h6 class="text-muted">
                    Stock bajo
                </h6>

                <h3>
                    <?= count($stockBajo); ?>
                </h3>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h6 class="text-muted">
                    Por vencer
                </h6>

                <h3>
                    <?= count($porVencer); ?>
                </h3>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h6 class="text-muted">
                    Compras
                </h6>

                <h3>
                    Q<?= number_format($totalCompras, 2); ?>
                </h3>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h6 class="text-muted">
                    Ventas
                </h6>

                <h3>
                    Q<?= number_format($totalVentas, 2); ?>
                </h3>
            </div>
        </div>
    </div>

</div>

<!-- REPORTES -->
<div class="card shadow-sm border-0">
<div class="card-body">

<ul class="nav nav-tabs no-print">

    <li class="nav-item">
        <button class="nav-link active"
                data-bs-toggle="tab"
                data-bs-target="#stock">
            Stock bajo
        </button>
    </li>

    <li class="nav-item">
        <button class="nav-link"
                data-bs-toggle="tab"
                data-bs-target="#vencer">
            Por vencer
        </button>
    </li>

    <li class="nav-item">
        <button class="nav-link"
                data-bs-toggle="tab"
                data-bs-target="#compras">
            Compras
        </button>
    </li>

    <li class="nav-item">
        <button class="nav-link"
                data-bs-toggle="tab"
                data-bs-target="#ventas">
            Ventas
        </button>
    </li>

</ul>

<div class="tab-content mt-4">

<!-- STOCK -->
<div class="tab-pane fade show active" id="stock">

<div class="report-header-print">
    <h3><?= htmlspecialchars(APP_NAME); ?></h3>
    <p>Reporte de Stock Bajo</p>
    <p><?= $fechaActual; ?></p>
</div>

<table class="table table-bordered table-hover">
<thead class="table-light">
<tr>
    <th>Producto</th>
    <th>Presentación</th>
    <th>Stock mínimo</th>
    <th>Stock actual</th>
</tr>
</thead>

<tbody>

<?php foreach($stockBajo as $item): ?>

<tr>
    <td><?= htmlspecialchars($item['nombre']); ?></td>
    <td><?= htmlspecialchars($item['presentacion']); ?></td>
    <td><?= $item['stock_minimo']; ?></td>
    <td><?= $item['stock_actual']; ?></td>
</tr>

<?php endforeach; ?>

</tbody>
</table>
</div>

<!-- POR VENCER -->
<div class="tab-pane fade" id="vencer">

<div class="report-header-print">
    <h3><?= htmlspecialchars(APP_NAME); ?></h3>
    <p>Reporte de Productos por Vencer</p>
    <p><?= $fechaActual; ?></p>
</div>

<table class="table table-bordered table-hover">
<thead class="table-light">
<tr>
    <th>Producto</th>
    <th>Lote</th>
    <th>Fecha ingreso</th>
    <th>Fecha vencimiento</th>
    <th>Días restantes</th>
</tr>
</thead>

<tbody>

<?php foreach($porVencer as $item): ?>

<tr>
    <td><?= htmlspecialchars($item['nombre_producto']); ?></td>
    <td><?= htmlspecialchars($item['codigo_lote']); ?></td>
    <td><?= date('d/m/Y', strtotime($item['fecha_ingreso'])); ?></td>
    <td><?= date('d/m/Y', strtotime($item['fecha_vencimiento'])); ?></td>
    <td><?= $item['dias_restantes']; ?></td>
</tr>

<?php endforeach; ?>

</tbody>
</table>
</div>

<!-- COMPRAS -->
<div class="tab-pane fade" id="compras">

<div class="report-header-print">
    <h3><?= htmlspecialchars(APP_NAME); ?></h3>
    <p>Reporte de Compras</p>
    <p><?= $fechaActual; ?></p>
</div>

<table class="table table-bordered table-hover">
<thead class="table-light">
<tr>
    <th>ID</th>
    <th>Fecha</th>
    <th>Proveedor</th>
    <th>Usuario</th>
    <th>Total</th>
</tr>
</thead>

<tbody>

<?php foreach($compras as $item): ?>

<tr>
    <td><?= $item['id_compra']; ?></td>
    <td><?= date('d/m/Y', strtotime($item['fecha_compra'])); ?></td>
    <td><?= htmlspecialchars($item['proveedor']); ?></td>
    <td><?= htmlspecialchars($item['usuario']); ?></td>
    <td>Q<?= number_format($item['total_compra'], 2); ?></td>
</tr>

<?php endforeach; ?>

</tbody>
</table>

</div>

<!-- VENTAS -->
<div class="tab-pane fade" id="ventas">

<div class="report-header-print">
    <h3><?= htmlspecialchars(APP_NAME); ?></h3>
    <p>Reporte de Ventas</p>
    <p><?= $fechaActual; ?></p>
</div>

<table class="table table-bordered table-hover">
<thead class="table-light">
<tr>
    <th>ID</th>
    <th>Fecha</th>
    <th>Cliente</th>
    <th>Usuario</th>
    <th>Método</th>
    <th>Total</th>
</tr>
</thead>

<tbody>

<?php foreach($ventas as $item): ?>

<tr>
    <td><?= $item['id_venta']; ?></td>
    <td><?= date('d/m/Y', strtotime($item['fecha_venta'])); ?></td>
    <td><?= htmlspecialchars($item['cliente'] ?? 'Consumidor final'); ?></td>
    <td><?= htmlspecialchars($item['usuario']); ?></td>
    <td><?= htmlspecialchars($item['metodo_pago']); ?></td>
    <td>Q<?= number_format($item['total_venta'], 2); ?></td>
</tr>

<?php endforeach; ?>

</tbody>
</table>

</div>

</div>
</div>
</div>

</div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>