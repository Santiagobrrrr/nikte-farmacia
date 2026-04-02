<?php
$allowed_roles = ['administradora'];
require_once __DIR__ . '/../../includes/role_check.php';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

        <div class="col-12 col-md-9 col-lg-10">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h1 class="mb-3">Usuarios</h1>
                    <p>Bienvenida admin</p>
                    <p>Aún nada</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>