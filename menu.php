<?php
require_once 'config/db.php';
require_once 'config/seo.php';

$shopId = currentShopId();
$shop = getCurrentShop($shopId);
$shopId = intval($shop['id']);

$tableCode = isset($_GET['table_id']) ? htmlspecialchars(trim($_GET['table_id'])) : (isset($_GET['table']) ? htmlspecialchars(trim($_GET['table'])) : 'T01');

// ดึงหมวดหมู่และรายการอาหารของร้านนี้ เพื่อใช้สร้าง Dynamic SEO + Restaurant JSON-LD Schema
$categories = fetchAll(query("SELECT * FROM categories WHERE shop_id = $shopId AND is_active = 1 ORDER BY sort_order ASC"));
$menuItems = fetchAll(query("
    SELECT mi.*, c.name as category_name
    FROM menu_items mi
    LEFT JOIN categories c ON mi.category_id = c.id
    WHERE mi.shop_id = $shopId AND mi.is_available = 1
    ORDER BY mi.sort_order ASC, mi.name ASC
"));
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
<?= renderDynamicSeoTags([
    'type' => 'restaurant',
    'shop' => $shop,
    'menu_items' => $menuItems,
    'categories' => $categories
]) ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css?v=9">
</head>
<body>
    <div class="menu-page" data-shop-id="<?= $shopId ?>" data-shop-slug="<?= htmlspecialchars($shop['shop_slug']) ?>" data-table-id="<?= $tableCode ?>">
        <!-- Header -->
        <div class="menu-header">
            <h1><i class="fas fa-utensils"></i> <span id="shopName"><?= htmlspecialchars($shop['shop_name']) ?></span></h1>
            <?php if (!empty($shop['description'])): ?>
                <p style="margin: 4px 0 6px; font-size: 13px; opacity: 0.9;"><?= htmlspecialchars($shop['description']) ?></p>
            <?php endif; ?>
            <div class="table-info" id="tableInfo">โต๊ะ <?= $tableCode ?></div>
        </div>
        
        <!-- Categories -->
        <div class="menu-categories"></div>
        
        <!-- Menu Items -->
        <div class="menu-items"></div>
        
        <!-- Cart Bar -->
        <div class="cart-bar">
            <div class="cart-info" onclick="customerMenu.toggleCart()">
                <div class="cart-count">0</div>
                <span>ดูตะกร้า</span>
            </div>
            <div class="cart-total">฿0</div>
            <button class="btn btn-primary" onclick="customerMenu.toggleCart()">
                <i class="fas fa-shopping-cart"></i> สั่งเลย
            </button>
        </div>
        
        <!-- Cart Sidebar -->
        <div class="cart-sidebar">
            <div class="cart-sidebar-header">
                <h3><i class="fas fa-shopping-cart"></i> ตะกร้าสินค้า (<?= htmlspecialchars($shop['shop_name']) ?>)</h3>
                <button class="modal-close" onclick="customerMenu.toggleCart()">&times;</button>
            </div>
            <div class="cart-items"></div>
            <div class="cart-sidebar-footer">
                <div class="total-row">
                    <span>รวมทั้งสิ้น</span>
                    <span>฿0</span>
                </div>
                <div class="form-group">
                    <textarea id="orderNote" placeholder="หมายเหตุเพิ่มเติม (ไม่บังคับ)" style="width:100%;padding:10px;border:2px solid #E0E0E0;border-radius:8px;font-family:inherit;resize:vertical;min-height:60px;"></textarea>
                </div>
                <button class="btn btn-primary btn-lg btn-block" onclick="customerMenu.submitOrder()">
                    <i class="fas fa-paper-plane"></i> ส่งรายการสั่งซื้อ
                </button>
            </div>
        </div>
    </div>
    
    <script>
        window.ORDERPOS_SHOP = {
            shopId: <?= $shopId ?>,
            shopSlug: <?= json_encode($shop['shop_slug']) ?>,
            shopName: <?= json_encode($shop['shop_name']) ?>,
            tableId: <?= json_encode($tableCode) ?>
        };
    </script>
    <script src="/assets/js/script.js?v=9"></script>
</body>
</html>
