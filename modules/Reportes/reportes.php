<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - Farmacia</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', sans-serif; 
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            min-height: 100vh; padding: 20px;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        header { 
            background: white; padding: 25px; border-radius: 15px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.2); margin-bottom: 30px;
            display: flex; justify-content: space-between; align-items: center;
        }
        h1 { color: #333; }
        .btn { 
            padding: 12px 24px; border: none; border-radius: 8px; 
            text-decoration: none; cursor: pointer; font-weight: bold;
            transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-primary { background: #007bff; color: white; }
        .btn-primary:hover { background: #0056b3; transform: translateY(-2px); }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #545b62; }
        .reportes-grid { 
            display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); 
            gap: 20px; margin-bottom: 30px;
        }
        .reporte-card { 
            background: white; padding: 30px; border-radius: 15px; 
            text-decoration: none; text-align: center; box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transition: all 0.3s; color: #333;
        }
        .reporte-card:hover { 
            transform: translateY(-10px); box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        .reporte-card .icono { 
            font-size: 3em; margin-bottom: 15px; color: #667eea;
        }
        .card { 
            background: white; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); 
            overflow: hidden; display: none;
        }
        .card.mostrar { display: block; }
        .card h2, .card h3 { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; padding: 25px; margin: 0;
        }
        .reporte-header { 
            display: flex; justify-content: space-between; align-items: center; 
            padding: 20px 30px; background: #f8f9fa;
        }
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; font-weight: bold; color: #333; }
        tr:hover { background: #f8f9fa; }
        .precio { font-weight: bold; color: #28a745; font-size: 1.1em; }
        .warning { background: #fff3cd !important; }
        .urgente { background: #f8d7da !important; color: #721c24; }
        .stats { display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap; }
        .stat-card { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; padding: 20px; border-radius: 10px; flex: 1; min-width: 200px;
            text-align: center;
        }
        @media (max-width: 768px) { 
            .reportes-grid { grid-template-columns: 1fr; }
            .reporte-header { flex-direction: column; gap: 15px; text-align: center; }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1><i class="fas fa-chart-bar"></i> Dashboard de Reportes</h1>
            <a href="clientes.php" class="btn btn-secondary">
                <i class="fas fa-users"></i> Gestión Clientes
            </a>
        </header>

        <!-- Reportes Rápidos -->
        <div class="reportes-grid">
            <a href="#" onclick="mostrarReporte('ventas')" class="reporte-card">
                <div class="icono"><i class="fas fa-shopping-cart"></i></div>
                <h3>Ventas Recientes</h3>
            </a>
            <a href="#" onclick="mostrarReporte('compras')" class="reporte-card">
                <div class="icono"><i class="fas fa-truck"></i></div>
                <h3>Compras</h3>
            </a>
            <a href="#" onclick="mostrarReporte('stock_bajo')" class="reporte-card">
                <div class="icono"><i class="fas fa-exclamation-triangle"></i></div>
                <h3>Stock Bajo</h3>
            </a>
            <a href="#" onclick="mostrarReporte('por_vencer')" class="reporte-card">
                <div class="icono"><i class="fas fa-calendar-times"></i></div>
                <h3>Por Vencer</h3>
            </a>
            <a href="#" onclick="mostrarReporte('clientes')" class="reporte-card">
                <div class="icono"><i class="fas fa-users"></i></div>
                <h3>Clientes Top</h3>
            </a>
        </div>

        <!-- Reporte de Ventas -->
        <div id="reporte-ventas" class="card">
            <div class="reporte-header">
                <h2><i class="fas fa-shopping-cart"></i> Ventas Recientes (15 registros)</h2>
                <div>
                    <button onclick="imprimir('ventas')" class="btn btn-primary">
                        <i class="fas fa-print"></i> Imprimir
                    </button>
                </div>
            </div>
            <div class="stats">
                <div class="stat-card">
                    <div style="font-size: 2em;">$1,245.50</div>
                    <div>Total Hoy</div>
                </div>
                <div class="stat-card">
                    <div style="font-size: 2em;">4</div>
                    <div>Ventas Hoy</div>
                </div>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr><th>ID</th><th>Fecha/Hora</th><th>Vendedor</th><th>Cliente</th><th>Total</th><th>Pago</th></tr>
                    </thead>
                    <tbody id="tbody-ventas"></tbody>
                </table>
            </div>
        </div>

        <!-- Reporte de Compras -->
        <div id="reporte-compras" class="card">
            <div class="reporte-header">
                <h2><i class="fas fa-truck"></i> Compras Recientes (8 registros)</h2>
                <div>
                    <button onclick="imprimir('compras')" class="btn btn-primary">Imprimir</button>
                </div>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr><th>ID</th><th>Fecha</th><th>Proveedor</th><th>Usuario</th><th>Total</th></tr>
                    </thead>
                    <tbody id="tbody-compras"></tbody>
                </table>
            </div>
        </div>

        <!-- Reporte Stock Bajo -->
        <div id="reporte-stock_bajo" class="card">
            <div class="reporte-header">
                <h2><i class="fas fa-exclamation-triangle"></i> Productos con Stock Bajo (5 productos)</h2>
                <div><button onclick="imprimir('stock_bajo')" class="btn btn-primary">Imprimir</button></div>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr><th>Producto</th><th>Precio Venta</th><th>Stock Actual</th><th>Acción</th></tr>
                    </thead>
                    <tbody id="tbody-stock"></tbody>
                </table>
            </div>
        </div>

        <!-- Reporte Productos por Vencer -->
        <div id="reporte-por_vencer" class="card">
            <div class="reporte-header">
                <h2><i class="fas fa-calendar-times"></i> Productos por Vencer (30 días) (12 lotes)</h2>
                <div><button onclick="imprimir('por_vencer')" class="btn btn-primary">Imprimir</button></div>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr><th>Producto</th><th>Lote</th><th>Vencimiento</th><th>Stock</th></tr>
                    </thead>
                    <tbody id="tbody-vencer"></tbody>
                </table>
            </div>
        </div>

        <!-- Reporte Clientes Top -->
        <div id="reporte-clientes" class="card">
            <div class="reporte-header">
                <h2><i class="fas fa-users"></i> Clientes Frecuentes (Top 10)</h2>
                <div><button onclick="imprimir('clientes')" class="btn btn-primary">Imprimir</button></div>
            </div>
            <div class="stats">
                <div class="stat-card">
                    <div style="font-size: 2em;">$12,450</div>
                    <div>Total Vendido</div>
                </div>
                <div class="stat-card">
                    <div style="font-size: 2em;">45</div>
                    <div>Total Ventas</div>
                </div>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr><th>Cliente</th><th>Teléfono</th><th>Ventas</th><th>Total Gastado</th></tr>
                    </thead>
                    <tbody id="tbody-clientes"></tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // DATOS SIMULADOS (basados en tu estructura de BD)
        const datos = {
            ventas: [
                {id: 101, fecha: '2024-01-15 10:30', vendedor: 'Ana López', cliente: 'María González', total: 245.50, pago: 'Tarjeta'},
                {id: 102, fecha: '2024-01-15 11:15', vendedor: 'Carlos Ruiz', cliente: 'Juan Pérez', total: 156.00, pago: 'Efectivo'},
                {id: 103, fecha: '2024-01-15 14:20', vendedor: 'Ana López', cliente: 'Ana Martínez', total: 389.75, pago: 'Tarjeta'},
                {id: 104, fecha: '2024-01-15 16:45', vendedor: 'Carlos Ruiz', cliente: 'Pedro Sánchez', total: 89.99, pago: 'Efectivo'},
                {id: 105, fecha: '2024-01-15 18:10', vendedor: 'Ana López', cliente: 'María González', total: 210.25, pago: 'Efectivo'}
            ],
            compras: [
                {id: 201, fecha: '2024-01-14', proveedor: 'FarmaCorp', usuario: 'Ana López', total: 12500.00},
                {id: 202, fecha: '2024-01-13', proveedor: 'MediPlus', usuario: 'Carlos Ruiz', total: 8900.50},
                {id: 203, fecha: '2024-01-12', proveedor: 'SaludTotal', usuario: 'Ana López', total: 4500.75}
            ],
            stock_bajo: [
                {producto: 'Paracetamol 500mg', precio: 2.50, stock: 3},
                {producto: 'Ibuprofeno 400mg', precio: 3.20, stock: 1},
                {producto: 'Amoxicilina 500mg', precio: 5.75, stock: 4},
                {producto: 'Vitamina C 1000mg', precio: 4.99, stock: 2}
            ],
            por_vencer: [
                {producto: 'Paracetamol 500mg', lote: 'LOT001', vence: '2024-02-10', stock: 15},
                {producto: 'Ibuprofeno 400mg', lote: 'LOT002', vence: '2024-02-05', stock: 8},
                {producto: 'Vitamina C', lote: 'LOT003', vence: '2024-02-08', stock: 22},
                {producto: 'Aspirina', lote: 'LOT004', vence: '2024-02-12', stock: 5}
            ],
            clientes: [
                {nombre: 'María González', telefono: '555-1234', ventas: 12, total: 2450.75},
                {nombre: 'Juan Pérez', telefono: '555-5678', ventas: 8, total: 1560.30},
                {nombre: 'Ana Martínez', telefono: '555-9012', ventas: 15, total: 3890.50}
            ]
        };

        function mostrarReporte(tipo) {
            // Ocultar todos los reportes
            document.querySelectorAll('.card').forEach(card => card.classList.remove('mostrar'));
            
            // Mostrar el reporte seleccionado
            const reporte = document.getElementById(`reporte-${tipo.replace('_', '-')}`);
            reporte.classList.add('mostrar');
            
            // Renderizar datos
            renderizarDatos(tipo);
        }

        function renderizarDatos(tipo) {
            const tbodyMap = {
                ventas: 'tbody-ventas',
                compras: 'tbody-compras', 
                'stock_bajo': 'tbody-stock',
                'por_vencer': 'tbody-vencer',
                clientes: 'tbody-clientes'
            };

            const tbody = document.getElementById(tbodyMap[tipo]);
            const datosReporte = datos[tipo];
            
            tbody.innerHTML = '';
            
            datosReporte.forEach(fila => {
                let html = '';
                if (tipo === 'ventas') {
                    html = `
                        <tr>
                            <td>${fila.id}</td>
                            <td>${new Date(fila.fecha).toLocaleString('es-ES')}</td>
                            <td>${fila.vendedor}</td>
                            <td>${fila.cliente}</td>
                            <td class="precio">$${fila.total.toFixed(2)}</td>
                            <td>${fila.pago}</td>
                        </tr>
                    `;
                } else if (tipo === 'compras') {
                    html = `
                        <tr>
                            <td>${fila.id}</td>
                            <td>${new Date(fila.fecha).toLocaleDateString('es-ES')}</td>
                            <td>${fila.proveedor}</td>
                            <td>${fila.usuario}</td>
                            <td class="precio">$${fila.total.toFixed(2)}</td>
                        </tr>
                    `;
                } else if (tipo === 'stock_bajo') {
                    html = `
                        <tr class="warning">
                            <td>${fila.producto}</td>
                            <td class="precio">$${fila.precio}</td>
                            <td><strong style="color: #dc3545;">${fila.stock}</strong></td>
                            <td><button class="btn btn-primary btn-sm">🛒 Comprar</button></td>
                        </tr>
                    `;
                } else if (tipo === 'por_vencer') {
                    const dias = Math.ceil((new Date(fila.vence) - new Date()) / (1000 * 60 * 60 * 24));
                    const clase = dias <= 7 ? 'urgente' : 'warning';
                    html = `
                        <tr class="${clase}">
                            <td>${fila.producto}</td>
                            <td>${fila.lote}</td>
                            <td>${new Date(fila.vence).toLocaleDateString('es-ES')}</td>
                            <td>${fila.stock}</td>
                        </tr>
                    `;
                } else if (tipo === 'clientes') {
                    html = `
                        <tr>
                            <td>${fila.nombre}</td>
                            <td>${fila.telefono}</td>
                            <td>${fila.ventas}</td>
                            <td class="precio">$${fila.total.toFixed(2)}</td>
                        </tr>
                    `;
                }
                tbody.innerHTML += html;
            });
        }

        function imprimir(tipo) {
            window.print();
        }

        // Inicializar mostrando primer reporte
        mostrarReporte('ventas');
    </script>
</body>
</html>