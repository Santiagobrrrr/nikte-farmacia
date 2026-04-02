<?php
require_once __DIR__ . '/../../includes/auth_check.php';

$access_error = $_SESSION['access_error'] ?? '';
unset($_SESSION['access_error']);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

        <div class="col-md-9 col-lg-10">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h1 class="mb-3">Dashboard</h1>

                    <?php if (!empty($access_error)): ?>
                        <div class="alert alert-warning">
                            <?= htmlspecialchars($access_error); ?>
                        </div>
                    <?php endif; ?>

                    <p><strong>Bienvenido:</strong> <?= htmlspecialchars(currentUserName()); ?></p>
                    <p><strong>Rol:</strong> <?= htmlspecialchars(currentRole()); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>