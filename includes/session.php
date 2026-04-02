<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool
{
    return isset($_SESSION['usuario_id']);
}

function currentUserName(): string
{
    return $_SESSION['nombre_usuario'] ?? '';
}

function currentRole(): string
{
    return $_SESSION['rol_nombre'] ?? '';
}