<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Clientes - Farmacia</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh; padding: 20px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        header { 
            background: white; padding: 25px; border-radius: 15px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;
        }
        h1 { color: #333; }
        .btn { 
            padding: 12px 24px; border: none; border-radius: 8px; 
            text-decoration: none; cursor: pointer; font-weight: bold;
            transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-primary { background: #28a745; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-warning { background: #ffc107; color: #333; }
        .btn-danger { background: #dc3545; color: white; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .btn-sm { padding: 6px 12px; font-size: 0.9em; }
        
        .card { 
            background: white; margin-bottom: 30px; border-radius: 15px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); overflow: hidden;
        }
        .card h3 { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; padding: 20px; margin: 0;
        }
        .form-grid { 
            display: grid; grid-template-columns: 1fr 1fr; gap: 20px; padding: 30px;
        }
        .form-group { display: flex; flex-direction: column; gap: 8px; }
        .form-group.full-width { grid-column: 1 / -1; }
        label { font-weight: bold; color: #555; }
        input, textarea { 
            padding: 12px; border: 2px solid #e1e5e9; border-radius: 8px; 
            font-size: 16px; transition: border-color 0.3s;
        }
        input:focus, textarea:focus { outline: none; border-color: #667eea; }
        textarea { resize: vertical; min-height: 80px; }
        .form-actions { padding: 0 30px 30px; text-align: right; }
        
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; font-weight: bold; color: #333; }
        tr:hover { background: #f8f9fa; }
        .precio { font-weight: bold; color: #28a745; }
        
        .alert { 
            background: #d4edda; color: #155724; padding: 15px 20px; 
            border-radius: 8px; margin-bottom: 20px; border: 1px solid #c3e6cb;
        }
        .reportes-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px; margin-bottom: 30px;
        }
        .reporte-card {
            background: white; padding: 30px; border-radius: 15px;
            text-decoration: none; text-align: center; color: #333;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1); transition: all 0.3s;
        }
        .reporte-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.2); }
        .reporte-card .icono { font-size: 3em; color: #667eea; margin-bottom: 15px; }
        .warning { background: #fff3cd !important; }
        
        @media (max-width: 768px) {
            .form-grid { grid-template-columns: 1fr; }
            header { flex-direction: column; gap: 15px; text-align: center; }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1><i class="fas fa-users"></i> Gestión de Clientes</h1>
            <a href="reportes.php" class="btn btn-secondary">
                <i class="fas fa-chart-bar"></i> Ver Reportes
            </a>
        </header>

        <!-- Formulario -->
        <div class="card">
            <h3><i class="fas fa-user-plus"></i> Nuevo / Editar Cliente</h3>
            <form id="formCliente">
                <div class="form-grid">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Nombre Completo:</label>
                        <input type="text" id="nombre" placeholder="Juan Pérez" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Teléfono:</label>
                        <input type="tel" id="telefono" placeholder="555-123-4567">
                    </div>
                    <div class="form-group full-width">
                        <label><i class="fas fa-map-marker-alt"></i> Dirección:</label>
                        <textarea id="direccion" placeholder="Calle Falsa 123, Colonia Centro"></textarea>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Cliente
                    </button>
                    <button type="reset" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Limpiar
                    </button>
                </div>
            </form>
        </div>

        <!-- Lista de Clientes (Datos de ejemplo) -->
        <div class="card">
            <h3><i class="fas fa-list"></i> Lista de Clientes <span class="badge">25 clientes</span></h3>
            <div class="table-container">
                <table>
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
                        // Datos de ejemplo
                        $clientes = [
                            ['1', 'María González', '555-1234', 'Av. Principal 456', '12'],
                            ['2', 'Juan Pérez', '555-5678', 'Calle Secundaria 789', '8'],
                            ['3', 'Ana López', '555-9012', 'Col. Centro 321', '5'],
                            ['4', 'Carlos Ramírez', '555-3456', 'Zona Industrial', '3'],
                            ['5', 'Laura Martínez', '555-7890', 'Residencial Norte', '15']
                        ];
                        
                        foreach ($clientes as $cliente):
                        ?>
                        <tr>
                            <td><?php echo $cliente[0]; ?></td>
                            <td><?php echo $cliente[1]; ?></td>
                            <td><?php echo $cliente[2]; ?></td>
                            <td><?php echo $cliente[3]; ?></td>
                            <td><span class="badge"><?php echo $cliente[4]; ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-warning" onclick="editarCliente(<?php echo $cliente[0]; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="eliminarCliente(<?php echo $cliente[0]; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Simular guardar cliente
        document.getElementById('formCliente').addEventListener('submit', function(e) {
            e.preventDefault();
            const nombre = document.getElementById('nombre').value;
            if (!nombre.trim()) {
                alert('¡El nombre es obligatorio!');
                return;
            }
            
            // Simular éxito
            alert(' Cliente guardado correctamente');
            this.reset();
            
            // Aquí agregarías el cliente a la tabla
            agregarClienteATabla(nombre);
        });

        function agregarClienteATabla(nombre) {
            const tbody = document.getElementById('listaClientes');
            const nuevaFila = `
                <tr>
                    <td>26</td>
                    <td>${nombre}</td>
                    <td>-</td>
                    <td>-</td>
                    <td><span class="badge">0</span></td>
                    <td>
                        <button class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            `;
            tbody.insertAdjacentHTML('afterbegin', nuevaFila);
        }

        function editarCliente(id) {
            alert(`Editando cliente ID: ${id}`);
            // Aquí cargarías los datos en el formulario
        }

        function eliminarCliente(id) {
            if (confirm(`¿Eliminar cliente ID ${id}?`)) {
                alert(` Cliente ${id} eliminado`);
            }
        }
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</body>
</html>