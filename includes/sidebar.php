<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/session.php';

$currentPage = $_SERVER['PHP_SELF'] ?? '';
?>

<div class="col-12 col-md-3 col-lg-2 mb-3">
    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="card-title">Menú</h5>

            <div class="list-group">
                <a
                    href="<?= BASE_URL; ?>/modules/dashboard/index.php"
                    class="list-group-item list-group-item-action <?= str_contains($currentPage, '/modules/dashboard/index.php') ? 'active' : ''; ?>">
                    Dashboard
                </a>

                <a
                    href="<?= BASE_URL; ?>/modules/productos/index.php"
                    class="list-group-item list-group-item-action <?= str_contains($currentPage, '/modules/productos/index.php') ? 'active' : ''; ?>">
                    Productos
                </a>

                <?php if (currentRole() === 'administradora'): ?>
                    <a
                        href="<?= BASE_URL; ?>/modules/usuarios/index.php"
                        class="list-group-item list-group-item-action <?= str_contains($currentPage, '/modules/usuarios/index.php') ? 'active' : ''; ?>">
                        Usuarios
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>