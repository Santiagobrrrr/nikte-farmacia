<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/session.php';
?>

<div class="col-12 col-md-3 col-lg-2 mb-3">
    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="card-title">Menú</h5>

            <div class="list-group">
                <a href="<?= BASE_URL; ?>/modules/dashboard/index.php" class="list-group-item list-group-item-action">
                    Dashboard
                </a>

                <?php if (currentRole() === 'administradora'): ?>
                    <a href="<?= BASE_URL; ?>/modules/usuarios/index.php" class="list-group-item list-group-item-action">
                        Usuarios
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>