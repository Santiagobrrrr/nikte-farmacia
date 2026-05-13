<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

$pdo = getPDO();

/* =========================
   GUARDAR O ACTUALIZAR
========================= */

if (isset($_POST['guardar'])) {

    $id = $_POST['id_cliente'] ?? '';

    $nombre = trim($_POST['nombre']);
    $telefono = trim($_POST['telefono']);
    $direccion = trim($_POST['direccion']);

    // EDITAR CLIENTE
    if (!empty($id)) {

        $sql = "
        UPDATE Cliente
        SET nombre = ?, telefono = ?, direccion = ?
        WHERE id_cliente = ?
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            $nombre,
            $telefono,
            $direccion,
            $id
        ]);

    } else {

        // NUEVO CLIENTE
        $sql = "
        INSERT INTO Cliente(nombre, telefono, direccion)
        VALUES (?, ?, ?)
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            $nombre,
            $telefono,
            $direccion
        ]);
    }

    header("Location: clientes.php");
    exit;
}

/* =========================
   ELIMINAR CLIENTE
========================= */

if (isset($_GET['eliminar'])) {

    $id = $_GET['eliminar'];

    $sql = "DELETE FROM Cliente WHERE id_cliente = ?";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([$id]);

    header("Location: clientes.php");
    exit;
}

/* =========================
   OBTENER CLIENTES
========================= */

$sqlClientes = "
SELECT 
    c.*,
    COUNT(v.id_venta) AS compras
FROM Cliente c
LEFT JOIN Venta v ON c.id_cliente = v.id_cliente
GROUP BY c.id_cliente
ORDER BY c.id_cliente DESC
";

$clientes = $pdo->query($sqlClientes)->fetchAll();

?>

<!DOCTYPE html>
<html lang="es">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Clientes</title>

<!-- BOOTSTRAP -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

body{
    background: linear-gradient(135deg,#e6f4ea,#ffffff);
}

/* HEADER */
.page-header{
    background:white;
    padding:20px;
    border-radius:12px;
    margin-bottom:20px;
    box-shadow:0px 5px 15px rgba(0,0,0,0.1);
}

h1{
    color:#198754;
}

/* CARD */
.card-custom{
    background:white;
    border-radius:12px;
    margin-bottom:20px;
    box-shadow:0px 5px 15px rgba(0,0,0,0.1);
}

.card-header-custom{
    background:#198754;
    color:white;
    padding:15px;
    font-weight:bold;
    border-radius:12px 12px 0 0;
}

/* FORM */
.form-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:20px;
    padding:20px;
}

.form-group{
    display:flex;
    flex-direction:column;
}

.form-group.full{
    grid-column:1/-1;
}

input,
textarea{
    padding:10px;
    border-radius:8px;
    border:1px solid #ccc;
}

input:focus,
textarea:focus{
    outline:none;
    border-color:#198754;
}

/* BOTONES */
.btn-verde{
    background:#198754;
    color:white;
}

.btn-verde:hover{
    background:#146c43;
    color:white;
}

/* TABLA */
.table thead{
    background:#198754;
    color:white;
}

.table tbody tr:hover{
    background:#e6f4ea;
}

/* ANIMACIÓN */
.fade-in{
    animation:fadeIn .5s ease;
}

@keyframes fadeIn{
    from{
        opacity:0;
        transform:translateY(10px);
    }
    to{
        opacity:1;
        transform:translateY(0);
    }
}

</style>

</head>

<body>

<div class="container-fluid">
<div class="row">

<!-- SIDEBAR -->
<?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

<!-- CONTENIDO -->
<div class="col-md-9 fade-in">

    <!-- HEADER -->
    <div class="page-header">
        <h1>Gestión de Clientes</h1>
    </div>

    <!-- FORMULARIO -->
    <div class="card-custom">

        <div class="card-header-custom">
            Nuevo / Editar Cliente
        </div>

        <form method="POST">

            <!-- ID OCULTO -->
            <input 
                type="hidden" 
                name="id_cliente" 
                id="id_cliente">

            <div class="form-grid">

                <div class="form-group">
                    <label>Nombre</label>

                    <input 
                        type="text"
                        name="nombre"
                        id="nombre"
                        required>
                </div>

                <div class="form-group">
                    <label>Teléfono</label>

                    <input 
                        type="text"
                        name="telefono"
                        id="telefono">
                </div>

                <div class="form-group full">
                    <label>Dirección</label>

                    <textarea
                        name="direccion"
                        id="direccion"></textarea>
                </div>

            </div>

            <div class="p-3 text-end">

                <button
                    type="submit"
                    name="guardar"
                    id="btnGuardar"
                    class="btn btn-verde">

                    Guardar Cliente

                </button>

            </div>

        </form>

    </div>

    <!-- TABLA -->
    <div class="card-custom">

        <div class="card-header-custom">
            Lista de Clientes
        </div>

        <div class="table-responsive p-3">

            <table class="table table-hover">

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Teléfono</th>
                        <th>Dirección</th>
                        <th>Compras</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>

                <?php foreach($clientes as $cliente): ?>

                    <tr>

                        <td>
                            <?= $cliente['id_cliente'] ?>
                        </td>

                        <td>
                            <?= $cliente['nombre'] ?>
                        </td>

                        <td>
                            <?= $cliente['telefono'] ?>
                        </td>

                        <td>
                            <?= $cliente['direccion'] ?>
                        </td>

                        <td>
                            <?= $cliente['compras'] ?>
                        </td>

                        <td>

                            <!-- EDITAR -->
                            <button
                                type="button"
                                class="btn btn-warning btn-sm"

                                onclick="editarCliente(
                                    '<?= $cliente['id_cliente'] ?>',
                                    '<?= htmlspecialchars($cliente['nombre']) ?>',
                                    '<?= htmlspecialchars($cliente['telefono']) ?>',
                                    '<?= htmlspecialchars($cliente['direccion']) ?>'
                                )">

                                ✏️

                            </button>

                            <!-- ELIMINAR -->
                            <a
                                href="?eliminar=<?= $cliente['id_cliente'] ?>"
                                class="btn btn-danger btn-sm"
                                onclick="return confirm('¿Eliminar cliente?')">

                                🗑️

                            </a>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>
</div>
</div>

<!-- JS -->
<script>

function editarCliente(id, nombre, telefono, direccion){

    // ID
    document.getElementById('id_cliente').value = id;

    // INPUTS
    document.getElementById('nombre').value = nombre;
    document.getElementById('telefono').value = telefono;
    document.getElementById('direccion').value = direccion;

    // CAMBIAR TEXTO BOTÓN
    document.getElementById('btnGuardar').innerText = "Actualizar Cliente";

    // SCROLL ARRIBA
    window.scrollTo({
        top:0,
        behavior:'smooth'
    });
}

</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>