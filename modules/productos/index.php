<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';

$productos = [];
$error = '';
$success = $_SESSION['producto_success'] ?? '';
unset($_SESSION['producto_success']);

try {
    $pdo = getPDO();

    $sql = "SELECT
                p.id_producto,
                p.nombre,
                p.presentacion,
                p.descripcion,
                p.uso_terapeutico,
                p.precio_venta,
                p.stock_minimo,
                p.requiere_receta,
                COALESCE(SUM(l.cantidad_actual), 0) AS stock_actual
            FROM producto p
            LEFT JOIN lote l ON l.id_producto = p.id_producto
            GROUP BY
                p.id_producto,
                p.nombre,
                p.presentacion,
                p.descripcion,
                p.uso_terapeutico,
                p.precio_venta,
                p.stock_minimo,
                p.requiere_receta
            ORDER BY p.nombre ASC";

    $stmt = $pdo->query($sql);
    $productos = $stmt->fetchAll();
} catch (Throwable $e) {
    $error = 'No se pudo cargar el inventario.';
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

        <div class="col-12 col-md-9 col-lg-10">
            <div class="card shadow-sm">
                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h1 class="mb-0">Inventario</h1>

                        <?php if (currentRole() === 'administradora'): ?>
                            <a href="<?= BASE_URL; ?>/modules/productos/form.php" class="btn btn-success">
                                Nuevo producto
                            </a>
                        <?php endif; ?>
                    </div>

                    <p class="text-muted">
                        Consulta general del inventario de productos, existencias y alertas.
                    </p>

                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <a href="<?= BASE_URL; ?>/modules/productos/index.php" class="btn btn-outline-primary btn-sm">
                            Ver inventario
                        </a>

                        <a href="<?= BASE_URL; ?>/modules/productos/stock_bajo.php" class="btn btn-outline-warning btn-sm">
                            Stock bajo
                        </a>

                        <a href="<?= BASE_URL; ?>/modules/productos/por_vencer.php" class="btn btn-outline-danger btn-sm">
                            Inventario por vencer
                        </a>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-12 col-md-10">
                            <input
                                type="text"
                                id="liveSearch"
                                class="form-control"
                                placeholder="Buscar en tiempo real por nombre, presentación, descripción o uso terapéutico">
                        </div>

                        <div class="col-12 col-md-2">
                            <button type="button" id="clearSearch" class="btn btn-outline-secondary w-100">
                                Limpiar
                            </button>
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
                    <?php elseif (empty($productos)): ?>
                        <div class="alert alert-warning">
                            No hay productos registrados todavía.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Presentación</th>
                                        <th>Precio</th>
                                        <th>Stock mínimo</th>
                                        <th>Stock actual</th>
                                        <th>Estado</th>
                                        <th>Receta</th>
                                        <th width="220">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($productos as $producto): ?>
                                        <?php
                                        $stockActual = (int) $producto['stock_actual'];
                                        $stockMinimo = (int) $producto['stock_minimo'];

                                        if ($stockActual <= 0) {
                                            $estado = 'Agotado';
                                            $clase = 'danger';
                                        } elseif ($stockActual <= $stockMinimo) {
                                            $estado = 'Stock bajo';
                                            $clase = 'warning';
                                        } else {
                                            $estado = 'Disponible';
                                            $clase = 'success';
                                        }

                                        $textoBusqueda = mb_strtolower(
                                            ($producto['nombre'] ?? '') . ' ' .
                                            ($producto['presentacion'] ?? '') . ' ' .
                                            ($producto['descripcion'] ?? '') . ' ' .
                                            ($producto['uso_terapeutico'] ?? '')
                                        );
                                        ?>
                                        <tr
                                            class="inventario-row"
                                            data-search="<?= htmlspecialchars($textoBusqueda); ?>">
                                            <td><?= (int) $producto['id_producto']; ?></td>
                                            <td><?= htmlspecialchars($producto['nombre']); ?></td>
                                            <td><?= htmlspecialchars($producto['presentacion'] ?? ''); ?></td>
                                            <td>Q<?= number_format((float) $producto['precio_venta'], 2); ?></td>
                                            <td><?= $stockMinimo; ?></td>
                                            <td><?= $stockActual; ?></td>
                                            <td>
                                                <span class="badge text-bg-<?= $clase; ?>">
                                                    <?= $estado; ?>
                                                </span>
                                            </td>
                                            <td><?= (int) $producto['requiere_receta'] === 1 ? 'Sí' : 'No'; ?></td>
                                            <td>
                                                <a href="<?= BASE_URL; ?>/modules/lotes/index.php?id_producto=<?= (int) $producto['id_producto']; ?>" class="btn btn-sm btn-outline-dark mb-1">
                                                    Ver lotes
                                                </a>

                                                <?php if (currentRole() === 'administradora'): ?>
                                                    <a href="<?= BASE_URL; ?>/modules/productos/form.php?id=<?= (int) $producto['id_producto']; ?>" class="btn btn-sm btn-primary mb-1">
                                                        Editar
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div id="noResultsMessage" class="alert alert-warning mt-3 d-none">
                            No se encontraron productos con esa búsqueda.
                        </div>

                        <div class="alert alert-info mt-3 mb-0">
                            El stock actual se calcula sumando las existencias de todos los lotes del producto.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('liveSearch');
    const clearBtn = document.getElementById('clearSearch');
    const rows = document.querySelectorAll('.inventario-row');
    const noResultsMessage = document.getElementById('noResultsMessage');

    if (!input || !clearBtn || rows.length === 0 || !noResultsMessage) {
        return;
    }

    function normalizeText(text) {
        return text
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }

    function filterRows() {
        const query = normalizeText(input.value.trim());
        let visibleCount = 0;

        rows.forEach(row => {
            const searchText = normalizeText(row.dataset.search || '');

            if (query === '' || searchText.includes(query)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (visibleCount === 0) {
            noResultsMessage.classList.remove('d-none');
        } else {
            noResultsMessage.classList.add('d-none');
        }
    }

    input.addEventListener('input', filterRows);

    clearBtn.addEventListener('click', function () {
        input.value = '';
        filterRows();
        input.focus();
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>