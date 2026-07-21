<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ขายหน้าร้าน - ระบบ POS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <a href="admin/dashboard.php" class="navbar-brand">
            <i class="fas fa-utensils"></i>
            <span>POS ร้านอาหาร</span>
        </a>
        <ul class="navbar-nav">
            <li><a href="admin/dashboard.php"><i class="fas fa-home"></i> หน้าแรก</a></li>
            <li><a href="pos.php" class="active"><i class="fas fa-cash-register"></i> ขายหน้าร้าน</a></li>
            <li><a href="kitchen.php"><i class="fas fa-kitchen-set"></i> ครัว</a></li>
            <li><a href="billing.php"><i class="fas fa-receipt"></i> เก็บเงิน</a></li>
        </ul>
        <div class="navbar-right">
            <span class="user-info"><i class="fas fa-user"></i> <?= $_SESSION['full_name'] ?></span>
            <a href="logout.php" class="btn btn-sm btn-danger"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
        </div>
    </nav>

    <div class="pos-layout">
        <!-- Menu Panel -->
        <div class="pos-menu">
            <div class="pos-categories"></div>
            <div class="pos-items"></div>
        </div>
        
        <!-- Cart Panel -->
        <div class="pos-cart">
            <div class="pos-cart-header">
                <select>
                    <option value="">เลือกโต๊ะ</option>
                </select>
            </div>
            <div class="pos-cart-items"></div>
            <div class="pos-cart-footer">
                <div class="total-row">
                    <span>รวมทั้งสิ้น <small>(0 รายการ)</small></span>
                    <span>฿0</span>
                </div>
                <button class="btn btn-primary btn-lg btn-block" onclick="staffPos.submitOrder()">
                    <i class="fas fa-paper-plane"></i> สั่งอาหาร
                </button>
            </div>
        </div>
    </div>
    
    <script src="assets/js/script.js?v=5"></script>
</body>
</html>