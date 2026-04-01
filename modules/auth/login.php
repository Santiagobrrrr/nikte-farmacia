<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/session.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/modules/auth/login.php');
    exit;
}

$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?= APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
    <div class="card shadow p-4" style="width: 100%; max-width: 420px;">
        <h2 class="text-center mb-3"><?= APP_NAME; ?></h2>
        <p class="text-center text-muted">Inicio de sesión</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="<?= BASE_URL; ?>/modules/auth/action.php" method="POST">
            <div class="mb-3">
                <label class="form-label">Usuario</label>
                <input type="text" name="usuario" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Contraseña</label>
                <input type="password" name="contrasena" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-success w-100">Ingresar</button>
        </form>
    </div>
</div>

</body>
</html>