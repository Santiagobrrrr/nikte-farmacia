<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';

$idVenta = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$success = $_SESSION['venta_success'] ?? '';
unset($_SESSION['venta_success']);

$venta = null;
$detalles = [];

if ($idVenta > 0) {
    try {
        $pdo = getPDO();

        $stmt = $pdo->prepare("
            SELECT
                v.id_venta,
                v.fecha_venta,
                v.metodo_pago,
                v.total_venta,
                c.nombre AS nombre_cliente,
                u.nombre AS nombre_usuario
            FROM venta v
            LEFT JOIN cliente c ON c.id_cliente = v.id_cliente
            LEFT JOIN usuario u ON u.id_usuario = v.id_usuario
            WHERE v.id_venta = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $idVenta]);
        $venta = $stmt->fetch();

        if ($venta) {
            $stmtDet = $pdo->prepare("
                SELECT
                    dv.cantidad,
                    dv.precio_unitario,
                    dv.subtotal,
                    p.nombre AS nombre_producto,
                    p.presentacion,
                    l.codigo_lote
                FROM detalleventa dv
                INNER JOIN producto p ON p.id_producto = dv.id_producto
                LEFT JOIN lote l ON l.id_lote = dv.id_lote
                WHERE dv.id_venta = :id
                ORDER BY dv.id_detalle_venta ASC
            ");
            $stmtDet->execute(['id' => $idVenta]);
            $detalles = $stmtDet->fetchAll();
        }

    } catch (Throwable $e) {
        $venta = null;
    }
}

if (!$venta) {
    header('Location: ' . BASE_URL . '/modules/ventas/index.php');
    exit;
}

$numeroComprobante = str_pad((string) $venta['id_venta'], 6, '0', STR_PAD_LEFT);
?>

<style>
    .receipt-wrapper {
        max-width: 780px;
        margin: 0 auto;
    }

    .receipt-card {
        background: #fff;
        border-radius: 10px;
    }

    .receipt-title {
        font-size: 1.8rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
    }

    .receipt-subtitle {
        color: #6c757d;
        margin-bottom: 0.25rem;
    }

    .receipt-number {
        font-weight: 700;
        font-size: 1rem;
    }

    .receipt-label {
        font-weight: 700;
        margin-bottom: 0.25rem;
    }

    .receipt-value {
        margin-bottom: 1rem;
    }

    .receipt-total {
        font-size: 1.1rem;
        font-weight: 700;
    }

    .internal-note {
        font-size: 0.85rem;
    }

    @page {
        margin: 12mm;
    }

    @media print {
        body * {
            visibility: hidden;
        }

        #print-area,
        #print-area * {
            visibility: visible;
        }

        #print-area {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }

        .no-print,
        .no-print * {
            display: none !important;
        }

        .internal-only {
            display: none !important;
        }

        .receipt-wrapper {
            max-width: 100%;
            margin: 0;
        }

        .receipt-card {
            border: none !important;
            box-shadow: none !important;
        }

        .card-body {
            padding: 0 !important;
        }

        .table {
            font-size: 12px;
        }
    }
</style>

<div class="container-fluid py-4">
    <div class="row">
        <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

        <div class="col-12 col-md-9 col-lg-10">
            <div class="no-print d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="mb-1">Detalle de venta #<?= (int) $venta['id_venta']; ?></h1>
                    <p class="text-muted mb-0">
                        Comprobante de la transacción registrada.
                    </p>
                </div>

                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-primary" onclick="window.print()">
                        Imprimir comprobante
                    </button>

                    <a href="<?= BASE_URL; ?>/modules/ventas/index.php" class="btn btn-secondary">
                        Volver
                    </a>
                </div>
            </div>

            <?php if (!empty($success)): ?>
                <div class="no-print alert alert-success">
                    <?= htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <div id="print-area" class="receipt-wrapper">
                <div class="card shadow-sm border-0 receipt-card">
                    <div class="card-body p-4">

                        <div class="text-center mb-4">
                            <div class="receipt-title"><?= htmlspecialchars(APP_NAME); ?></div>
                            <div class="receipt-subtitle">Comprobante interno de venta</div>
                            <div class="receipt-number">
                                Comprobante No. <?= htmlspecialchars($numeroComprobante); ?>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-6">
                                <p class="receipt-label">Fecha:</p>
                                <p class="receipt-value">
                                    <?= date('d/m/Y H:i', strtotime($venta['fecha_venta'])); ?>
                                </p>
                            </div>

                            <div class="col-6 text-end">
                                <p class="receipt-label">Método de pago:</p>
                                <p class="receipt-value">
                                    <?= ucfirst(htmlspecialchars($venta['metodo_pago'])); ?>
                                </p>
                            </div>

                            <div class="col-6">
                                <p class="receipt-label">Cliente:</p>
                                <p class="receipt-value">
                                    <?= htmlspecialchars($venta['nombre_cliente'] ?? 'Consumidor final'); ?>
                                </p>
                            </div>

                            <div class="col-6 text-end">
                                <p class="receipt-label">Vendedor:</p>
                                <p class="receipt-value">
                                    <?= htmlspecialchars($venta['nombre_usuario'] ?? ''); ?>
                                </p>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Producto</th>
                                        <th class="text-end">Cant.</th>
                                        <th class="text-end">Precio</th>
                                        <th class="text-end">Subtotal</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($detalles as $d): ?>
                                        <tr>
                                            <td>
                                                <?= htmlspecialchars($d['nombre_producto']); ?>

                                                <?php if (!empty($d['presentacion'])): ?>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?= htmlspecialchars($d['presentacion']); ?>
                                                    </small>
                                                <?php endif; ?>

                                                <?php if (!empty($d['codigo_lote'])): ?>
                                                    <br>
                                                    <small class="text-muted internal-only">
                                                        Lote: <?= htmlspecialchars($d['codigo_lote']); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>

                                            <td class="text-end">
                                                <?= (int) $d['cantidad']; ?>
                                            </td>

                                            <td class="text-end">
                                                Q<?= number_format((float) $d['precio_unitario'], 2); ?>
                                            </td>

                                            <td class="text-end">
                                                Q<?= number_format((float) $d['subtotal'], 2); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>

                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end receipt-total">
                                            Total
                                        </td>
                                        <td class="text-end receipt-total">
                                            Q<?= number_format((float) $venta['total_venta'], 2); ?>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="text-center mt-4">
                            <small class="text-muted">
                                Gracias por su compra.
                            </small>
                        </div>

                        <div class="text-center mt-2">
                            <small class="text-muted">
                                Comprobante interno sin validez fiscal.
                            </small>
                        </div>

                        <div class="text-center mt-2 internal-only">
                            <small class="text-muted internal-note">
                                Información interna: los lotes se muestran únicamente para control de inventario.
                            </small>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>