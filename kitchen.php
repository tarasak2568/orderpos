<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';
require_once 'config/seo.php';
$currentShop = getCurrentShop($_SESSION['shop_id'] ?? 1);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?= renderDynamicSeoTags([
    'type' => 'noindex',
    'title' => 'หน้าจอครัว - ' . $currentShop['shop_name']
]) ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <meta http-equiv="refresh" content="15">
</head>
<body>
    <div class="kitchen-page">
        <div class="kitchen-header">
            <h1><i class="fas fa-kitchen-set"></i> หน้าจอครัว - <?= htmlspecialchars($currentShop['shop_name']) ?></h1>
            <p style="color: var(--gray);">อัปเดตอัตโนมัติทุก 15 วินาที</p>
            <div style="margin-top: 15px;">
                <a href="admin/dashboard.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> กลับหน้าแรก</a>
            </div>
        </div>
        <div class="kitchen-grid"></div>
    </div>
    
    <script src="assets/js/script.js"></script>
</body>
</html>