<?php
require_once __DIR__ . '/../../config/config.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Clientes</title>

<!-- ✅ BOOTSTRAP -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
/* 🌿 Fondo */
body {
    background: linear-gradient(135deg, #e6f4ea, #ffffff);
}

/* 🟢 Header */
.page-header {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0px 5px 15px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

h1 {
    color: #198754;
}

/* 📦 Cards */
.card-custom {
    background: white;
    border-radius: 12px;
    box-shadow: 0px 5px 15px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.card-header-custom {
    background: #198754;
    color: white;
    padding: 15px;
    border-radius: 12px 12px 0 0;
    font-weight: bold;
}

/* 🧾 Form */
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    padding: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group.full {
    grid-column: 1 / -1;
}

input, textarea {
    padding: 10px;
    border-radius: 8px;
    border: 1px solid #ccc;
}

input:focus, textarea:focus {
    border-color: #198754;
    outline: none;
}

/* 🟢 Botones */
.btn-verde {
    background: #198754;
    color: white;
}
.btn-verde:hover {
    background: #146c43;
}

.btn-sec {
    background: #6c757d;
    color: white;
}

/* 📊 Tabla */
.table thead {
    background: #198754;
    color: white;
}

.table tbody tr:hover {
    background: #e6f4ea;
}

/* 🎬 Animación */
.fade-in {
    animation: fadeIn 0.5s ease-in-out;
}
@keyframes fadeIn {
    from {opacity:0; transform: translateY(10px);}
    to {opacity:1; transform: translateY(0);}
}
</style>
</head>

<body>

<div class="container-fluid">
<div class="row">

<!-- ✅ SIDEBAR -->
<?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

<!-- ✅ CONTENIDO -->
<div class="col-md-9 fade-in">

    <!-- HEADER -->
    <div class="page-header">
        <h1>Gestión de Clientes</h1>
    </div>

    <!-- FORMULARIO -->
    <div class="card-custom">
        <div class="card-header-custom">Nuevo / Editar Cliente</div>

        <form id="formCliente">
            <div class="form-grid">
                <div class="form-group">
                    <label>Nombre</label>
                    <input type="text" id="nombre" required>
                </div>

                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="text" id="telefono">
                </div>

                <div class="form-group full">
                    <label>Dirección</label>
                    <textarea id="direccion"></textarea>
                </div>
            </div>

            <div class="p-3 text-end">
                <button type="submit" class="btn btn-verde">Guardar</button>
                <button type="reset" class="btn btn-sec">Limpiar</button>
            </div>
        </form>
    </div>

    <!-- TABLA -->
    <div class="card-custom">
        <div class="card-header-custom">Lista de Clientes</div>

        <div class="table-responsive p-3">
            <table class="table">
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
                <tbody id="listaClientes">

                    <?php
                    $clientes = [
                        ['1', 'María González', '555-1234', 'Av. Principal 456', '12'],
                        ['2', 'Juan Pérez', '555-5678', 'Calle Secundaria 789', '8'],
                    ];

                    foreach ($clientes as $c):
                    ?>

                    <tr>
                        <td><?= $c[0] ?></td>
                        <td><?= $c[1] ?></td>
                        <td><?= $c[2] ?></td>
                        <td><?= $c[3] ?></td>
                        <td><?= $c[4] ?></td>
                        <td>
                            <button class="btn btn-warning btn-sm">✏️</button>
                            <button class="btn btn-danger btn-sm">🗑️</button>
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

<!-- ✅ JS BOOTSTRAP -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.getElementById('formCliente').addEventListener('submit', function(e){
    e.preventDefault();

    const nombre = document.getElementById('nombre').value;
    if(!nombre){
        alert('Nombre requerido');
        return;
    }

    const tabla = document.getElementById('listaClientes');

    tabla.insertAdjacentHTML('afterbegin', `
        <tr>
            <td>Nuevo</td>
            <td>${nombre}</td>
            <td>-</td>
            <td>-</td>
            <td>0</td>
            <td>
                <button class="btn btn-warning btn-sm">✏️</button>
                <button class="btn btn-danger btn-sm">🗑️</button>
            </td>
        </tr>
    `);

    this.reset();
});
</script>

</body>
</html>