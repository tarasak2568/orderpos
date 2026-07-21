<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบ POS ร้านอาหาร</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=7">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <a href="dashboard.php" class="navbar-brand">
            <i class="fas fa-utensils"></i>
            <span>POS ร้านอาหาร</span>
        </a>
        <ul class="navbar-nav">
            <li><a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>"><i class="fas fa-home"></i> หน้าแรก</a></li>
            <li><a href="../pos.php" class="<?= basename($_SERVER['PHP_SELF']) == 'pos.php' ? 'active' : '' ?>"><i class="fas fa-cash-register"></i> ขายหน้าร้าน</a></li>
            <li><a href="../kitchen.php" class="<?= basename($_SERVER['PHP_SELF']) == 'kitchen.php' ? 'active' : '' ?>"><i class="fas fa-kitchen-set"></i> ครัว</a></li>
            <li><a href="menu_manage.php" class="<?= basename($_SERVER['PHP_SELF']) == 'menu_manage.php' ? 'active' : '' ?>"><i class="fas fa-book"></i> จัดการเมนู</a></li>
            <li><a href="tables_manage.php" class="<?= basename($_SERVER['PHP_SELF']) == 'tables_manage.php' ? 'active' : '' ?>"><i class="fas fa-chair"></i> จัดการโต๊ะ</a></li>
            <li><a href="qr_tables.php" class="<?= basename($_SERVER['PHP_SELF']) == 'qr_tables.php' ? 'active' : '' ?>"><i class="fas fa-qrcode"></i> QR Code</a></li>
            <li><a href="orders_manage.php" class="<?= basename($_SERVER['PHP_SELF']) == 'orders_manage.php' ? 'active' : '' ?>"><i class="fas fa-clipboard-list"></i> ออเดอร์</a></li>
        </ul>
        <div class="navbar-right">
            <span class="user-info"><i class="fas fa-user"></i> <?= $_SESSION['full_name'] ?></span>
            <a href="../logout.php" class="btn btn-sm btn-danger"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
        </div>
    </nav>

    <!-- Layout -->
    <div class="layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>"><i class="fas fa-home"></i> หน้าแรก</a></li>
                <li><a href="../pos.php"><i class="fas fa-cash-register"></i> ขายหน้าร้าน</a></li>
                <li><a href="../kitchen.php"><i class="fas fa-kitchen-set"></i> หน้าจอครัว</a></li>
                <li><hr style="margin: 10px 20px; border-color: #E0E0E0;"></li>
                <li><a href="menu_manage.php" class="<?= basename($_SERVER['PHP_SELF']) == 'menu_manage.php' ? 'active' : '' ?>"><i class="fas fa-book"></i> จัดการเมนู</a></li>
                <li><a href="tables_manage.php" class="<?= basename($_SERVER['PHP_SELF']) == 'tables_manage.php' ? 'active' : '' ?>"><i class="fas fa-chair"></i> จัดการโต๊ะ</a></li>
                <li><a href="qr_tables.php" class="<?= basename($_SERVER['PHP_SELF']) == 'qr_tables.php' ? 'active' : '' ?>"><i class="fas fa-qrcode"></i> QR Code โต๊ะ</a></li>
                <li><a href="orders_manage.php" class="<?= basename($_SERVER['PHP_SELF']) == 'orders_manage.php' ? 'active' : '' ?>"><i class="fas fa-clipboard-list"></i> ดูออเดอร์</a></li>
                <li><hr style="margin: 10px 20px; border-color: #E0E0E0;"></li>
                <li><a href="../menu.php" target="_blank"><i class="fas fa-external-link-alt"></i> ดูเมนู (ลูกค้า)</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a></li>
            </ul>
        </aside>
        
        <main class="main-content">