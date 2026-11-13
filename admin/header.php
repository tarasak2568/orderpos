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
            <li><a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>"><i class="fas fa-home"></i> หน้าแรก</a></li>
            <li><a href="../pos.php" class="<?= basename($_SERVER['PHP_SELF']) == 'pos.php' ? 'active' : '' ?>"><i class="fas fa-cash-register"></i> ขายหน้าร้าน</a></li>
            <?php if (!$isRetailShop): ?>
            <li><a href="../kitchen.php" class="<?= basename($_SERVER['PHP_SELF']) == 'kitchen.php' ? 'active' : '' ?>"><i class="fas fa-kitchen-set"></i> ครัว</a></li>
            <?php endif; ?>
            <li><a href="menu_manage.php" class="<?= basename($_SERVER['PHP_SELF']) == 'menu_manage.php' ? 'active' : '' ?>"><i class="fas <?= $isRetailShop ? 'fa-boxes-stacked' : 'fa-book' ?>"></i> <?= $isRetailShop ? 'สินค้า & สต๊อก' : 'จัดการเมนู' ?></a></li>
            <li><a href="tables_manage.php" class="<?= basename($_SERVER['PHP_SELF']) == 'tables_manage.php' ? 'active' : '' ?>"><i class="fas fa-chair"></i> <?= $isRetailShop ? 'จุดขาย/ช่องทาง' : 'จัดการโต๊ะ' ?></a></li>
            <li><a href="qr_tables.php" class="<?= basename($_SERVER['PHP_SELF']) == 'qr_tables.php' ? 'active' : '' ?>"><i class="fas fa-qrcode"></i> QR Code</a></li>
            <li><a href="orders_manage.php" class="<?= basename($_SERVER['PHP_SELF']) == 'orders_manage.php' ? 'active' : '' ?>"><i class="fas fa-clipboard-list"></i> <?= $isRetailShop ? 'บิลขาย' : 'ออเดอร์' ?></a></li>
            <li><a href="shop_settings.php" class="<?= basename($_SERVER['PHP_SELF']) == 'shop_settings.php' ? 'active' : '' ?>"><i class="fas fa-sliders"></i> ตั้งค่าร้าน & SEO</a></li>
            <li><a href="guide.php" class="<?= basename($_SERVER['PHP_SELF']) == 'guide.php' ? 'active' : '' ?>"><i class="fas fa-book-open"></i> คู่มือใช้งาน</a></li>
        </ul>
        <div class="navbar-right">
            <span class="user-info"><i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['full_name']) ?></span>
            <a href="../logout.php" class="btn btn-sm btn-danger"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
        </div>
    </nav>

    <!-- Layout -->
    <div class="layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>"><i class="fas fa-home"></i> หน้าแรก</a></li>
                <li><a href="../pos.php"><i class="fas fa-cash-register"></i> ขายหน้าร้าน (POS)</a></li>
                <?php if (!$isRetailShop): ?>
                <li><a href="../kitchen.php"><i class="fas fa-kitchen-set"></i> หน้าจอครัว</a></li>
                <?php endif; ?>
                <li><hr style="margin: 10px 20px; border-color: #E0E0E0;"></li>
                <li><a href="menu_manage.php" class="<?= basename($_SERVER['PHP_SELF']) == 'menu_manage.php' ? 'active' : '' ?>"><i class="fas <?= $isRetailShop ? 'fa-boxes-stacked' : 'fa-book' ?>"></i> <?= $isRetailShop ? 'จัดการสินค้า & สต๊อก' : 'จัดการเมนู' ?></a></li>
                <li><a href="tables_manage.php" class="<?= basename($_SERVER['PHP_SELF']) == 'tables_manage.php' ? 'active' : '' ?>"><i class="fas fa-chair"></i> <?= $isRetailShop ? 'จุดขาย/ช่องทาง' : 'จัดการโต๊ะ' ?></a></li>
                <li><a href="qr_tables.php" class="<?= basename($_SERVER['PHP_SELF']) == 'qr_tables.php' ? 'active' : '' ?>"><i class="fas fa-qrcode"></i> QR Code หน้าร้าน</a></li>
                <li><a href="orders_manage.php" class="<?= basename($_SERVER['PHP_SELF']) == 'orders_manage.php' ? 'active' : '' ?>"><i class="fas fa-clipboard-list"></i> <?= $isRetailShop ? 'ประวัติการขาย' : 'ดูออเดอร์' ?></a></li>
                <li><a href="shop_settings.php" class="<?= basename($_SERVER['PHP_SELF']) == 'shop_settings.php' ? 'active' : '' ?>"><i class="fas fa-sliders"></i> ตั้งค่าร้าน & SEO</a></li>
                <li><a href="guide.php" class="<?= basename($_SERVER['PHP_SELF']) == 'guide.php' ? 'active' : '' ?>"><i class="fas fa-book-open"></i> คู่มือการใช้งาน</a></li>
                <li><hr style="margin: 10px 20px; border-color: #E0E0E0;"></li>
                <li><a href="../menu.php?shop=<?= urlencode($currentShop['shop_slug']) ?>" target="_blank"><i class="fas fa-external-link-alt"></i> ดูหน้าร้านออนไลน์ (ลูกค้า)</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
