<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

$pdo = getPDO();

$fechaActual = date('d/m/Y');

/* =========================
   STOCK BAJO
========================= */
$stockBajo = $pdo->query("
SELECT 
    p.nombre,
    SUM(l.cantidad_actual) AS stock_total,
    p.stock_minimo
FROM Producto p
INNER JOIN Lote l 
    ON p.id_producto = l.id_producto
GROUP BY p.id_producto
HAVING stock_total <= p.stock_minimo
")->fetchAll();

/* =========================
   PRODUCTOS POR VENCER
========================= */
$porVencer = $pdo->query("
SELECT 
    p.nombre AS nombre_producto,
    l.codigo_lote,
    l.fecha_ingreso,
    l.fecha_vencimiento,
    l.cantidad_actual
FROM Lote l
INNER JOIN Producto p 
    ON p.id_producto = l.id_producto
WHERE l.fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
AND l.cantidad_actual > 0
")->fetchAll();

/* =========================
   COMPRAS
========================= */
$compras = $pdo->query("
SELECT 
    c.id_compra,
    c.fecha_compra,
    p.nombre AS proveedor,
    c.total_compra
FROM Compra c
INNER JOIN Proveedor p 
    ON c.id_proveedor = p.id_proveedor
")->fetchAll();

/* =========================
   VENTAS
========================= */
$ventas = $pdo->query("
SELECT 
    id_venta,
    fecha_venta,
    total_venta
FROM Venta
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Reportes</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

body{
    background:#f4fff6;
    animation: fadeIn 0.5s ease;
}

h2{
    color:#198754;
    font-weight:bold;
}

/* =========================
   SIDEBAR + CONTENIDO
========================= */

.main-card{
    background:white;
    border-radius:20px;
    box-shadow:0 4px 20px rgba(0,0,0,0.08);
    padding:25px;
    animation: slideUp 0.5s ease;
}

/* =========================
   TABS
========================= */

.nav-tabs{
    border:none;
    gap:10px;
}

.nav-tabs .nav-link{
    border:none;
    color:#198754;
    border-radius:12px;
    transition:0.3s;
    font-weight:600;
}

.nav-tabs .nav-link:hover{
    background:#d1f3dd;
}

.nav-tabs .nav-link.active{
    background:#198754;
    color:white;
}

/* =========================
   TABLAS
========================= */

.table{
    background:white;
    border-radius:15px;
    overflow:hidden;
}

.table thead{
    background:#198754;
    color:white;
}

.table tbody tr{
    transition:0.3s;
}

.table tbody tr:hover{
    background:#f1fff5;
    transform:scale(1.003);
}

.text-danger{
    color:#dc3545 !important;
}

/* =========================
   BOTÓN IMPRIMIR
========================= */

.btn-print{
    background:#198754;
    color:white;
    border:none;
    padding:10px 25px;
    border-radius:10px;
    transition:0.3s;
    font-weight:600;
}

.btn-print:hover{
    background:#157347;
    transform:translateY(-2px);
}

/* =========================
   ENCABEZADO IMPRESIÓN
========================= */

.encabezado{
    display:none;
    text-align:center;
    margin-bottom:20px;
}

/* =========================
   ANIMACIONES
========================= */

@keyframes fadeIn{
    from{
        opacity:0;
    }
    to{
        opacity:1;
    }
}

@keyframes slideUp{
    from{
        opacity:0;
        transform:translateY(20px);
    }
    to{
        opacity:1;
        transform:translateY(0);
    }
}

/* =========================
   IMPRESIÓN
========================= */

@media print{

    body *{
        visibility:hidden;
    }

    .tab-pane.active,
    .tab-pane.active *{
        visibility:visible;
    }

    .tab-pane.active{
        position:absolute;
        top:0;
        left:0;
        width:100%;
    }

    .encabezado{
        display:block;
    }

    .btn-print,
    .nav-tabs,
    .sidebar{
        display:none !important;
    }
}

</style>
</head>

<body>

<div class="container-fluid mt-3">
<div class="row">

<!-- SIDEBAR -->
<?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

<!-- CONTENIDO -->
<div class="col-md-9">

<div class="main-card">

<div class="d-flex justify-content-between align-items-center">
    <h2>Reportes</h2>

    <button onclick="window.print()" class="btn-print">
        Imprimir
    </button>
</div>

<!-- TABS -->
<ul class="nav nav-tabs mt-4">

    <li class="nav-item">
        <button class="nav-link active"
                data-bs-toggle="tab"
                data-bs-target="#stock">
            Stock Bajo
        </button>
    </li>

    <li class="nav-item">
        <button class="nav-link"
                data-bs-toggle="tab"
                data-bs-target="#vencer">
            Por Vencer
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

<!-- CONTENIDO TABS -->
<div class="tab-content mt-4">

<!-- =========================
     STOCK BAJO
========================= -->

<div class="tab-pane fade show active" id="stock">

<div class="encabezado">
    <h3>Farmacia Nikte</h3>
    <p>Reporte de Stock Bajo</p>
    <p>Fecha: <?= $fechaActual ?></p>
</div>

<table class="table table-hover align-middle">

<thead>
<tr>
    <th>Producto</th>
    <th>Stock Actual</th>
    <th>Stock Mínimo</th>
</tr>
</thead>

<tbody>

<?php if(count($stockBajo) > 0): ?>

<?php foreach($stockBajo as $r): ?>

<tr>
    <td><?= $r['nombre'] ?></td>
    <td><?= $r['stock_total'] ?></td>
    <td><?= $r['stock_minimo'] ?></td>
</tr>

<?php endforeach; ?>

<?php else: ?>

<tr>
    <td colspan="3" class="text-center">
        No hay productos con stock bajo
    </td>
</tr>

<?php endif; ?>

</tbody>
</table>
</div>

<!-- =========================
     POR VENCER
========================= -->

<div class="tab-pane fade" id="vencer">

<div class="encabezado">
    <h3>Farmacia Nikte</h3>
    <p>Reporte de Productos por Vencer</p>
    <p>Fecha: <?= $fechaActual ?></p>
</div>

<table class="table table-hover align-middle">

<thead>
<tr>
    <th>Producto</th>
    <th>Lote</th>
    <th>Fecha Ingreso</th>
    <th>Fecha Vencimiento</th>
    <th>Cantidad</th>
</tr>
</thead>

<tbody>

<?php if(count($porVencer) > 0): ?>

<?php foreach($porVencer as $r): ?>

<tr>
    <td><?= $r['nombre_producto'] ?></td>

    <td><?= $r['codigo_lote'] ?></td>

    <td>
        <?= date('d/m/Y', strtotime($r['fecha_ingreso'])) ?>
    </td>

    <td class="text-danger fw-bold">
        <?= date('d/m/Y', strtotime($r['fecha_vencimiento'])) ?>
    </td>

    <td><?= $r['cantidad_actual'] ?></td>
</tr>

<?php endforeach; ?>

<?php else: ?>

<tr>
    <td colspan="5" class="text-center">
        No hay productos próximos a vencer
    </td>
</tr>

<?php endif; ?>

</tbody>
</table>
</div>

<!-- =========================
     COMPRAS
========================= -->

<div class="tab-pane fade" id="compras">

<div class="encabezado">
    <h3>Farmacia Nikte</h3>
    <p>Reporte de Compras</p>
    <p>Fecha: <?= $fechaActual ?></p>
</div>

<table class="table table-hover align-middle">

<thead>
<tr>
    <th>ID</th>
    <th>Fecha</th>
    <th>Proveedor</th>
    <th>Total</th>
</tr>
</thead>

<tbody>

<?php if(count($compras) > 0): ?>

<?php foreach($compras as $r): ?>

<tr>
    <td><?= $r['id_compra'] ?></td>

    <td>
        <?= date('d/m/Y', strtotime($r['fecha_compra'])) ?>
    </td>

    <td><?= $r['proveedor'] ?></td>

    <td>Q<?= number_format($r['total_compra'], 2) ?></td>
</tr>

<?php endforeach; ?>

<?php else: ?>

<tr>
    <td colspan="4" class="text-center">
        No hay compras registradas
    </td>
</tr>

<?php endif; ?>

</tbody>
</table>
</div>

<!-- =========================
     VENTAS
========================= -->

<div class="tab-pane fade" id="ventas">

<div class="encabezado">
    <h3>Farmacia Nikte</h3>
    <p>Reporte de Ventas</p>
    <p>Fecha: <?= $fechaActual ?></p>
</div>

<table class="table table-hover align-middle">

<thead>
<tr>
    <th>ID</th>
    <th>Fecha</th>
    <th>Total</th>
</tr>
</thead>

<tbody>

<?php if(count($ventas) > 0): ?>

<?php foreach($ventas as $r): ?>

<tr>
    <td><?= $r['id_venta'] ?></td>

    <td>
        <?= date('d/m/Y', strtotime($r['fecha_venta'])) ?>
    </td>

    <td>Q<?= number_format($r['total_venta'], 2) ?></td>
</tr>

<?php endforeach; ?>

<?php else: ?>

<tr>
    <td colspan="3" class="text-center">
        No hay ventas registradas
    </td>
</tr>

<?php endif; ?>

</tbody>
</table>
</div>

</div>
</div>
</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>