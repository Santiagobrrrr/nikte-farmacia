<?php
require_once __DIR__ . '/../../config/config.php';
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

$descuentoOld = isset($old['descuento_porcentaje']) ? (float) $old['descuento_porcentaje'] : 0;
$motivoDescuentoOld = $old['motivo_descuento'] ?? '';
$maxDescuento = defined('MAX_DESCUENTO_PORCENTAJE') ? (float) MAX_DESCUENTO_PORCENTAJE : 20;

try {
    $pdo = getPDO();

    $sql = "
        SELECT
            p.id_producto,
            p.nombre,
            p.presentacion,
            p.precio_venta,
            p.requiere_receta,
            COALESCE(SUM(
                CASE
                    WHEN l.fecha_vencimiento >= CURDATE() THEN l.cantidad_actual
                    ELSE 0
                END
            ), 0) AS stock_total
        FROM producto p
        LEFT JOIN lote l ON l.id_producto = p.id_producto
        WHERE p.activo = 1
        GROUP BY p.id_producto, p.nombre, p.presentacion, p.precio_venta, p.requiere_receta
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

<style>
    .producto-suggestions {
        z-index: 30;
        max-height: 240px;
        overflow-y: auto;
    }

    .venta-table td,
    .venta-table th {
        vertical-align: middle;
    }

    .venta-total-box {
        font-size: 2.3rem;
        font-weight: 700;
        color: #198754;
        line-height: 1;
    }

    .venta-section-title {
        font-size: 1.35rem;
        font-weight: 600;
        margin-bottom: 1rem;
    }

    .venta-card {
        border: 0;
        box-shadow: 0 .125rem .35rem rgba(0,0,0,.08);
    }

    .venta-footer-box {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: .75rem;
    }

    .mini-label {
        color: #6c757d;
        font-size: .95rem;
    }
</style>

<div class="container-fluid py-4">
    <div class="row">
        <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

        <div class="col-12 col-md-9 col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="mb-1">Nueva venta</h1>
                    <p class="text-muted mb-0">
                        Punto de venta para registrar productos, validar stock y generar comprobante.
                    </p>
                </div>

                <a href="<?= BASE_URL ?>/modules/ventas/index.php" class="btn btn-secondary">
                    Volver
                </a>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="<?= BASE_URL ?>/modules/ventas/action.php" id="form-venta">

                <!-- 1. RESUMEN / DATOS DE VENTA -->
                <div class="card venta-card mb-3">
                    <div class="card-body">
                        <div class="venta-section-title">Resumen de venta</div>

                        <div class="row g-3">
                            <div class="col-12 col-md-6 col-lg-4">
                                <label class="form-label">Cliente</label>
                                <input
                                    type="text"
                                    name="nombre_cliente"
                                    class="form-control"
                                    placeholder="Consumidor final"
                                    value="<?= htmlspecialchars($old['nombre_cliente'] ?? '') ?>">
                            </div>

                            <div class="col-12 col-md-6 col-lg-3">
                                <label class="form-label">NIT</label>
                                <input
                                    type="text"
                                    name="nit_cliente"
                                    class="form-control"
                                    placeholder="CF o NIT del cliente"
                                    value="<?= htmlspecialchars($old['nit_cliente'] ?? '') ?>">
                            </div>

                            <div class="col-12 col-md-6 col-lg-2">
                                <label class="form-label">Método de pago</label>
                                <select name="metodo_pago" class="form-select">
                                    <?php $metodoOld = $old['metodo_pago'] ?? 'efectivo'; ?>
                                    <option value="efectivo" <?= $metodoOld === 'efectivo' ? 'selected' : ''; ?>>Efectivo</option>
                                    <option value="tarjeta" <?= $metodoOld === 'tarjeta' ? 'selected' : ''; ?>>Tarjeta</option>
                                    <option value="transferencia" <?= $metodoOld === 'transferencia' ? 'selected' : ''; ?>>Transferencia</option>
                                </select>
                            </div>

                            <div class="col-6 col-md-3 col-lg-1">
                                <label class="form-label">Productos</label>
                                <input type="text" id="cantidad-items-view" class="form-control" value="0" readonly>
                            </div>

                            <div class="col-6 col-md-3 col-lg-2">
                                <label class="form-label">Total actual</label>
                                <input type="text" id="total-venta-view" class="form-control" value="Q 0.00" readonly>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. BÚSQUEDA DE PRODUCTOS -->
                <div class="card venta-card mb-3">
                    <div class="card-body">
                        <div class="venta-section-title">Búsqueda de productos</div>

                        <div class="row g-2 align-items-end">
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
                                    class="list-group position-absolute w-100 shadow-sm producto-suggestions d-none">
                                </div>
                            </div>

                            <div class="col-12 col-md-4 col-lg-2">
                                <label class="form-label">Cantidad</label>
                                <input type="number" id="input-cantidad" class="form-control" min="1" value="1">
                            </div>

                            <div class="col-12 col-md-4 col-lg-2">
                                <label class="form-label">Precio</label>
                                <input type="text" id="input-precio" class="form-control" readonly>
                            </div>

                            <div class="col-12 col-md-4 col-lg-2">
                                <label class="form-label">Stock</label>
                                <input type="text" id="input-stock" class="form-control" readonly>
                            </div>

                            <div class="col-12 col-lg-1 d-grid">
                                <button type="button" id="btn-agregar" class="btn btn-primary">
                                    Agregar
                                </button>
                            </div>
                        </div>

                        <div id="receta-warning" class="alert alert-warning mt-3 d-none mb-0">
                            Este producto requiere receta médica. Verifique la receta antes de completar la venta.
                        </div>
                    </div>
                </div>

                <!-- 3. TABLA DE PRODUCTOS -->
                <div class="card venta-card mb-3">
                    <div class="card-body">
                        <div class="venta-section-title">Productos agregados</div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover venta-table mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Producto</th>
                                        <th width="130">Cantidad</th>
                                        <th width="150">Precio</th>
                                        <th width="150">Subtotal</th>
                                        <th width="110">Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-productos">
                                    <tr id="fila-vacia">
                                        <td colspan="5" class="text-center text-muted">
                                            No hay productos agregados.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-light border mt-3 mb-0">
                            El precio se toma automáticamente desde el inventario y no puede modificarse manualmente.
                        </div>
                    </div>
                </div>

                <!-- 4. DESCUENTO AUTORIZADO -->
                <div class="card venta-card mb-3">
                    <div class="card-body">
                        <div class="venta-section-title">Descuento autorizado</div>

                        <div class="row g-3">
                            <div class="col-12 col-md-3">
                                <label class="form-label">Descuento (%)</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="<?= htmlspecialchars((string) $maxDescuento); ?>"
                                    name="descuento_porcentaje"
                                    id="descuento_porcentaje"
                                    class="form-control"
                                    value="<?= htmlspecialchars((string) $descuentoOld); ?>">
                                <small class="text-muted">
                                    Máximo permitido: <?= number_format($maxDescuento, 2); ?>%
                                </small>
                            </div>

                            <div class="col-12 col-md-3">
                                <label class="form-label">Clave de autorización</label>
                                <input
                                    type="password"
                                    name="clave_descuento"
                                    id="clave_descuento"
                                    class="form-control"
                                    placeholder="Requerida si hay descuento">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Motivo del descuento</label>
                                <input
                                    type="text"
                                    name="motivo_descuento"
                                    id="motivo_descuento"
                                    class="form-control"
                                    maxlength="150"
                                    value="<?= htmlspecialchars($motivoDescuentoOld); ?>"
                                    placeholder="Ejemplo: promoción autorizada">
                            </div>
                        </div>

                        <small class="text-muted d-block mt-2">
                            Si el descuento es 0%, no se necesita clave ni motivo. La validación final se realiza al guardar la venta.
                        </small>
                    </div>
                </div>

                <!-- 5. TOTAL Y CIERRE -->
                <div class="venta-footer-box p-3">
                    <div class="row align-items-center g-3">
                        <div class="col-12 col-lg-6">
                            <div class="row g-2">
                                <div class="col-12">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Subtotal</span>
                                        <strong>Q <span id="subtotal-venta">0.00</span></strong>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Descuento</span>
                                        <strong class="text-danger">- Q <span id="descuento-venta">0.00</span></strong>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="mini-label mb-1">Total final</div>
                                    <div class="venta-total-box">
                                        Q <span id="total-venta">0.00</span>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <small class="text-muted">
                                        Revise los productos agregados y el descuento antes de guardar la venta.
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <div id="productos-hidden"></div>

                            <div class="d-flex flex-column flex-md-row gap-2 justify-content-lg-end">
                                <button type="submit" id="btn-guardar-venta" class="btn btn-success px-4">
                                    Guardar venta
                                </button>

                                <a href="<?= BASE_URL ?>/modules/ventas/index.php" class="btn btn-outline-secondary px-4">
                                    Cancelar
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
const catalogoProductos = <?= json_encode(
    $productos,
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
); ?>;

let productosVenta = <?= json_encode(
    $itemsJs,
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
); ?>;

let productoSeleccionado = null;

const searchInput = document.getElementById('search-producto');
const suggestionsBox = document.getElementById('suggestions-producto');
const cantidadInput = document.getElementById('input-cantidad');
const precioInput = document.getElementById('input-precio');
const stockInput = document.getElementById('input-stock');
const recetaWarning = document.getElementById('receta-warning');
const tbody = document.getElementById('tbody-productos');
const hiddenContainer = document.getElementById('productos-hidden');
const totalVentaEl = document.getElementById('total-venta');
const subtotalVentaEl = document.getElementById('subtotal-venta');
const descuentoVentaEl = document.getElementById('descuento-venta');
const descuentoInput = document.getElementById('descuento_porcentaje');
const claveDescuentoInput = document.getElementById('clave_descuento');
const motivoDescuentoInput = document.getElementById('motivo_descuento');
const maxDescuento = <?= json_encode($maxDescuento); ?>;
const totalVentaViewEl = document.getElementById('total-venta-view');
const cantidadItemsEl = document.getElementById('cantidad-items-view');
const guardarBtn = document.getElementById('btn-guardar-venta');

function normalizarTexto(texto) {
    return (texto || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');
}

function escapeHtml(texto) {
    return String(texto || '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function getProductoLabel(producto) {
    return producto.nombre + (producto.presentacion ? ' - ' + producto.presentacion : '');
}

function limpiarSeleccionProducto() {
    productoSeleccionado = null;
    precioInput.value = '';
    stockInput.value = '';
    recetaWarning.classList.add('d-none');
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

        const nombre = document.createElement('div');
        nombre.innerHTML = '<strong>' + escapeHtml(producto.nombre) + '</strong>';

        const detalle = document.createElement('small');
        detalle.className = 'text-muted';
        detalle.textContent =
            (producto.presentacion || 'Sin presentación') +
            ' | Stock: ' + producto.stock_total +
            ' | Q' + parseFloat(producto.precio_venta).toFixed(2) +
            ((parseInt(producto.requiere_receta, 10) === 1) ? ' | Requiere receta' : '');

        item.appendChild(nombre);
        item.appendChild(detalle);

        item.addEventListener('click', function () {
            productoSeleccionado = producto;
            searchInput.value = getProductoLabel(producto);
            precioInput.value = 'Q ' + parseFloat(producto.precio_venta).toFixed(2);
            stockInput.value = producto.stock_total;

            if (parseInt(producto.requiere_receta, 10) === 1) {
                recetaWarning.classList.remove('d-none');
            } else {
                recetaWarning.classList.add('d-none');
            }

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
    searchInput.focus();
});

function calcularSubtotalBruto() {
    return productosVenta.reduce((acumulado, producto) => {
        return acumulado + producto.subtotal;
    }, 0);
}

function obtenerDescuentoPorcentaje() {
    let descuento = parseFloat(descuentoInput?.value || 0);

    if (isNaN(descuento) || descuento < 0) {
        descuento = 0;
    }

    if (descuento > maxDescuento) {
        descuento = maxDescuento;
        descuentoInput.value = maxDescuento;
    }

    return descuento;
}

function actualizarTotales() {
    const subtotal = calcularSubtotalBruto();
    const descuentoPorcentaje = obtenerDescuentoPorcentaje();
    const descuentoMonto = subtotal * (descuentoPorcentaje / 100);
    const totalFinal = subtotal - descuentoMonto;

    if (subtotalVentaEl) {
        subtotalVentaEl.textContent = subtotal.toFixed(2);
    }

    if (descuentoVentaEl) {
        descuentoVentaEl.textContent = descuentoMonto.toFixed(2);
    }

    if (totalVentaEl) {
        totalVentaEl.textContent = totalFinal.toFixed(2);
    }

    if (totalVentaViewEl) {
        totalVentaViewEl.value = 'Q ' + totalFinal.toFixed(2);
    }
}

function renderTabla() {
    if (productosVenta.length === 0) {
        tbody.innerHTML = '<tr id="fila-vacia"><td colspan="5" class="text-center text-muted">No hay productos agregados.</td></tr>';
        hiddenContainer.innerHTML = '';
        cantidadItemsEl.value = '0';

        if (subtotalVentaEl) {
            subtotalVentaEl.textContent = '0.00';
        }

        if (descuentoVentaEl) {
            descuentoVentaEl.textContent = '0.00';
        }

        if (totalVentaEl) {
            totalVentaEl.textContent = '0.00';
        }

        if (totalVentaViewEl) {
            totalVentaViewEl.value = 'Q 0.00';
        }

        return;
    }

    let html = '';
    let hiddenHtml = '';
    let cantidadItems = 0;

    productosVenta.forEach((producto, index) => {
        cantidadItems += producto.cantidad;

        html += `
            <tr>
                <td>${escapeHtml(producto.nombre)}</td>
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
    cantidadItemsEl.value = cantidadItems;

    actualizarTotales();
}

function quitarProducto(index) {
    productosVenta.splice(index, 1);
    renderTabla();
}

window.quitarProducto = quitarProducto;

if (descuentoInput) {
    descuentoInput.addEventListener('input', actualizarTotales);
}

document.getElementById('form-venta').addEventListener('submit', function (e) {
    if (productosVenta.length === 0) {
        e.preventDefault();
        alert('Debes agregar al menos un producto.');
        return;
    }

    if (!hiddenContainer || hiddenContainer.querySelectorAll('input').length === 0) {
        e.preventDefault();
        alert('Los productos no se prepararon correctamente para guardar. Vuelve a agregar el producto.');
        return;
    }

    const descuento = obtenerDescuentoPorcentaje();

    if (descuento > 0) {
        if (!claveDescuentoInput || claveDescuentoInput.value.trim() === '') {
            e.preventDefault();
            alert('Debes ingresar la clave de autorización para aplicar descuento.');
            return;
        }

        if (!motivoDescuentoInput || motivoDescuentoInput.value.trim() === '') {
            e.preventDefault();
            alert('Debes ingresar el motivo del descuento.');
            return;
        }
    }

    guardarBtn.disabled = true;
    guardarBtn.textContent = 'Guardando venta...';
});

renderTabla();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>