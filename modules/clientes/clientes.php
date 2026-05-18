<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';

$success = $_SESSION['cliente_success'] ?? '';
$error = $_SESSION['cliente_error'] ?? '';

unset($_SESSION['cliente_success'], $_SESSION['cliente_error']);

try {
    $pdo = getPDO();

    // GUARDAR / ACTUALIZAR
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar'])) {

        $idCliente = (int) ($_POST['id_cliente'] ?? 0);

        $nombre = trim($_POST['nombre'] ?? '');
        $nit = trim($_POST['nit'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');

        if ($nombre === '') {
            $_SESSION['cliente_error'] = 'El nombre es obligatorio.';
            header('Location: ' . BASE_URL . '/modules/clientes/clientes.php');
            exit;
        }

        // ACTUALIZAR
        if ($idCliente > 0) {

            $stmt = $pdo->prepare("
                UPDATE cliente
                SET
                    nombre = :nombre,
                    nit = :nit,
                    telefono = :telefono,
                    direccion = :direccion
                WHERE id_cliente = :id_cliente
            ");

            $stmt->execute([
                'nombre' => $nombre,
                'nit' => $nit !== '' ? $nit : null,
                'telefono' => $telefono !== '' ? $telefono : null,
                'direccion' => $direccion !== '' ? $direccion : null,
                'id_cliente' => $idCliente
            ]);

            $_SESSION['cliente_success'] = 'Cliente actualizado correctamente.';

        } else {

            // INSERTAR
            $stmt = $pdo->prepare("
                INSERT INTO cliente
                (nombre, nit, telefono, direccion)
                VALUES
                (:nombre, :nit, :telefono, :direccion)
            ");

            $stmt->execute([
                'nombre' => $nombre,
                'nit' => $nit !== '' ? $nit : null,
                'telefono' => $telefono !== '' ? $telefono : null,
                'direccion' => $direccion !== '' ? $direccion : null
            ]);

            $_SESSION['cliente_success'] = 'Cliente registrado correctamente.';
        }

        header('Location: ' . BASE_URL . '/modules/clientes/clientes.php');
        exit;
    }

    // ELIMINAR
    if (isset($_GET['eliminar'])) {

        $idCliente = (int) $_GET['eliminar'];

        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM venta
            WHERE id_cliente = :id_cliente
        ");

        $stmt->execute([
            'id_cliente' => $idCliente
        ]);

        $ventas = (int) $stmt->fetchColumn();

        if ($ventas > 0) {

            $_SESSION['cliente_error'] =
                'No se puede eliminar un cliente con ventas registradas.';

        } else {

            $stmt = $pdo->prepare("
                DELETE FROM cliente
                WHERE id_cliente = :id_cliente
            ");

            $stmt->execute([
                'id_cliente' => $idCliente
            ]);

            $_SESSION['cliente_success'] =
                'Cliente eliminado correctamente.';
        }

        header('Location: ' . BASE_URL . '/modules/clientes/clientes.php');
        exit;
    }

    // LISTADO
    $stmt = $pdo->query("
        SELECT
            c.id_cliente,
            c.nombre,
            c.nit,
            c.telefono,
            c.direccion,
            COUNT(v.id_venta) AS ventas_realizadas
        FROM cliente c
        LEFT JOIN venta v
            ON v.id_cliente = c.id_cliente
        GROUP BY
            c.id_cliente,
            c.nombre,
            c.nit,
            c.telefono,
            c.direccion
        ORDER BY c.id_cliente DESC
    ");

    $clientes = $stmt->fetchAll();

} catch (Throwable $e) {

    $clientes = [];
    $error = 'Ocurrió un error al cargar clientes.';
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid py-4">

    <div class="row">

        <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

        <div class="col-12 col-md-9 col-lg-10">

            <!-- TITULO -->
            <div class="mb-4">
                <h1 class="mb-1 text-dark fw-bold">
                    Gestión de Clientes
                </h1>

                <p class="text-muted">
                    Administración de clientes registrados.
                </p>
            </div>

            <!-- ALERTAS -->
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- FORMULARIO -->
            <div class="card shadow-sm border-0 mb-4">

                <div class="card-body">

                    <h4
                        class="mb-4 text-dark border-bottom pb-2"
                        id="formTitle">
                        Nuevo Cliente
                    </h4>

                    <form
                        method="POST"
                        action="<?= BASE_URL; ?>/modules/clientes/clientes.php">

                        <input
                            type="hidden"
                            name="id_cliente"
                            id="id_cliente">

                        <div class="row g-3">

                            <!-- NOMBRE -->
                            <div class="col-md-6">
                                <label class="form-label">
                                    Nombre *
                                </label>

                                <input
                                    type="text"
                                    name="nombre"
                                    id="nombre"
                                    class="form-control"
                                    required>
                            </div>

                            <!-- NIT -->
                            <div class="col-md-6">
                                <label class="form-label">
                                    NIT
                                </label>

                                <input
                                    type="text"
                                    name="nit"
                                    id="nit"
                                    class="form-control">
                            </div>

                            <!-- TELEFONO -->
                            <div class="col-md-6">
                                <label class="form-label">
                                    Teléfono
                                </label>

                                <input
                                    type="text"
                                    name="telefono"
                                    id="telefono"
                                    class="form-control">
                            </div>

                            <!-- DIRECCION -->
                            <div class="col-12">
                                <label class="form-label">
                                    Dirección
                                </label>

                                <textarea
                                    name="direccion"
                                    id="direccion"
                                    rows="3"
                                    class="form-control"></textarea>
                            </div>

                        </div>

                        <!-- BOTONES -->
                        <div class="mt-4 d-flex gap-2">

                            <button
                                type="submit"
                                name="guardar"
                                id="btnGuardar"
                                class="btn btn-success">

                                Guardar Cliente
                            </button>

                            <button
                                type="button"
                                id="btnCancelarEdicion"
                                class="btn btn-outline-secondary d-none">

                                Cancelar
                            </button>

                        </div>

                    </form>

                </div>

            </div>

            <!-- TABLA -->
            <div class="card shadow-sm border-0">

                <div class="card-body">

                    <h4 class="mb-4 text-dark">
                        Lista de Clientes
                    </h4>

                    <?php if (empty($clientes)): ?>

                        <div class="alert alert-warning">
                            No hay clientes registrados.
                        </div>

                    <?php else: ?>

                        <div class="table-responsive">

                            <table class="table table-hover align-middle">

                                <thead class="table-light">

                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>NIT</th>
                                        <th>Teléfono</th>
                                        <th>Dirección</th>
                                        <th>Ventas</th>
                                        <th width="200">Acciones</th>
                                    </tr>

                                </thead>

                                <tbody>

                                    <?php foreach ($clientes as $cliente): ?>

                                        <tr>

                                            <td>
                                                <?= $cliente['id_cliente'] ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars($cliente['nombre']) ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars($cliente['nit'] ?? '') ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars($cliente['telefono'] ?? '') ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars($cliente['direccion'] ?? '') ?>
                                            </td>

                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?= $cliente['ventas_realizadas'] ?>
                                                </span>
                                            </td>

                                            <td>

                                                <!-- EDITAR -->
                                                <button
                                                    type="button"
                                                    class="btn btn-warning btn-sm btn-editar-cliente"

                                                    data-id="<?= $cliente['id_cliente'] ?>"

                                                    data-nombre="<?= htmlspecialchars($cliente['nombre'], ENT_QUOTES) ?>"

                                                    data-nit="<?= htmlspecialchars($cliente['nit'] ?? '', ENT_QUOTES) ?>"

                                                    data-telefono="<?= htmlspecialchars($cliente['telefono'] ?? '', ENT_QUOTES) ?>"

                                                    data-direccion="<?= htmlspecialchars($cliente['direccion'] ?? '', ENT_QUOTES) ?>">

                                                    Editar
                                                </button>

                                                <!-- ELIMINAR -->
                                                <?php if ((int)$cliente['ventas_realizadas'] === 0): ?>

                                                    <a
                                                        href="<?= BASE_URL; ?>/modules/clientes/clientes.php?eliminar=<?= $cliente['id_cliente'] ?>"
                                                        class="btn btn-danger btn-sm"
                                                        onclick="return confirm('¿Eliminar cliente?')">

                                                        Eliminar
                                                    </a>

                                                <?php else: ?>

                                                    <button
                                                        class="btn btn-secondary btn-sm"
                                                        disabled>

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

        document.getElementById('id_cliente').value =
            this.dataset.id;

        document.getElementById('nombre').value =
            this.dataset.nombre;

        document.getElementById('nit').value =
            this.dataset.nit;

        document.getElementById('telefono').value =
            this.dataset.telefono;

        document.getElementById('direccion').value =
            this.dataset.direccion;

        document.getElementById('formTitle').innerText =
            'Editar Cliente';

        document.getElementById('btnGuardar').innerText =
            'Actualizar Cliente';

        document.getElementById('btnCancelarEdicion')
            .classList.remove('d-none');

        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
});

document.getElementById('btnCancelarEdicion')
.addEventListener('click', function () {

    document.getElementById('id_cliente').value = '';
    document.getElementById('nombre').value = '';
    document.getElementById('nit').value = '';
    document.getElementById('telefono').value = '';
    document.getElementById('direccion').value = '';

    document.getElementById('formTitle').innerText =
        'Nuevo Cliente';

    document.getElementById('btnGuardar').innerText =
        'Guardar Cliente';

    this.classList.add('d-none');
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>