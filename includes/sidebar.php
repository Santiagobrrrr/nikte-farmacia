<?php
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$isActive = function (string $segment) use ($currentPath): bool {
    return strpos($currentPath, $segment) !== false;
};

$userName = function_exists('currentUserName') ? currentUserName() : 'Usuario';
$roleName = function_exists('currentRole') ? currentRole() : '';

$clientesReady = file_exists(__DIR__ . '/../modules/clientes/index.php');
$reportesReady = file_exists(__DIR__ . '/../modules/reportes/index.php');
$usuariosReady = file_exists(__DIR__ . '/../modules/usuarios/index.php');

$menuItems = [
    [
        'label' => 'Dashboard',
        'icon' => 'bi-speedometer2',
        'url' => BASE_URL . '/modules/dashboard/index.php',
        'active' => $isActive('/modules/dashboard/'),
        'show' => true,
        'disabled' => false,
    ],
    [
        'label' => 'Inventario',
        'icon' => 'bi-box-seam',
        'url' => BASE_URL . '/modules/productos/index.php',
        'active' => $isActive('/modules/productos/') || $isActive('/modules/lotes/'),
        'show' => true,
        'disabled' => false,
    ],
    [
        'label' => 'Proveedores',
        'icon' => 'bi-truck',
        'url' => BASE_URL . '/modules/proveedores/index.php',
        'active' => $isActive('/modules/proveedores/'),
        'show' => true,
        'disabled' => false,
    ],
    [
        'label' => 'Compras',
        'icon' => 'bi-cart-plus',
        'url' => BASE_URL . '/modules/compras/index.php',
        'active' => $isActive('/modules/compras/'),
        'show' => true,
        'disabled' => false,
    ],
    [
        'label' => 'Ventas',
        'icon' => 'bi-cart-check',
        'url' => BASE_URL . '/modules/ventas/index.php',
        'active' => $isActive('/modules/ventas/'),
        'show' => true,
        'disabled' => false,
    ],
    [
        'label' => 'Clientes',
        'icon' => 'bi-people',
        'url' => $clientesReady ? BASE_URL . '/modules/clientes/index.php' : '#',
        'active' => $isActive('/modules/clientes/'),
        'show' => true,
        'disabled' => !$clientesReady,
    ],
    [
        'label' => 'Reportes',
        'icon' => 'bi-bar-chart',
        'url' => $reportesReady ? BASE_URL . '/modules/reportes/index.php' : '#',
        'active' => $isActive('/modules/reportes/'),
        'show' => true,
        'disabled' => !$reportesReady,
    ],
    [
        'label' => 'Usuarios',
        'icon' => 'bi-person-gear',
        'url' => $usuariosReady ? BASE_URL . '/modules/usuarios/index.php' : '#',
        'active' => $isActive('/modules/usuarios/'),
        'show' => $roleName === 'administradora',
        'disabled' => !$usuariosReady,
    ],
];

function renderSidebarMenu(array $menuItems): void
{
    foreach ($menuItems as $item) {
        if (!$item['show']) {
            continue;
        }

        $classes = 'sidebar-link';
        if ($item['active']) {
            $classes .= ' active';
        }

        if ($item['disabled']) {
            $classes .= ' disabled';
        }
        ?>
        <a href="<?= htmlspecialchars($item['url']); ?>"
           class="<?= $classes; ?>"
           <?= $item['disabled'] ? 'tabindex="-1" aria-disabled="true"' : ''; ?>>
            <i class="bi <?= htmlspecialchars($item['icon']); ?>"></i>
            <span><?= htmlspecialchars($item['label']); ?></span>

            <?php if ($item['disabled']): ?>
                <small class="ms-auto text-muted">Próx.</small>
            <?php endif; ?>
        </a>
        <?php
    }
}
?>

<style>
    .sidebar-card {
        min-height: calc(100vh - 2rem);
        background: #ffffff;
        border: 0;
        border-radius: 1rem;
    }

    .sidebar-brand {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #e9ecef;
        margin-bottom: 1rem;
    }

    .sidebar-brand-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        background: #198754;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }

    .sidebar-brand-title {
        font-weight: 700;
        margin-bottom: 0;
        line-height: 1.1;
    }

    .sidebar-brand-subtitle {
        font-size: 0.8rem;
        color: #6c757d;
        margin-bottom: 0;
    }

    .sidebar-link {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        text-decoration: none;
        color: #343a40;
        padding: 0.75rem 0.85rem;
        border-radius: 0.75rem;
        margin-bottom: 0.35rem;
        transition: all 0.18s ease-in-out;
        font-weight: 500;
    }

    .sidebar-link i {
        font-size: 1.1rem;
        color: #198754;
        transition: all 0.18s ease-in-out;
    }

    .sidebar-link:hover {
        background: #eaf7f0;
        color: #198754;
        transform: translateX(4px);
    }

    .sidebar-link.active {
        background: #198754;
        color: #ffffff;
        box-shadow: 0 8px 18px rgba(25, 135, 84, 0.22);
    }

    .sidebar-link.active i {
        color: #ffffff;
    }

    .sidebar-link.disabled {
        opacity: 0.55;
        pointer-events: none;
    }

    .sidebar-user-box {
        border-top: 1px solid #e9ecef;
        padding-top: 1rem;
        margin-top: auto;
    }

    .sidebar-user-name {
        font-weight: 700;
        margin-bottom: 0;
    }

    .sidebar-user-role {
        font-size: 0.8rem;
        color: #6c757d;
        margin-bottom: 0.75rem;
    }

    .mobile-menu-btn {
        border-radius: 0.85rem;
    }

    .offcanvas-sidebar {
        width: 285px !important;
    }
</style>

<div class="col-12 d-md-none mb-3">
    <button class="btn btn-success mobile-menu-btn w-100 d-flex align-items-center justify-content-center gap-2"
            type="button"
            data-bs-toggle="offcanvas"
            data-bs-target="#mobileSidebar"
            aria-controls="mobileSidebar">
        <i class="bi bi-list"></i>
        Menú del sistema
    </button>
</div>

<div class="offcanvas offcanvas-start offcanvas-sidebar d-md-none" tabindex="-1" id="mobileSidebar">
    <div class="offcanvas-header">
        <div class="d-flex align-items-center gap-2">
            <div class="sidebar-brand-icon">
                <i class="bi bi-capsule"></i>
            </div>
            <div>
                <h5 class="mb-0"><?= htmlspecialchars(APP_NAME); ?></h5>
                <small class="text-muted">Menú principal</small>
            </div>
        </div>

        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
    </div>

    <div class="offcanvas-body d-flex flex-column">
        <nav>
            <?php renderSidebarMenu($menuItems); ?>
        </nav>

        <div class="sidebar-user-box">
            <p class="sidebar-user-name"><?= htmlspecialchars($userName); ?></p>
            <p class="sidebar-user-role"><?= htmlspecialchars($roleName); ?></p>

            <a href="<?= BASE_URL; ?>/modules/auth/logout.php" class="btn btn-outline-danger w-100">
                <i class="bi bi-box-arrow-right me-1"></i>
                Cerrar sesión
            </a>
        </div>
    </div>
</div>

<div class="col-md-3 col-lg-2 d-none d-md-block">
    <div class="card shadow-sm sidebar-card">
        <div class="card-body d-flex flex-column">
            <div class="sidebar-brand">
                <div class="sidebar-brand-icon">
                    <i class="bi bi-capsule"></i>
                </div>

                <div>
                    <p class="sidebar-brand-title"><?= htmlspecialchars(APP_NAME); ?></p>
                </div>
            </div>

            <nav>
                <?php renderSidebarMenu($menuItems); ?>
            </nav>

            <div class="sidebar-user-box">
                <p class="sidebar-user-name"><?= htmlspecialchars($userName); ?></p>
                <p class="sidebar-user-role"><?= htmlspecialchars($roleName); ?></p>

                <a href="<?= BASE_URL; ?>/modules/auth/logout.php" class="btn btn-outline-danger w-100">
                    <i class="bi bi-box-arrow-right me-1"></i>
                    Cerrar sesión
                </a>
            </div>
        </div>
    </div>
</div>