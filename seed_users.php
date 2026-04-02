<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo = getPDO();
    $pdo->beginTransaction();

    function getOrCreateRole(PDO $pdo, string $nombre, string $descripcion): int
    {
        $stmt = $pdo->prepare("SELECT id_rol FROM rol WHERE nombre_rol = :nombre LIMIT 1");
        $stmt->execute(['nombre' => $nombre]);
        $role = $stmt->fetch();

        if ($role) {
            return (int) $role['id_rol'];
        }

        $stmt = $pdo->prepare("INSERT INTO rol (nombre_rol, descripcion) VALUES (:nombre, :descripcion)");
        $stmt->execute([
            'nombre' => $nombre,
            'descripcion' => $descripcion
        ]);

        return (int) $pdo->lastInsertId();
    }

    function createOrUpdateUser(PDO $pdo, string $nombre, string $usuario, string $plainPassword, int $idRol): void
    {
        $hash = password_hash($plainPassword, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("SELECT id_usuario FROM usuario WHERE usuario = :usuario LIMIT 1");
        $stmt->execute(['usuario' => $usuario]);
        $existing = $stmt->fetch();

        if ($existing) {
            $stmt = $pdo->prepare("
                UPDATE usuario
                SET nombre = :nombre, contrasena = :contrasena, id_rol = :id_rol
                WHERE id_usuario = :id_usuario
            ");
            $stmt->execute([
                'nombre' => $nombre,
                'contrasena' => $hash,
                'id_rol' => $idRol,
                'id_usuario' => $existing['id_usuario']
            ]);
            return;
        }

        $stmt = $pdo->prepare("
            INSERT INTO usuario (nombre, usuario, contrasena, id_rol)
            VALUES (:nombre, :usuario, :contrasena, :id_rol)
        ");
        $stmt->execute([
            'nombre' => $nombre,
            'usuario' => $usuario,
            'contrasena' => $hash,
            'id_rol' => $idRol
        ]);
    }

    $rolAdmin = getOrCreateRole($pdo, 'administradora', 'Acceso total al sistema');
    $rolVendedora = getOrCreateRole($pdo, 'vendedora', 'Acceso limitado al sistema');

    createOrUpdateUser($pdo, 'Admin Nikte', 'admin', '1234', $rolAdmin);
    createOrUpdateUser($pdo, 'Vendedora Nikte', 'vendedora', '1234', $rolVendedora);

    $pdo->commit();

    echo 'Usuarios de prueba creados/actualizados correctamente.';
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo 'Error: ' . $e->getMessage();
}