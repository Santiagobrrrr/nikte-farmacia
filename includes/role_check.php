<?php
require_once __DIR__ . '/auth_check.php';

if (!isset($allowed_roles) || !is_array($allowed_roles)) {
    $allowed_roles = [];
}

if (!in_array(currentRole(), $allowed_roles, true)) {
    $_SESSION['access_error'] = 'Sin permisos necesarios para acceder a esta página.';
    header('Location: ' . BASE_URL . '/modules/dashboard/index.php');
    exit;
}