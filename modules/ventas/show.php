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
                v.subtotal_venta,
                v.descuento_porcentaje,
                v.descuento_monto,
                v.motivo_descuento,
                v.total_venta,
                c.nombre AS nombre_cliente,
                c.nit AS nit_cliente,
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

$subtotalVenta = (float) ($venta['subtotal_venta'] ?? 0);
$descuentoPorcentaje = (float) ($venta['descuento_porcentaje'] ?? 0);
$descuentoMonto = (float) ($venta['descuento_monto'] ?? 0);
$totalVenta = (float) ($venta['total_venta'] ?? 0);

if ($subtotalVenta <= 0) {
    $subtotalVenta = $totalVenta + $descuentoMonto;
}

$nitCliente = trim((string) ($venta['nit_cliente'] ?? ''));
if ($nitCliente === '') {
    $nitCliente = 'CF';
}
?>

<style>
    .invoice-wrapper {
        max-width: 900px;
        margin: 0 auto;
    }

    .invoice-card {
        background: #fff;
        border-radius: 10px;
        border: 1px solid #dee2e6;
    }

    .invoice-title {
        text-align: center;
        font-size: 1.8rem;
        font-weight: 700;
        margin-bottom: 1.1rem;
        color: #0f5f78;
    }

    .invoice-separator {
        border-top: 2px solid #212529;
        margin-bottom: 1rem;
    }

    .invoice-info {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.25rem;
        margin-bottom: 1rem;
        font-size: .92rem;
    }

    .invoice-box {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: .8rem .9rem;
        background: #fdfdfd;
    }

    .invoice-box-title {
        font-weight: 700;
        color: #0f5f78;
        letter-spacing: .5px;
        text-transform: uppercase;
        margin-bottom: .55rem;
    }

    .invoice-line {
        margin-bottom: .35rem;
    }

    .invoice-line:last-child {
        margin-bottom: 0;
    }

    .invoice-label {
        font-weight: 700;
        color: #212529;
    }

    .invoice-value {
        color: #212529;
    }

    .invoice-table {
        font-size: .9rem;
        margin-bottom: 0;
    }

    .invoice-table th {
        background: #f1f4f6;
        color: #212529;
        font-weight: 700;
        text-align: center;
    }

    .invoice-table td,
    .invoice-table th {
        vertical-align: middle;
        padding: .55rem .6rem;
    }

    .invoice-total-label {
        font-weight: 700;
        text-align: right;
    }

    .invoice-total-value {
        font-weight: 700;
        text-align: right;
    }

    .invoice-footer {
        text-align: center;
        margin-top: 1.4rem;
        color: #6c757d;
        font-size: .85rem;
    }

    .internal-note {
        font-size: .78rem;
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

        .invoice-wrapper {
            max-width: 100%;
            margin: 0;
        }

        .invoice-card {
            border: none !important;
            box-shadow: none !important;
        }

        .card-body {
            padding: 0 !important;
        }

        .invoice-table,
        .invoice-info {
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

            <div id="print-area" class="invoice-wrapper">
                <div class="card shadow-sm invoice-card">
                    <div class="card-body p-4">

                        <div class="invoice-title">
                            Factura
                        </div>

                        <div class="invoice-separator"></div>

                        <div class="invoice-info">
                            <div class="invoice-box">
                                <div class="invoice-box-title">
                                    <?= strtoupper(htmlspecialchars(APP_NAME)); ?>
                                </div>

                                <div class="invoice-line">
                                    <span class="invoice-label">Tipo:</span>
                                    <span class="invoice-value">Comprobante interno de venta</span>
                                </div>

                                <div class="invoice-line">
                                    <span class="invoice-label">Nombre receptor:</span>
                                    <span class="invoice-value">
                                        <?= htmlspecialchars($venta['nombre_cliente'] ?? 'Consumidor final'); ?>
                                    </span>
                                </div>

                                <div class="invoice-line">
                                    <span class="invoice-label">NIT receptor:</span>
                                    <span class="invoice-value">
                                        <?= htmlspecialchars($nitCliente); ?>
                                    </span>
                                </div>
                            </div>

                            <div class="invoice-box">
                                <div class="invoice-line">
                                    <span class="invoice-label">Número de comprobante:</span>
                                    <span class="invoice-value">
                                        <?= htmlspecialchars($numeroComprobante); ?>
                                    </span>
                                </div>

                                <div class="invoice-line">
                                    <span class="invoice-label">Fecha y hora de emisión:</span>
                                    <span class="invoice-value">
                                        <?= date('d/m/Y H:i', strtotime($venta['fecha_venta'])); ?>
                                    </span>
                                </div>

                                <div class="invoice-line">
                                    <span class="invoice-label">Vendedor:</span>
                                    <span class="invoice-value">
                                        <?= htmlspecialchars($venta['nombre_usuario'] ?? ''); ?>
                                    </span>
                                </div>

                                <div class="invoice-line">
                                    <span class="invoice-label">Método de pago:</span>
                                    <span class="invoice-value">
                                        <?= ucfirst(htmlspecialchars($venta['metodo_pago'])); ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered align-middle invoice-table">
                                <thead>
                                    <tr>
                                        <th width="60">#</th>
                                        <th>Descripción</th>
                                        <th width="110">Cantidad</th>
                                        <th width="140">P. Unitario</th>
                                        <th width="130">Descuento</th>
                                        <th width="140">Total</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($detalles as $index => $d): ?>
                                        <tr>
                                            <td class="text-center">
                                                <?= $index + 1; ?>
                                            </td>

                                            <td>
                                                <strong><?= htmlspecialchars($d['nombre_producto']); ?></strong>

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

                                            <td class="text-center">
                                                <?= (int) $d['cantidad']; ?>
                                            </td>

                                            <td class="text-end">
                                                Q<?= number_format((float) $d['precio_unitario'], 2); ?>
                                            </td>

                                            <td class="text-end">
                                                Q0.00
                                            </td>

                                            <td class="text-end">
                                                Q<?= number_format((float) $d['subtotal'], 2); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>

                                <tfoot>
                                    <tr>
                                        <td colspan="4" class="invoice-total-label">
                                            TOTALES:
                                        </td>
                                        <td class="text-end">
                                            Q<?= number_format($descuentoMonto, 2); ?>
                                        </td>
                                        <td class="text-end">
                                            Q<?= number_format($subtotalVenta, 2); ?>
                                        </td>
                                    </tr>

                                    <?php if ($descuentoMonto > 0): ?>
                                        <tr>
                                            <td colspan="5" class="invoice-total-label">
                                                Descuento general <?= number_format($descuentoPorcentaje, 2); ?>%
                                            </td>
                                            <td class="text-end">
                                                - Q<?= number_format($descuentoMonto, 2); ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>

                                    <tr>
                                        <td colspan="5" class="invoice-total-label">
                                            Total final
                                        </td>
                                        <td class="invoice-total-value">
                                            Q<?= number_format($totalVenta, 2); ?>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <?php if ($descuentoMonto > 0 && !empty($venta['motivo_descuento'])): ?>
                            <div class="alert alert-light border internal-only mt-3 mb-0">
                                <strong>Motivo del descuento:</strong>
                                <?= htmlspecialchars($venta['motivo_descuento']); ?>
                            </div>
                        <?php endif; ?>

                        <div class="invoice-footer">
                            <div>Gracias por su compra.</div>
                            <div class="mt-1">Comprobante interno sin validez fiscal.</div>

                            <div class="mt-2 internal-only">
                                <span class="internal-note">
                                    Información interna: los lotes se muestran únicamente para control de inventario.
                                </span>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>