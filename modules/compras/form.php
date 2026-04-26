<?php
$allowed_roles = ['administradora'];
require_once __DIR__ . '/../../includes/role_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';

$error = $_SESSION['compra_error'] ?? '';
unset($_SESSION['compra_error']);

$old = $_SESSION['compra_old'] ?? [];
unset($_SESSION['compra_old']);

$proveedores = [];
$productos = [];
$productosMap = [];

try {
    $pdo = getPDO();

    $stmt = $pdo->query("SELECT id_proveedor, nombre FROM proveedor ORDER BY nombre ASC");
    $proveedores = $stmt->fetchAll();

    $stmt = $pdo->query("
        SELECT id_producto, nombre, presentacion
        FROM producto
        WHERE activo = 1
        ORDER BY nombre ASC
    ");
    $productos = $stmt->fetchAll();

    foreach ($productos as $producto) {
        $productosMap[(int) $producto['id_producto']] = $producto['nombre']
            . (!empty($producto['presentacion']) ? ' - ' . $producto['presentacion'] : '');
    }
} catch (Throwable $e) {
    $error = 'No se pudieron cargar los datos del formulario.';
}

$items = $old['items'] ?? [
    [
        'id_producto' => '',
        'codigo_lote' => '',
        'fecha_vencimiento' => '',
        'costo_unitario' => '',
        'cantidad' => '',
    ]
];
?>

<div class="container-fluid py-4">
    <div class="row">
        <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

        <div class="col-12 col-md-9 col-lg-10">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h1 class="mb-0">Nueva compra</h1>
                        <a href="<?= BASE_URL; ?>/modules/compras/index.php" class="btn btn-secondary">
                            Volver
                        </a>
                    </div>

                    <p class="text-muted">
                        Registro de compra con varios productos y sus respectivos lotes.
                    </p>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form action="<?= BASE_URL; ?>/modules/compras/action.php" method="POST" id="compraForm">
                        <div class="row mb-3">
                            <div class="col-12 col-md-6">
                                <label for="fecha_compra" class="form-label">Fecha de compra *</label>
                                <input
                                    type="date"
                                    id="fecha_compra"
                                    name="fecha_compra"
                                    class="form-control"
                                    value="<?= htmlspecialchars($old['fecha_compra'] ?? date('Y-m-d')); ?>"
                                    required>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="id_proveedor" class="form-label">Proveedor *</label>
                                <select id="id_proveedor" name="id_proveedor" class="form-select" required>
                                    <option value="">Seleccione un proveedor</option>
                                    <?php foreach ($proveedores as $proveedor): ?>
                                        <option
                                            value="<?= (int) $proveedor['id_proveedor']; ?>"
                                            <?= ((int) ($old['id_proveedor'] ?? 0) === (int) $proveedor['id_proveedor']) ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($proveedor['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="mb-0">Productos de la compra</h4>
                            <button type="button" class="btn btn-outline-success btn-sm" id="addItemBtn">
                                Agregar producto
                            </button>
                        </div>

                        <div id="itemsContainer">
                            <?php foreach ($items as $index => $item): ?>
                                <?php
                                $idProductoActual = (int) ($item['id_producto'] ?? 0);
                                $textoProductoActual = $idProductoActual > 0 && isset($productosMap[$idProductoActual])
                                    ? $productosMap[$idProductoActual]
                                    : '';
                                ?>
                                <div class="item-row border rounded p-3 mb-3" data-index="<?= $index; ?>">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <strong>Producto #<?= $index + 1; ?></strong>
                                        <button type="button" class="btn btn-outline-danger btn-sm remove-item">
                                            Quitar
                                        </button>
                                    </div>

                                    <div class="row">
                                        <div class="col-12 col-md-6 mb-3 position-relative">
                                            <label class="form-label">Producto *</label>

                                            <input
                                                type="hidden"
                                                name="items[<?= $index; ?>][id_producto]"
                                                class="producto-id"
                                                value="<?= htmlspecialchars((string) $idProductoActual); ?>">

                                            <input
                                                type="text"
                                                class="form-control producto-search"
                                                placeholder="Escribe para buscar un producto"
                                                autocomplete="off"
                                                value="<?= htmlspecialchars($textoProductoActual); ?>"
                                                required>

                                            <div class="list-group producto-suggestions position-absolute w-100 shadow-sm d-none"
                                                 style="z-index: 20; max-height: 220px; overflow-y: auto;"></div>
                                        </div>

                                        <div class="col-12 col-md-6 mb-3">
                                            <label class="form-label">Código de lote *</label>
                                            <input
                                                type="text"
                                                name="items[<?= $index; ?>][codigo_lote]"
                                                class="form-control"
                                                value="<?= htmlspecialchars($item['codigo_lote'] ?? ''); ?>"
                                                required>
                                        </div>

                                        <div class="col-12 col-md-4 mb-3">
                                            <label class="form-label">Fecha de vencimiento *</label>
                                            <input
                                                type="date"
                                                name="items[<?= $index; ?>][fecha_vencimiento]"
                                                class="form-control"
                                                value="<?= htmlspecialchars($item['fecha_vencimiento'] ?? ''); ?>"
                                                required>
                                        </div>

                                        <div class="col-12 col-md-4 mb-3">
                                            <label class="form-label">Costo unitario *</label>
                                            <input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                name="items[<?= $index; ?>][costo_unitario]"
                                                class="form-control costo-unitario"
                                                value="<?= htmlspecialchars($item['costo_unitario'] ?? ''); ?>"
                                                required>
                                        </div>

                                        <div class="col-12 col-md-4 mb-3">
                                            <label class="form-label">Cantidad *</label>
                                            <input
                                                type="number"
                                                min="1"
                                                name="items[<?= $index; ?>][cantidad]"
                                                class="form-control cantidad"
                                                value="<?= htmlspecialchars($item['cantidad'] ?? ''); ?>"
                                                required>
                                        </div>
                                    </div>

                                    <div class="text-end">
                                        <small class="text-muted">
                                            Subtotal: Q<span class="subtotal-item">0.00</span>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="alert alert-info">
                            Total estimado de compra: <strong>Q<span id="totalCompra">0.00</span></strong>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">Guardar compra</button>
                            <a href="<?= BASE_URL; ?>/modules/compras/index.php" class="btn btn-outline-secondary">
                                Cancelar
                            </a>
                        </div>
                    </form>

                    <template id="itemTemplate">
                        <div class="item-row border rounded p-3 mb-3" data-index="__INDEX__">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong>Producto #__NUM__</strong>
                                <button type="button" class="btn btn-outline-danger btn-sm remove-item">
                                    Quitar
                                </button>
                            </div>

                            <div class="row">
                                <div class="col-12 col-md-6 mb-3 position-relative">
                                    <label class="form-label">Producto *</label>

                                    <input type="hidden" name="items[__INDEX__][id_producto]" class="producto-id" value="">

                                    <input
                                        type="text"
                                        class="form-control producto-search"
                                        placeholder="Escribe para buscar un producto"
                                        autocomplete="off"
                                        required>

                                    <div class="list-group producto-suggestions position-absolute w-100 shadow-sm d-none"
                                         style="z-index: 20; max-height: 220px; overflow-y: auto;"></div>
                                </div>

                                <div class="col-12 col-md-6 mb-3">
                                    <label class="form-label">Código de lote *</label>
                                    <input type="text" name="items[__INDEX__][codigo_lote]" class="form-control" required>
                                </div>

                                <div class="col-12 col-md-4 mb-3">
                                    <label class="form-label">Fecha de vencimiento *</label>
                                    <input type="date" name="items[__INDEX__][fecha_vencimiento]" class="form-control" required>
                                </div>

                                <div class="col-12 col-md-4 mb-3">
                                    <label class="form-label">Costo unitario *</label>
                                    <input type="number" step="0.01" min="0" name="items[__INDEX__][costo_unitario]" class="form-control costo-unitario" required>
                                </div>

                                <div class="col-12 col-md-4 mb-3">
                                    <label class="form-label">Cantidad *</label>
                                    <input type="number" min="1" name="items[__INDEX__][cantidad]" class="form-control cantidad" required>
                                </div>
                            </div>

                            <div class="text-end">
                                <small class="text-muted">
                                    Subtotal: Q<span class="subtotal-item">0.00</span>
                                </small>
                            </div>
                        </div>
                    </template>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const productos = <?= json_encode($productos, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    const itemsContainer = document.getElementById('itemsContainer');
    const addItemBtn = document.getElementById('addItemBtn');
    const template = document.getElementById('itemTemplate');
    const totalCompraEl = document.getElementById('totalCompra');
    const compraForm = document.getElementById('compraForm');

    function normalizeText(text) {
        return (text || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }

    function getProductoLabel(producto) {
        return producto.nombre + (producto.presentacion ? ' - ' + producto.presentacion : '');
    }

    function updateLabels() {
        const rows = itemsContainer.querySelectorAll('.item-row');
        rows.forEach((row, index) => {
            const title = row.querySelector('strong');
            if (title) {
                title.textContent = 'Producto #' + (index + 1);
            }
        });
    }

    function calculateTotals() {
        let total = 0;

        document.querySelectorAll('.item-row').forEach(row => {
            const costoInput = row.querySelector('.costo-unitario');
            const cantidadInput = row.querySelector('.cantidad');
            const subtotalEl = row.querySelector('.subtotal-item');

            const costo = parseFloat(costoInput?.value || 0);
            const cantidad = parseInt(cantidadInput?.value || 0, 10);
            const subtotal = (isNaN(costo) ? 0 : costo) * (isNaN(cantidad) ? 0 : cantidad);

            if (subtotalEl) {
                subtotalEl.textContent = subtotal.toFixed(2);
            }

            total += subtotal;
        });

        totalCompraEl.textContent = total.toFixed(2);
    }

    function hideSuggestions(row) {
        const suggestions = row.querySelector('.producto-suggestions');
        if (suggestions) {
            suggestions.classList.add('d-none');
            suggestions.innerHTML = '';
        }
    }

    function renderSuggestions(row, query) {
        const input = row.querySelector('.producto-search');
        const hiddenInput = row.querySelector('.producto-id');
        const suggestions = row.querySelector('.producto-suggestions');

        if (!input || !hiddenInput || !suggestions) {
            return;
        }

        const normalizedQuery = normalizeText(query.trim());

        if (normalizedQuery === '') {
            suggestions.innerHTML = '';
            suggestions.classList.add('d-none');
            return;
        }

        const matches = productos.filter(producto => {
            const text = normalizeText(producto.nombre + ' ' + (producto.presentacion || ''));
            return text.includes(normalizedQuery);
        }).slice(0, 8);

        suggestions.innerHTML = '';

        if (matches.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'list-group-item text-muted';
            empty.textContent = 'No se encontraron coincidencias';
            suggestions.appendChild(empty);
            suggestions.classList.remove('d-none');
            return;
        }

        matches.forEach(producto => {
            const option = document.createElement('button');
            option.type = 'button';
            option.className = 'list-group-item list-group-item-action';
            option.innerHTML = `
                <div><strong>${producto.nombre}</strong></div>
                <small class="text-muted">${producto.presentacion || 'Sin presentación'}</small>
            `;

            option.addEventListener('click', function () {
                input.value = getProductoLabel(producto);
                hiddenInput.value = producto.id_producto;
                hideSuggestions(row);
            });

            suggestions.appendChild(option);
        });

        suggestions.classList.remove('d-none');
    }

    function bindRowEvents(row) {
        const removeBtn = row.querySelector('.remove-item');
        const costoInput = row.querySelector('.costo-unitario');
        const cantidadInput = row.querySelector('.cantidad');
        const input = row.querySelector('.producto-search');
        const hiddenInput = row.querySelector('.producto-id');

        if (removeBtn) {
            removeBtn.addEventListener('click', function () {
                const rows = itemsContainer.querySelectorAll('.item-row');
                if (rows.length > 1) {
                    row.remove();
                    updateLabels();
                    calculateTotals();
                }
            });
        }

        if (costoInput) {
            costoInput.addEventListener('input', calculateTotals);
        }

        if (cantidadInput) {
            cantidadInput.addEventListener('input', calculateTotals);
        }

        if (input && hiddenInput) {
            input.addEventListener('input', function () {
                hiddenInput.value = '';
                renderSuggestions(row, input.value);
            });

            input.addEventListener('focus', function () {
                renderSuggestions(row, input.value);
            });

            input.addEventListener('blur', function () {
                setTimeout(() => {
                    hideSuggestions(row);
                }, 180);
            });
        }
    }

    addItemBtn.addEventListener('click', function () {
        const index = itemsContainer.querySelectorAll('.item-row').length;
        let html = template.innerHTML;
        html = html.replaceAll('__INDEX__', index);
        html = html.replaceAll('__NUM__', index + 1);

        const wrapper = document.createElement('div');
        wrapper.innerHTML = html.trim();

        const row = wrapper.firstElementChild;
        itemsContainer.appendChild(row);
        bindRowEvents(row);
        updateLabels();
        calculateTotals();
    });

    compraForm.addEventListener('submit', function (e) {
        let hayError = false;

        document.querySelectorAll('.item-row').forEach(row => {
            const input = row.querySelector('.producto-search');
            const hiddenInput = row.querySelector('.producto-id');

            if (input && hiddenInput && hiddenInput.value.trim() === '') {
                input.classList.add('is-invalid');
                hayError = true;
            } else if (input) {
                input.classList.remove('is-invalid');
            }
        });

        if (hayError) {
            e.preventDefault();
            alert('Debes seleccionar un producto válido en cada fila de la compra.');
        }
    });

    document.querySelectorAll('.item-row').forEach(bindRowEvents);
    calculateTotals();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>