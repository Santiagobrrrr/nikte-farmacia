<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['usuario_id']);
}

function currentUserName() {
    return $_SESSION['nombre_usuario'] ?? '';
}

function currentRole() {
    return $_SESSION['rol_nombre'] ?? '';
}