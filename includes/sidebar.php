<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/session.php';

$currentPage = $_SERVER['PHP_SELF'] ?? '';

$isDashboard = str_contains($currentPage, '/modules/dashboard/index.php');

$isProductos = str_contains($currentPage, '/modules/productos/')
    || str_contains($currentPage, '/modules/lotes/');

$isProveedores = str_contains($currentPage, '/modules/proveedores/');
$isCompras = str_contains($currentPage, '/modules/compras/');
$isVentas = str_contains($currentPage, '/modules/ventas/');
$isReportes = str_contains($currentPage, '/modules/reportes/');
$isUsuarios = str_contains($currentPage, '/modules/usuarios/');
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
                    Inventario
                </a>

                <a href="<?= BASE_URL; ?>/modules/proveedores/index.php"
                   class="list-group-item list-group-item-action <?= $isProveedores ? 'active' : ''; ?>">
                    Proveedores
                </a>

                <a href="<?= BASE_URL; ?>/modules/compras/index.php"
                    class="list-group-item list-group-item-action <?= $isCompras ? 'active' : ''; ?>">
                     Compras
                </a>

                <a href="#"
                   class="list-group-item list-group-item-action disabled">
                    Ventas
                </a>

                <a href="#"
                   class="list-group-item list-group-item-action disabled">
                    Reportes
                </a>

                <?php if (currentRole() === 'administradora'): ?>
                    <a href="<?= BASE_URL; ?>/modules/usuarios/index.php"
                       class="list-group-item list-group-item-action <?= $isUsuarios ? 'active' : ''; ?>">
                        Usuarios
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>