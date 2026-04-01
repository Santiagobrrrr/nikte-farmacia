<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/session.php';

if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . '/modules/auth/login.php');
    exit;
}