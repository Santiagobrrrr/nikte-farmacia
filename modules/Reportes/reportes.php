<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

$pdo = getPDO();

$fechaActual = date('d/m/Y');

// STOCK BAJO
$stockBajo = $pdo->query("
SELECT p.nombre, SUM(l.cantidad_actual) AS stock_total, p.stock_minimo
FROM Producto p
INNER JOIN Lote l ON p.id_producto = l.id_producto
GROUP BY p.id_producto
HAVING stock_total <= p.stock_minimo
")->fetchAll();

// POR VENCER (AGREGAMOS LOTE 🔥)
$porVencer = $pdo->query("
SELECT nombre_producto, codigo_lote, fecha_vencimiento, cantidad_actual 
FROM productos_por_vencer
")->fetchAll();

// COMPRAS
$compras = $pdo->query("
SELECT c.id_compra, c.fecha_compra, p.nombre AS proveedor, c.total_compra
FROM Compra c
INNER JOIN Proveedor p ON c.id_proveedor = p.id_proveedor
")->fetchAll();

// VENTAS
$ventas = $pdo->query("
SELECT id_venta, fecha_venta, total_venta FROM Venta
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Reportes</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body { background: #f4fff6; }
h2 { color: #198754; }

/* Tabs */
.nav-tabs .nav-link.active {
    background-color: #198754;
    color: white;
}

/* ENCABEZADO PARA IMPRESIÓN */
.encabezado {
    display: none;
    text-align: center;
    margin-bottom: 20px;
}

/* PRINT */
@media print {
    body * { visibility: hidden; }

    .tab-pane.active, .tab-pane.active * {
        visibility: visible;
    }

    .tab-pane.active {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
    }

    .encabezado {
        display: block;
    }
}
</style>
</head>

<body>
<div class="container-fluid">
<div class="row">

<?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

<div class="col-md-9">

<div class="d-flex justify-content-between mt-3">
    <h2>Reportes</h2>
    <button onclick="window.print()" class="btn btn-success">Imprimir</button>
</div>

<ul class="nav nav-tabs mt-3">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#stock">Stock Bajo</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#vencer">Por Vencer</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#compras">Compras</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#ventas">Ventas</button></li>
</ul>

<div class="tab-content mt-3">

<!-- STOCK -->
<div class="tab-pane fade show active" id="stock">
<div class="encabezado">
    <h3>Farmacia Nikte</h3>
    <p>Reporte de Stock Bajo</p>
    <p>Fecha: <?= $fechaActual ?></p>
</div>

<table class="table">
<tr><th>Producto</th><th>Stock</th><th>Mínimo</th></tr>
<?php foreach($stockBajo as $r): ?>
<tr><td><?= $r['nombre'] ?></td><td><?= $r['stock_total'] ?></td><td><?= $r['stock_minimo'] ?></td></tr>
<?php endforeach; ?>
</table>
</div>

<!-- VENCER -->
<div class="tab-pane fade" id="vencer">
<div class="encabezado">
    <h3>Farmacia Nikte</h3>
    <p>Reporte de Productos por Vencer</p>
    <p>Fecha: <?= $fechaActual ?></p>
</div>

<table class="table">
<tr><th>Producto</th><th>Lote</th><th>Fecha</th><th>Cantidad</th></tr>
<?php foreach($porVencer as $r): ?>
<tr>
<td><?= $r['nombre_producto'] ?></td>
<td><?= $r['codigo_lote'] ?></td>
<td><?= $r['fecha_vencimiento'] ?></td>
<td><?= $r['cantidad_actual'] ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>

<!-- COMPRAS -->
<div class="tab-pane fade" id="compras">
<div class="encabezado">
    <h3>Farmacia Nikte</h3>
    <p>Reporte de Compras</p>
    <p>Fecha: <?= $fechaActual ?></p>
</div>

<table class="table">
<tr><th>ID</th><th>Fecha</th><th>Proveedor</th><th>Total</th></tr>
<?php foreach($compras as $r): ?>
<tr>
<td><?= $r['id_compra'] ?></td>
<td><?= $r['fecha_compra'] ?></td>
<td><?= $r['proveedor'] ?></td>
<td><?= $r['total_compra'] ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>

<!-- VENTAS -->
<div class="tab-pane fade" id="ventas">
<div class="encabezado">
    <h3>Farmacia Nikte</h3>
    <p>Reporte de Ventas</p>
    <p>Fecha: <?= $fechaActual ?></p>
</div>

<table class="table">
<tr><th>ID</th><th>Fecha</th><th>Total</th></tr>
<?php foreach($ventas as $r): ?>
<tr>
<td><?= $r['id_venta'] ?></td>
<td><?= $r['fecha_venta'] ?></td>
<td><?= $r['total_venta'] ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>

</div>
</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>