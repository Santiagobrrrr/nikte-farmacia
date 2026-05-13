<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';

$success = $_SESSION['cliente_success'] ?? '';
$error = $_SESSION['cliente_error'] ?? '';
unset($_SESSION['cliente_success'], $_SESSION['cliente_error']);

try {
    $pdo = getPDO();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar'])) {
        $idCliente = isset($_POST['id_cliente']) ? (int) $_POST['id_cliente'] : 0;
        $nombre = trim($_POST['nombre'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');

        if ($nombre === '') {
            $_SESSION['cliente_error'] = 'El nombre del cliente es obligatorio.';
            header('Location: ' . BASE_URL . '/modules/clientes/clientes.php');
            exit;
        }

        if ($idCliente > 0) {
            $stmt = $pdo->prepare("
                UPDATE cliente
                SET nombre = :nombre,
                    telefono = :telefono,
                    direccion = :direccion
                WHERE id_cliente = :id_cliente
            ");

            $stmt->execute([
                'nombre' => $nombre,
                'telefono' => $telefono !== '' ? $telefono : null,
                'direccion' => $direccion !== '' ? $direccion : null,
                'id_cliente' => $idCliente,
            ]);

            $_SESSION['cliente_success'] = 'Cliente actualizado correctamente.';
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO cliente (nombre, telefono, direccion)
                VALUES (:nombre, :telefono, :direccion)
            ");

            $stmt->execute([
                'nombre' => $nombre,
                'telefono' => $telefono !== '' ? $telefono : null,
                'direccion' => $direccion !== '' ? $direccion : null,
            ]);

            $_SESSION['cliente_success'] = 'Cliente registrado correctamente.';
        }

        header('Location: ' . BASE_URL . '/modules/clientes/clientes.php');
        exit;
    }

    if (isset($_GET['eliminar'])) {
        $idCliente = (int) $_GET['eliminar'];

        if ($idCliente > 0) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM venta 
                WHERE id_cliente = :id_cliente
            ");
            $stmt->execute(['id_cliente' => $idCliente]);
            $ventasCliente = (int) $stmt->fetchColumn();

            if ($ventasCliente > 0) {
                $_SESSION['cliente_error'] = 'No se puede eliminar un cliente que ya tiene ventas registradas.';
            } else {
                $stmt = $pdo->prepare("
                    DELETE FROM cliente 
                    WHERE id_cliente = :id_cliente
                ");
                $stmt->execute(['id_cliente' => $idCliente]);

                $_SESSION['cliente_success'] = 'Cliente eliminado correctamente.';
            }
        }

        header('Location: ' . BASE_URL . '/modules/clientes/clientes.php');
        exit;
    }

    $stmt = $pdo->query("
        SELECT
            c.id_cliente,
            c.nombre,
            c.telefono,
            c.direccion,
            COUNT(v.id_venta) AS ventas_realizadas
        FROM cliente c
        LEFT JOIN venta v ON v.id_cliente = c.id_cliente
        GROUP BY c.id_cliente, c.nombre, c.telefono, c.direccion
        ORDER BY c.id_cliente DESC
    ");

    $clientes = $stmt->fetchAll();

} catch (Throwable $e) {
    $clientes = [];
    $error = 'No se pudieron cargar los clientes.';
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

        <div class="col-12 col-md-9 col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="mb-1">Clientes</h1>
                    <p class="text-muted mb-0">
                        Gestión de clientes registrados en el sistema.
                    </p>
                </div>
            </div>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body">
                    <h4 class="mb-3" id="formTitle">Nuevo cliente</h4>

                    <form method="POST" action="<?= BASE_URL; ?>/modules/clientes/clientes.php">
                        <input type="hidden" name="id_cliente" id="id_cliente">

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Nombre *</label>
                                <input type="text" name="nombre" id="nombre" class="form-control" required>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Teléfono</label>
                                <input type="text" name="telefono" id="telefono" class="form-control">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Dirección</label>
                                <textarea name="direccion" id="direccion" class="form-control" rows="2"></textarea>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-3">
                            <button type="submit" name="guardar" id="btnGuardar" class="btn btn-success">
                                Guardar cliente
                            </button>

                            <button type="button" id="btnCancelarEdicion" class="btn btn-outline-secondary d-none">
                                Cancelar edición
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h4 class="mb-3">Listado de clientes</h4>

                    <?php if (empty($clientes)): ?>
                        <div class="alert alert-warning mb-0">
                            No hay clientes registrados.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Teléfono</th>
                                        <th>Dirección</th>
                                        <th>Ventas</th>
                                        <th width="190">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($clientes as $cliente): ?>
                                        <tr>
                                            <td><?= (int) $cliente['id_cliente']; ?></td>

                                            <td><?= htmlspecialchars($cliente['nombre']); ?></td>

                                            <td>
                                                <?= htmlspecialchars($cliente['telefono'] ?? ''); ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars($cliente['direccion'] ?? ''); ?>
                                            </td>

                                            <td>
                                                <span class="badge text-bg-light border">
                                                    <?= (int) $cliente['ventas_realizadas']; ?>
                                                </span>
                                            </td>

                                            <td>
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-primary btn-editar-cliente"
                                                    data-id="<?= (int) $cliente['id_cliente']; ?>"
                                                    data-nombre="<?= htmlspecialchars($cliente['nombre'], ENT_QUOTES); ?>"
                                                    data-telefono="<?= htmlspecialchars($cliente['telefono'] ?? '', ENT_QUOTES); ?>"
                                                    data-direccion="<?= htmlspecialchars($cliente['direccion'] ?? '', ENT_QUOTES); ?>">
                                                    Editar
                                                </button>

                                                <?php if ((int) $cliente['ventas_realizadas'] === 0): ?>
                                                    <a
                                                        href="<?= BASE_URL; ?>/modules/clientes/clientes.php?eliminar=<?= (int) $cliente['id_cliente']; ?>"
                                                        class="btn btn-sm btn-outline-danger"
                                                        onclick="return confirm('¿Deseas eliminar este cliente?');">
                                                        Eliminar
                                                    </a>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" disabled>
                                                        Con ventas
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
document.querySelectorAll('.btn-editar-cliente').forEach(button => {
    button.addEventListener('click', function () {
        document.getElementById('id_cliente').value = this.dataset.id;
        document.getElementById('nombre').value = this.dataset.nombre;
        document.getElementById('telefono').value = this.dataset.telefono;
        document.getElementById('direccion').value = this.dataset.direccion;

        document.getElementById('formTitle').textContent = 'Editar cliente';
        document.getElementById('btnGuardar').textContent = 'Actualizar cliente';
        document.getElementById('btnCancelarEdicion').classList.remove('d-none');

        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
});

document.getElementById('btnCancelarEdicion').addEventListener('click', function () {
    document.getElementById('id_cliente').value = '';
    document.getElementById('nombre').value = '';
    document.getElementById('telefono').value = '';
    document.getElementById('direccion').value = '';

    document.getElementById('formTitle').textContent = 'Nuevo cliente';
    document.getElementById('btnGuardar').textContent = 'Guardar cliente';
    this.classList.add('d-none');
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>