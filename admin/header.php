<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/seo.php';

$currentShop = getCurrentShop($_SESSION['shop_id'] ?? 1);
$_SESSION['shop_id'] = intval($currentShop['id']);
$_SESSION['shop_name'] = $currentShop['shop_name'];
$_SESSION['shop_slug'] = $currentShop['shop_slug'];
$_SESSION['shop_type'] = $currentShop['shop_type'] ?? 'restaurant';
$isRetailShop = ($_SESSION['shop_type'] === 'retail');

$userRole = currentUserRole();
$isAdminOrSuper = hasAnyRole(['superadmin', 'admin']);
$isSuperAdminUser = hasAnyRole(['superadmin']);
$currentAdminPage = basename($_SERVER['PHP_SELF']);

if ($userRole === 'kitchen' && $currentAdminPage !== 'guide.php') {
    header('Location: ../kitchen.php');
    exit;
}
if (!$isAdminOrSuper && in_array($currentAdminPage, ['menu_manage.php', 'tables_manage.php', 'qr_tables.php', 'staff_manage.php', 'shop_settings.php', 'platform_manage.php'], true)) {
    header('Location: dashboard.php');
    exit;
}
if (!$isSuperAdminUser && $currentAdminPage === 'platform_manage.php') {
    header('Location: dashboard.php');
    exit;
}
$roleMeta = getRoleDisplayInfo($userRole);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg?v=2">
    <link rel="alternate icon" href="/favicon.ico?v=2">
<?= renderDynamicSeoTags([
    'type' => 'noindex',
    'title' => htmlspecialchars($currentShop['shop_name']) . ' - ระบบจัดการร้าน OrderPOS'
]) ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=9">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <a href="dashboard.php" class="navbar-brand">
            <i class="fas <?= $isRetailShop ? 'fa-basket-shopping' : 'fa-store' ?>"></i>
            <span><?= htmlspecialchars($currentShop['shop_name']) ?></span>
        </a>
        <ul class="navbar-nav">
            <li><a href="dashboard.php" class="<?= $currentAdminPage == 'dashboard.php' ? 'active' : '' ?>"><i class="fas fa-home"></i> หน้าแรก</a></li>
            <li><a href="../pos.php"><i class="fas fa-cash-register"></i> ขายหน้าร้าน</a></li>
            <?php if (!$isRetailShop): ?>
            <li><a href="../kitchen.php"><i class="fas fa-kitchen-set"></i> ครัว</a></li>
            <?php endif; ?>
            <?php if ($isAdminOrSuper): ?>
            <li><a href="menu_manage.php" class="<?= $currentAdminPage == 'menu_manage.php' ? 'active' : '' ?>"><i class="fas <?= $isRetailShop ? 'fa-boxes-stacked' : 'fa-book' ?>"></i> <?= $isRetailShop ? 'สินค้า & สต๊อก' : 'จัดการเมนู' ?></a></li>
            <?php endif; ?>
            <li><a href="members_manage.php" class="<?= $currentAdminPage == 'members_manage.php' ? 'active' : '' ?>"><i class="fas fa-address-card"></i> สมาชิกลูกค้า</a></li>
            <?php if ($isAdminOrSuper): ?>
            <li><a href="staff_manage.php" class="<?= $currentAdminPage == 'staff_manage.php' ? 'active' : '' ?>"><i class="fas fa-users-gear"></i> ทีมงาน</a></li>
            <?php endif; ?>
            <?php if ($isSuperAdminUser): ?>
            <li><a href="platform_manage.php" class="<?= $currentAdminPage == 'platform_manage.php' ? 'active' : '' ?>"><i class="fas fa-crown"></i> ร้านค้าสมาชิก</a></li>
            <?php endif; ?>
            <li><a href="guide.php" class="<?= $currentAdminPage == 'guide.php' ? 'active' : '' ?>"><i class="fas fa-book-open"></i> คู่มือ</a></li>
        </ul>
        <div class="navbar-right">
            <span class="user-info" title="<?= htmlspecialchars($roleMeta['desc'] ?? '') ?>">
                <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['full_name']) ?>
                <span style="margin-left:6px;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:600;background:<?= htmlspecialchars($roleMeta['bg']) ?>;color:<?= htmlspecialchars($roleMeta['color']) ?>;">
                    <?= htmlspecialchars(strtoupper($userRole)) ?>
                </span>
            </span>
            <a href="../logout.php" class="btn btn-sm btn-danger"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
        </div>
    </nav>

    <!-- Layout -->
    <div class="layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="<?= $currentAdminPage == 'dashboard.php' ? 'active' : '' ?>"><i class="fas fa-home"></i> หน้าแรก</a></li>
                <li><a href="../pos.php"><i class="fas fa-cash-register"></i> ขายหน้าร้าน (POS)</a></li>
                <?php if (!$isRetailShop): ?>
                <li><a href="../kitchen.php"><i class="fas fa-kitchen-set"></i> หน้าจอครัว</a></li>
                <?php endif; ?>
                <li><a href="../billing.php"><i class="fas fa-receipt"></i> เก็บเงินหน้าร้าน</a></li>
                <li><hr style="margin: 10px 20px; border-color: #E0E0E0;"></li>
                <?php if ($isAdminOrSuper): ?>
                <li><a href="menu_manage.php" class="<?= $currentAdminPage == 'menu_manage.php' ? 'active' : '' ?>"><i class="fas <?= $isRetailShop ? 'fa-boxes-stacked' : 'fa-book' ?>"></i> <?= $isRetailShop ? 'จัดการสินค้า & สต๊อก' : 'จัดการเมนู' ?></a></li>
                <li><a href="tables_manage.php" class="<?= $currentAdminPage == 'tables_manage.php' ? 'active' : '' ?>"><i class="fas fa-chair"></i> <?= $isRetailShop ? 'จุดขาย/ช่องทาง' : 'จัดการโต๊ะ' ?></a></li>
                <li><a href="qr_tables.php" class="<?= $currentAdminPage == 'qr_tables.php' ? 'active' : '' ?>"><i class="fas fa-qrcode"></i> QR Code หน้าร้าน</a></li>
                <?php endif; ?>
                <li><a href="orders_manage.php" class="<?= $currentAdminPage == 'orders_manage.php' ? 'active' : '' ?>"><i class="fas fa-clipboard-list"></i> <?= $isRetailShop ? 'ประวัติการขาย' : 'ดูออเดอร์' ?></a></li>
                <li><hr style="margin: 10px 20px; border-color: #E0E0E0;"></li>
                <li><a href="members_manage.php" class="<?= $currentAdminPage == 'members_manage.php' ? 'active' : '' ?>"><i class="fas fa-address-card"></i> สมาชิกลูกค้า & แต้ม</a></li>
                <?php if ($isAdminOrSuper): ?>
                <li><a href="staff_manage.php" class="<?= $currentAdminPage == 'staff_manage.php' ? 'active' : '' ?>"><i class="fas fa-users-gear"></i> จัดการทีมงาน & สิทธิ์</a></li>
                <li><a href="shop_settings.php" class="<?= $currentAdminPage == 'shop_settings.php' ? 'active' : '' ?>"><i class="fas fa-sliders"></i> ตั้งค่าร้าน & SEO</a></li>
                <?php endif; ?>
                <?php if ($isSuperAdminUser): ?>
                <li><a href="platform_manage.php" class="<?= $currentAdminPage == 'platform_manage.php' ? 'active' : '' ?>" style="color:#6D28D9;font-weight:600;"><i class="fas fa-crown"></i> จัดการร้านค้าสมาชิก (Platform)</a></li>
                <?php endif; ?>
                <li><hr style="margin: 10px 20px; border-color: #E0E0E0;"></li>
                <li><a href="guide.php" class="<?= $currentAdminPage == 'guide.php' ? 'active' : '' ?>"><i class="fas fa-book-open"></i> คู่มือการใช้งาน</a></li>
                <li><a href="../menu.php?shop=<?= urlencode($currentShop['shop_slug']) ?>" target="_blank"><i class="fas fa-external-link-alt"></i> ดูหน้าร้านออนไลน์ (ลูกค้า)</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
        <?php if ($isSuperAdminUser): ?>
            <div style="background:#F5F3FF;border:1px solid #DDD6FE;color:#5B21B6;padding:10px 16px;border-radius:10px;margin-bottom:16px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;font-size:13px;">
                <span><i class="fas fa-crown"></i> <strong>Super Admin Mode:</strong> กำลังจัดการร้าน <strong><?= htmlspecialchars($currentShop['shop_name']) ?></strong> (ID #<?= intval($currentShop['id']) ?>)</span>
                <a href="platform_manage.php" class="btn btn-sm btn-outline" style="border-color:#7C3AED;color:#6D28D9;">สลับร้าน / จัดการแพลตฟอร์ม</a>
            </div>
        <?php endif; ?>
