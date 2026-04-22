
Copiar

<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';
 
$error = $_SESSION['venta_error'] ?? '';
unset($_SESSION['venta_error']);
 
$productos = [];
 
try {
    $pdo = getPDO();
 
    $productos = $pdo->query("
        SELECT 
            p.id_producto,
            p.nombre,
            p.precio_venta,
            COALESCE(SUM(l.cantidad_actual), 0) AS stock_total
        FROM producto p
        LEFT JOIN lote l ON l.id_producto = p.id_producto AND l.cantidad_actual > 0
        WHERE p.activo = 1
        GROUP BY p.id_producto
        ORDER BY p.nombre
    ")->fetchAll();
 
} catch (Throwable $e) {
    $error = 'Error al cargar datos: ' . $e->getMessage();
}
?>
 
<div class="container-fluid py-4">
    <div class="row">
        <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
 
        <div class="col-12 col-md-9 col-lg-10">
            <div class="card shadow-sm">
                <div class="card-body">
 
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h1 class="mb-0">Nueva venta</h1>
                        <a href="<?= BASE_URL ?>/modules/ventas/index.php" class="btn btn-secondary">Volver</a>
                    </div>
 
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
 
                    <form method="POST" action="<?= BASE_URL ?>/modules/ventas/action.php" id="form-venta">
 
                        <div class="row mb-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Nombre del cliente *</label>
                                <input type="text" name="nombre_cliente" class="form-control" placeholder="Ingresa el nombre del cliente" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Método de pago</label>
                                <input type="text" class="form-control" value="Efectivo" readonly>
                                <input type="hidden" name="metodo_pago" value="efectivo">
                            </div>
                        </div>
 
                        <hr>
                        <h5 class="mb-3">Agregar productos</h5>
 
                        <div class="row g-2 align-items-end mb-3">
                            <div class="col-12 col-md-5">
                                <label class="form-label">Producto</label>
                                <select id="select-producto" class="form-select">
                                    <option value="">-- Seleccionar producto --</option>
                                    <?php foreach ($productos as $p): ?>
                                        <option value="<?= $p['id_producto'] ?>"
                                            data-nombre="<?= htmlspecialchars($p['nombre']) ?>"
                                            data-precio="<?= $p['precio_venta'] ?>"
                                            data-stock="<?= $p['stock_total'] ?>">
                                            <?= htmlspecialchars($p['nombre']) ?> — Stock: <?= $p['stock_total'] ?> — Q<?= number_format($p['precio_venta'], 2) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label">Cantidad</label>
                                <input type="number" id="input-cantidad" class="form-control" min="1" value="1">
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label">Precio unitario</label>
                                <input type="number" id="input-precio" class="form-control" step="0.01" min="0">
                            </div>
                            <div class="col-12 col-md-3">
                                <button type="button" id="btn-agregar" class="btn btn-primary w-100">Agregar</button>
                            </div>
                        </div>
 
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Producto</th>
                                        <th>Cantidad</th>
                                        <th>Precio unitario</th>
                                        <th>Subtotal</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-productos">
                                    <tr id="fila-vacia">
                                        <td colspan="5" class="text-center text-muted">No hay productos agregados.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
 
                        <div class="text-end fs-5 fw-bold mb-3">
                            Total: Q <span id="total-venta">0.00</span>
                        </div>
 
                        <div id="productos-hidden"></div>
 
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">Guardar venta</button>
                            <a href="<?= BASE_URL ?>/modules/ventas/index.php" class="btn btn-secondary">Cancelar</a>
                        </div>
 
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
 
<script>
let productos = [];
 
document.getElementById('select-producto').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    if (opt.value) {
        document.getElementById('input-precio').value = opt.dataset.precio;
    }
});
 
document.getElementById('btn-agregar').addEventListener('click', function() {
    const select = document.getElementById('select-producto');
    const opt = select.options[select.selectedIndex];
    const cantidad = parseInt(document.getElementById('input-cantidad').value);
    const precio = parseFloat(document.getElementById('input-precio').value);
 
    if (!opt.value) return alert('Selecciona un producto.');
    if (!cantidad || cantidad < 1) return alert('La cantidad debe ser mayor a 0.');
    if (isNaN(precio) || precio < 0) return alert('El precio no es válido.');
    if (cantidad > parseInt(opt.dataset.stock)) return alert('Stock insuficiente. Disponible: ' + opt.dataset.stock);
    if (productos.find(p => p.id === opt.value)) return alert('El producto ya fue agregado.');
 
    productos.push({
        id: opt.value,
        nombre: opt.dataset.nombre,
        cantidad: cantidad,
        precio: precio,
        subtotal: cantidad * precio
    });
 
    renderTabla();
    select.value = '';
    document.getElementById('input-cantidad').value = 1;
    document.getElementById('input-precio').value = '';
});
 
function renderTabla() {
    const tbody = document.getElementById('tbody-productos');
    const hidden = document.getElementById('productos-hidden');
 
    if (productos.length === 0) {
        tbody.innerHTML = '<tr id="fila-vacia"><td colspan="5" class="text-center text-muted">No hay productos agregados.</td></tr>';
        hidden.innerHTML = '';
        document.getElementById('total-venta').textContent = '0.00';
        return;
    }
 
    let html = '', hiddenHtml = '', total = 0;
 
    productos.forEach((p, i) => {
        total += p.subtotal;
        html += `<tr>
            <td>${p.nombre}</td>
            <td>${p.cantidad}</td>
            <td>Q ${p.precio.toFixed(2)}</td>
            <td>Q ${p.subtotal.toFixed(2)}</td>
            <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarProducto(${i})">Quitar</button></td>
        </tr>`;
        hiddenHtml += `
            <input type="hidden" name="productos[${i}][id_producto]" value="${p.id}">
            <input type="hidden" name="productos[${i}][cantidad]" value="${p.cantidad}">
            <input type="hidden" name="productos[${i}][precio_unitario]" value="${p.precio}">
            <input type="hidden" name="productos[${i}][subtotal]" value="${p.subtotal.toFixed(2)}">
        `;
    });
 
    tbody.innerHTML = html;
    hidden.innerHTML = hiddenHtml;
    document.getElementById('total-venta').textContent = total.toFixed(2);
}
 
function eliminarProducto(i) {
    productos.splice(i, 1);
    renderTabla();
}
 
document.getElementById('form-venta').addEventListener('submit', function(e) {
    if (productos.length === 0) {
        e.preventDefault();
        alert('Debes agregar al menos un producto.');
    }
});
</script>
 
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>