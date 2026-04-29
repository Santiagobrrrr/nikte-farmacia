<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';

$error = $_SESSION['venta_error'] ?? '';
unset($_SESSION['venta_error']);

$old = $_SESSION['venta_old'] ?? [];
unset($_SESSION['venta_old']);

$productos = [];
$productosMap = [];
$itemsJs = [];

try {
    $pdo = getPDO();

    $sql = "
        SELECT
            p.id_producto,
            p.nombre,
            p.presentacion,
            p.precio_venta,
            COALESCE(SUM(
                CASE
                    WHEN l.fecha_vencimiento >= CURDATE() THEN l.cantidad_actual
                    ELSE 0
                END
            ), 0) AS stock_total
        FROM producto p
        LEFT JOIN lote l ON l.id_producto = p.id_producto
        WHERE p.activo = 1
        GROUP BY p.id_producto, p.nombre, p.presentacion, p.precio_venta
        HAVING COALESCE(SUM(
            CASE
                WHEN l.fecha_vencimiento >= CURDATE() THEN l.cantidad_actual
                ELSE 0
            END
        ), 0) > 0
        ORDER BY p.nombre ASC
    ";

    $productos = $pdo->query($sql)->fetchAll();

    foreach ($productos as $producto) {
        $idProducto = (int) $producto['id_producto'];
        $label = $producto['nombre'];

        if (!empty($producto['presentacion'])) {
            $label .= ' - ' . $producto['presentacion'];
        }

        $productosMap[$idProducto] = [
            'label' => $label,
            'precio_venta' => (float) $producto['precio_venta'],
            'stock_total' => (int) $producto['stock_total'],
        ];
    }

    $itemsOld = $old['productos'] ?? [];

    if (is_array($itemsOld)) {
        foreach ($itemsOld as $item) {
            $idProducto = (int) ($item['id_producto'] ?? 0);
            $cantidad = (int) ($item['cantidad'] ?? 0);

            if ($idProducto > 0 && $cantidad > 0 && isset($productosMap[$idProducto])) {
                $precio = (float) $productosMap[$idProducto]['precio_venta'];

                $itemsJs[] = [
                    'id_producto' => $idProducto,
                    'nombre' => $productosMap[$idProducto]['label'],
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio,
                    'subtotal' => $cantidad * $precio,
                ];
            }
        }
    }

} catch (Throwable $e) {
    $error = 'Error al cargar datos: ' . $e->getMessage();
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

        <div class="col-12 col-md-9 col-lg-10">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h1 class="mb-1">Nueva venta</h1>
                            <p class="text-muted mb-0">Registro de venta con selección de productos y validación de stock disponible.</p>
                        </div>
                        <a href="<?= BASE_URL ?>/modules/ventas/index.php" class="btn btn-secondary">Volver</a>
                    </div>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form method="POST" action="<?= BASE_URL ?>/modules/ventas/action.php" id="form-venta">
                        <div class="row g-3 mb-4">
                            <div class="col-12 col-lg-8">
                                <label class="form-label">Nombre del cliente *</label>
                                <input
                                    type="text"
                                    name="nombre_cliente"
                                    class="form-control"
                                    placeholder="Ingresa el nombre del cliente"
                                    value="<?= htmlspecialchars($old['nombre_cliente'] ?? '') ?>"
                                    required>
                            </div>

                            <div class="col-12 col-lg-4">
                                <label class="form-label">Método de pago</label>
                                <input type="text" class="form-control" value="Efectivo" readonly>
                                <input type="hidden" name="metodo_pago" value="efectivo">
                            </div>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="mb-0">Agregar productos</h4>
                        </div>

                        <div class="row g-2 align-items-end mb-3">
                            <div class="col-12 col-lg-5 position-relative">
                                <label class="form-label">Producto</label>
                                <input
                                    type="text"
                                    id="search-producto"
                                    class="form-control"
                                    placeholder="Escribe para buscar producto"
                                    autocomplete="off">
                                <div
                                    id="suggestions-producto"
                                    class="list-group position-absolute w-100 shadow-sm d-none"
                                    style="z-index: 30; max-height: 220px; overflow-y: auto;">
                                </div>
                            </div>

                            <div class="col-12 col-md-4 col-lg-2">
                                <label class="form-label">Cantidad</label>
                                <input type="number" id="input-cantidad" class="form-control" min="1" value="1">
                            </div>

                            <div class="col-12 col-md-4 col-lg-2">
                                <label class="form-label">Precio unitario</label>
                                <input type="text" id="input-precio" class="form-control" readonly>
                            </div>

                            <div class="col-12 col-md-4 col-lg-1">
                                <label class="form-label">Stock</label>
                                <input type="text" id="input-stock" class="form-control" readonly>
                            </div>

                            <div class="col-12 col-lg-2">
                                <button type="button" id="btn-agregar" class="btn btn-primary w-100">
                                    Agregar
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Producto</th>
                                        <th width="130">Cantidad</th>
                                        <th width="160">Precio unitario</th>
                                        <th width="160">Subtotal</th>
                                        <th width="100"></th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-productos">
                                    <tr id="fila-vacia">
                                        <td colspan="5" class="text-center text-muted">No hay productos agregados.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="text-end fs-4 fw-bold mb-3">
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
const catalogoProductos = <?= json_encode(
    $productos,
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
) ?>;

let productosVenta = <?= json_encode(
    $itemsJs,
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
) ?>;

let productoSeleccionado = null;

const searchInput = document.getElementById('search-producto');
const suggestionsBox = document.getElementById('suggestions-producto');
const cantidadInput = document.getElementById('input-cantidad');
const precioInput = document.getElementById('input-precio');
const stockInput = document.getElementById('input-stock');
const tbody = document.getElementById('tbody-productos');
const hiddenContainer = document.getElementById('productos-hidden');
const totalVentaEl = document.getElementById('total-venta');

function normalizarTexto(texto) {
    return (texto || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');
}

function getProductoLabel(producto) {
    return producto.nombre + (producto.presentacion ? ' - ' + producto.presentacion : '');
}

function limpiarSeleccionProducto() {
    productoSeleccionado = null;
    precioInput.value = '';
    stockInput.value = '';
}

function renderSuggestions(query) {
    const q = normalizarTexto(query.trim());
    suggestionsBox.innerHTML = '';

    if (q === '') {
        suggestionsBox.classList.add('d-none');
        return;
    }

    const coincidencias = catalogoProductos
        .filter(p => normalizarTexto(getProductoLabel(p)).includes(q))
        .slice(0, 8);

    if (coincidencias.length === 0) {
        const div = document.createElement('div');
        div.className = 'list-group-item text-muted';
        div.textContent = 'No se encontraron coincidencias';
        suggestionsBox.appendChild(div);
        suggestionsBox.classList.remove('d-none');
        return;
    }

    coincidencias.forEach(producto => {
        const item = document.createElement('button');
        item.type = 'button';
        item.className = 'list-group-item list-group-item-action';
        item.innerHTML = `
            <div><strong>${producto.nombre}</strong></div>
            <small class="text-muted">${producto.presentacion || 'Sin presentación'} | Stock: ${producto.stock_total} | Q${parseFloat(producto.precio_venta).toFixed(2)}</small>
        `;

        item.addEventListener('click', function () {
            productoSeleccionado = producto;
            searchInput.value = getProductoLabel(producto);
            precioInput.value = 'Q ' + parseFloat(producto.precio_venta).toFixed(2);
            stockInput.value = producto.stock_total;
            suggestionsBox.classList.add('d-none');
            cantidadInput.focus();
        });

        suggestionsBox.appendChild(item);
    });

    suggestionsBox.classList.remove('d-none');
}

searchInput.addEventListener('input', function () {
    limpiarSeleccionProducto();
    renderSuggestions(this.value);
});

searchInput.addEventListener('focus', function () {
    renderSuggestions(this.value);
});

searchInput.addEventListener('blur', function () {
    setTimeout(() => suggestionsBox.classList.add('d-none'), 180);
});

document.getElementById('btn-agregar').addEventListener('click', function () {
    const cantidad = parseInt(cantidadInput.value, 10);

    if (!productoSeleccionado) {
        alert('Debes seleccionar un producto válido.');
        return;
    }

    if (!cantidad || cantidad < 1) {
        alert('La cantidad debe ser mayor a 0.');
        return;
    }

    if (cantidad > parseInt(productoSeleccionado.stock_total, 10)) {
        alert('Stock insuficiente. Disponible: ' + productoSeleccionado.stock_total);
        return;
    }

    const existente = productosVenta.findIndex(
        p => parseInt(p.id_producto, 10) === parseInt(productoSeleccionado.id_producto, 10)
    );

    if (existente !== -1) {
        const nuevaCantidad = productosVenta[existente].cantidad + cantidad;

        if (nuevaCantidad > parseInt(productoSeleccionado.stock_total, 10)) {
            alert('No puedes agregar más de lo disponible para ese producto.');
            return;
        }

        productosVenta[existente].cantidad = nuevaCantidad;
        productosVenta[existente].subtotal = nuevaCantidad * productosVenta[existente].precio_unitario;
    } else {
        productosVenta.push({
            id_producto: productoSeleccionado.id_producto,
            nombre: getProductoLabel(productoSeleccionado),
            cantidad: cantidad,
            precio_unitario: parseFloat(productoSeleccionado.precio_venta),
            subtotal: cantidad * parseFloat(productoSeleccionado.precio_venta)
        });
    }

    renderTabla();
    searchInput.value = '';
    cantidadInput.value = 1;
    limpiarSeleccionProducto();
});

function renderTabla() {
    if (productosVenta.length === 0) {
        tbody.innerHTML = '<tr id="fila-vacia"><td colspan="5" class="text-center text-muted">No hay productos agregados.</td></tr>';
        hiddenContainer.innerHTML = '';
        totalVentaEl.textContent = '0.00';
        return;
    }

    let html = '';
    let hiddenHtml = '';
    let total = 0;

    productosVenta.forEach((producto, index) => {
        total += producto.subtotal;

        html += `
            <tr>
                <td>${producto.nombre}</td>
                <td>${producto.cantidad}</td>
                <td>Q ${producto.precio_unitario.toFixed(2)}</td>
                <td>Q ${producto.subtotal.toFixed(2)}</td>
                <td>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="quitarProducto(${index})">
                        Quitar
                    </button>
                </td>
            </tr>
        `;

        hiddenHtml += `
            <input type="hidden" name="productos[${index}][id_producto]" value="${producto.id_producto}">
            <input type="hidden" name="productos[${index}][cantidad]" value="${producto.cantidad}">
        `;
    });

    tbody.innerHTML = html;
    hiddenContainer.innerHTML = hiddenHtml;
    totalVentaEl.textContent = total.toFixed(2);
}

function quitarProducto(index) {
    productosVenta.splice(index, 1);
    renderTabla();
}

window.quitarProducto = quitarProducto;

document.getElementById('form-venta').addEventListener('submit', function (e) {
    if (productosVenta.length === 0) {
        e.preventDefault();
        alert('Debes agregar al menos un producto.');
    }
});

renderTabla();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>