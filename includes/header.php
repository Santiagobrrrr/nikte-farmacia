<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/session.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-success shadow-sm">
    <div class="container-fluid">
        <span class="navbar-brand mb-0 h1"><?= APP_NAME; ?></span>

        <?php if (isLoggedIn()): ?>
            <div class="d-flex align-items-center gap-3 text-white">
                <span><?= htmlspecialchars(currentUserName()); ?> (<?= htmlspecialchars(currentRole()); ?>)</span>
                <a href="<?= BASE_URL; ?>/modules/auth/logout.php" class="btn btn-sm btn-outline-light">
                    Cerrar sesión
                </a>
            </div>
        <?php endif; ?>
    </div>
</nav>