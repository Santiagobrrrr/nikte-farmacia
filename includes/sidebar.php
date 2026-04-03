<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/session.php';

$currentPage = $_SERVER['PHP_SELF'] ?? '';

$isDashboard = str_contains($currentPage, '/modules/dashboard/index.php');
$isProductos = str_contains($currentPage, '/modules/productos/index.php')
    || str_contains($currentPage, '/modules/productos/form.php')
    || str_contains($currentPage, '/modules/lotes/index.php');
$isPorVencer = str_contains($currentPage, '/modules/productos/por_vencer.php');
$isStockBajo = str_contains($currentPage, '/modules/productos/stock_bajo.php');
$isIngresarLote = str_contains($currentPage, '/modules/lotes/form.php');
$isProveedores = str_contains($currentPage, '/modules/proveedores/');
$isUsuarios = str_contains($currentPage, '/modules/usuarios/index.php');
?>

<div class="col-12 col-md-3 col-lg-2 mb-3">
    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="card-title">Menú</h5>

            <div class="list-group">
                <a href="<?= BASE_URL; ?>/modules/dashboard/index.php"
                   class="list-group-item list-group-item-action <?= $isDashboard ? 'active' : ''; ?>">
                    Dashboard
                </a>

                <a href="<?= BASE_URL; ?>/modules/productos/index.php"
                   class="list-group-item list-group-item-action <?= $isProductos ? 'active' : ''; ?>">
                    Productos
                </a>

                <a href="<?= BASE_URL; ?>/modules/productos/por_vencer.php"
                   class="list-group-item list-group-item-action <?= $isPorVencer ? 'active' : ''; ?>">
                    Productos por vencer
                </a>

                <a href="<?= BASE_URL; ?>/modules/productos/stock_bajo.php"
                   class="list-group-item list-group-item-action <?= $isStockBajo ? 'active' : ''; ?>">
                    Stock bajo
                </a>

                <a href="<?= BASE_URL; ?>/modules/proveedores/index.php"
                   class="list-group-item list-group-item-action <?= $isProveedores ? 'active' : ''; ?>">
                    Proveedores
                </a>

                <?php if (currentRole() === 'administradora'): ?>
                    <a href="<?= BASE_URL; ?>/modules/lotes/form.php"
                       class="list-group-item list-group-item-action <?= $isIngresarLote ? 'active' : ''; ?>">
                        Ingresar lote
                    </a>

                    <a href="<?= BASE_URL; ?>/modules/usuarios/index.php"
                       class="list-group-item list-group-item-action <?= $isUsuarios ? 'active' : ''; ?>">
                        Usuarios
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>