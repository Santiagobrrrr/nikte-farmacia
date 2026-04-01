<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/session.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | <?= APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="card shadow p-4">
        <h1 class="mb-3">Dashboard</h1>
        <p><strong>Bienvenido:</strong> <?= htmlspecialchars(currentUserName()); ?></p>
        <p><strong>Rol:</strong> <?= htmlspecialchars(currentRole()); ?></p>

        <?php if (currentRole() === 'administradora'): ?>
            <div class="alert alert-success mt-3">
                Bienvenida administradora.
            </div>
        <?php else: ?>
            <div class="alert alert-info mt-3">
                Bienvenida vendedora.
            </div>
        <?php endif; ?>

        <div class="mt-3">
            <a href="<?= BASE_URL; ?>/modules/auth/logout.php" class="btn btn-danger">Cerrar sesión</a>
        </div>
    </div>
</div>

</body>
</html>