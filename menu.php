<?php
require_once 'config/db.php';
require_once 'config/seo.php';

$shopId = currentShopId();
$shop = getCurrentShop($shopId);
$shopId = intval($shop['id']);
$shopType = $shop['shop_type'] ?? 'restaurant';
$isRetail = ($shopType === 'retail');

$rawTable = $_GET['table_id'] ?? ($_GET['table'] ?? ($isRetail ? 'WALKIN' : 'T01'));
$tableCode = htmlspecialchars(trim($rawTable), ENT_QUOTES, 'UTF-8');
$isWalkinChannel = $isRetail || strtoupper(trim($rawTable)) === 'WALKIN';

// ดึงหมวดหมู่และรายการสินค้า/อาหารที่เปิดขายของร้าน เพื่อใช้สร้าง Dynamic SEO + JSON-LD Schema
$categories = fetchAll(query("SELECT * FROM categories WHERE shop_id = $shopId AND is_active = 1 ORDER BY sort_order ASC"));
$menuItems = fetchAll(query("
    SELECT mi.*, c.name as category_name
    FROM menu_items mi
    LEFT JOIN categories c ON mi.category_id = c.id
    WHERE mi.shop_id = $shopId AND mi.is_available = 1
    ORDER BY mi.sort_order ASC, mi.name ASC
"));

$storeTagline = trim($shop['description'] ?? '') !== ''
    ? $shop['description']
    : ($isRetail
        ? 'คัดสรรสินค้าคุณภาพ ราคาประหยัด สั่งซื้อสะดวกและชำระเงินได้ทันที'
        : 'ปรุงสดใหม่ทุกเมนู สั่งง่ายผ่านระบบออนไลน์ ส่งตรงเข้าครัวทันที');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg?v=2">
    <link rel="alternate icon" href="/favicon.ico?v=2">
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
    <main class="menu-page" data-shop-id="<?= $shopId ?>" data-shop-slug="<?= htmlspecialchars($shop['shop_slug'], ENT_QUOTES, 'UTF-8') ?>" data-table-id="<?= $tableCode ?>">
        <!-- Header -->
        <header class="menu-header">
            <h1>
                <i class="fas <?= $isRetail ? 'fa-store' : 'fa-utensils' ?>"></i>
                <span id="shopName"><?= htmlspecialchars($shop['shop_name'], ENT_QUOTES, 'UTF-8') ?></span>
            </h1>
            <p style="margin: 4px 0 8px; font-size: 13px; opacity: 0.92; line-height: 1.45;">
                <?= htmlspecialchars($storeTagline, ENT_QUOTES, 'UTF-8') ?>
            </p>
            <?php if (!empty($shop['phone']) || !empty($shop['address'])): ?>
                <div style="font-size: 12px; opacity: 0.88; margin-bottom: 8px; display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                    <?php if (!empty($shop['phone'])): ?>
                        <span><i class="fas fa-phone"></i> <?= htmlspecialchars($shop['phone'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                    <?php if (!empty($shop['address'])): ?>
                        <span><i class="fas fa-location-dot"></i> <?= htmlspecialchars($shop['address'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <div class="table-info" id="tableInfo">
                <?= $isWalkinChannel ? 'สั่งซื้อสินค้าหน้าร้าน / ออนไลน์' : ('โต๊ะ ' . $tableCode) ?>
            </div>
        </header>

        <!-- Categories -->
        <nav class="menu-categories" aria-label="หมวดหมู่<?= $isRetail ? 'สินค้า' : 'อาหาร' ?>"></nav>

        <!-- Menu / Product Items -->
        <section class="menu-items" aria-label="รายการ<?= $isRetail ? 'สินค้า' : 'เมนู' ?>"></section>

        <!-- Cart Bar -->
        <div class="cart-bar">
            <div class="cart-info" onclick="customerMenu.toggleCart()">
                <div class="cart-count">0</div>
                <span>ดูตะกร้า</span>
            </div>
            <div class="cart-total">฿0</div>
            <button class="btn btn-primary" onclick="customerMenu.toggleCart()">
                <i class="fas fa-shopping-cart"></i> <?= $isRetail ? 'สั่งซื้อเลย' : 'สั่งเลย' ?>
            </button>
        </div>

        <!-- Cart Sidebar -->
        <aside class="cart-sidebar" aria-label="ตะกร้าสินค้า">
            <div class="cart-sidebar-header">
                <h3><i class="fas fa-shopping-cart"></i> ตะกร้าสินค้า (<?= htmlspecialchars($shop['shop_name'], ENT_QUOTES, 'UTF-8') ?>)</h3>
                <button class="modal-close" onclick="customerMenu.toggleCart()">&times;</button>
            </div>
            <div class="cart-items"></div>
            <div class="cart-sidebar-footer">
                <div class="total-row">
                    <span>รวมทั้งสิ้น</span>
                    <span>฿0</span>
                </div>
                <div class="form-group">
                    <textarea id="orderNote" placeholder="<?= $isRetail ? 'หมายเหตุเพิ่มเติม เช่น แยกถุง หรือฝากหมายเหตุถึงร้าน (ไม่บังคับ)' : 'หมายเหตุเพิ่มเติม เช่น ไม่ใส่ผัก เผ็ดน้อย (ไม่บังคับ)' ?>" style="width:100%;padding:10px;border:2px solid #E0E0E0;border-radius:8px;font-family:inherit;resize:vertical;min-height:60px;"></textarea>
                </div>
                <button class="btn btn-primary btn-lg btn-block" onclick="customerMenu.submitOrder()">
                    <i class="fas fa-paper-plane"></i> <?= $isRetail ? 'ยืนยันสั่งซื้อสินค้า' : 'ส่งรายการสั่งซื้อ' ?>
                </button>
            </div>
        </aside>
    </main>

    <script>
        window.ORDERPOS_SHOP = {
            shopId: <?= $shopId ?>,
            shopSlug: <?= json_encode($shop['shop_slug']) ?>,
            shopName: <?= json_encode($shop['shop_name'], JSON_UNESCAPED_UNICODE) ?>,
            shopType: <?= json_encode($shopType) ?>,
            tableId: <?= json_encode($tableCode) ?>
        };
    </script>
    <script src="/assets/js/script.js?v=10"></script>
</body>
</html>
