<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/session.php';

session_unset();
session_destroy();

header('Location: ' . BASE_URL . '/modules/auth/login.php');
exit;