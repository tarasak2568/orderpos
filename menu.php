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
            <div style="display: flex; align-items: center; justify-content: center; gap: 10px; flex-wrap: wrap; margin-top: 4px;">
                <div class="table-info" id="tableInfo">
                    <?= $isWalkinChannel ? 'สั่งซื้อสินค้าหน้าร้าน / ออนไลน์' : ('โต๊ะ ' . $tableCode) ?>
                </div>
                <button type="button"
                        onclick="openCustomerGuideModal()"
                        style="background: rgba(255,255,255,0.2); color: #FFFFFF; border: 1px solid rgba(255,255,255,0.45); border-radius: 999px; padding: 4px 12px; font-size: 12.5px; font-family: inherit; cursor: pointer; display: inline-flex; align-items: center; gap: 5px;">
                    <i class="fas fa-circle-question"></i> <?= $isRetail ? 'วิธีสั่งซื้อ' : 'วิธีสั่งอาหาร' ?>
                </button>
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

    <!-- Customer Quick Guide Modal -->
    <div id="customerGuideModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="customerGuideTitle" style="padding: 16px;">
        <div class="modal" style="padding: 22px; max-width: 460px;">
            <div class="modal-header" style="margin-bottom: 14px;">
                <h3 id="customerGuideTitle" style="font-size: 18px; display: flex; align-items: center; gap: 8px; margin: 0;">
                    <i class="fas fa-book-open" style="color: var(--primary);"></i>
                    <?= $isRetail ? 'วิธีสั่งซื้อสินค้า & ชำระเงิน' : 'วิธีสั่งอาหารผ่าน QR Code' ?>
                </h3>
                <button type="button" class="modal-close" onclick="closeCustomerGuideModal()" aria-label="ปิดคู่มือ">&times;</button>
            </div>
            <div style="display: flex; flex-direction: column; gap: 10px; font-size: 13.5px; color: #334155; line-height: 1.55;">
                <div style="display: flex; gap: 10px; align-items: flex-start; background: #F8FAFC; padding: 10px 12px; border-radius: 10px;">
                    <span style="min-width: 24px; height: 24px; border-radius: 50%; background: var(--primary); color: #FFF; font-weight: 700; font-size: 12px; display: inline-flex; align-items: center; justify-content: center;">1</span>
                    <div><strong><?= $isRetail ? 'เลือกหมวดหมู่ & ค้นหาสินค้า' : 'เลือกหมวดหมู่ & รายการอาหาร' ?></strong><br>แตะแถบหมวดหมู่ด้านบน หรือเลื่อนดูรายการที่มีสถานะพร้อมจำหน่าย</div>
                </div>
                <div style="display: flex; gap: 10px; align-items: flex-start; background: #F8FAFC; padding: 10px 12px; border-radius: 10px;">
                    <span style="min-width: 24px; height: 24px; border-radius: 50%; background: var(--primary); color: #FFF; font-weight: 700; font-size: 12px; display: inline-flex; align-items: center; justify-content: center;">2</span>
                    <div><strong>หยิบใส่ตะกร้า & ปรับจำนวน</strong><br>กดปุ่ม "+ เพิ่ม" แล้วแตะแถบตะกร้าด้านล่างเพื่อเพิ่ม/ลดจำนวน (+ / -)</div>
                </div>
                <div style="display: flex; gap: 10px; align-items: flex-start; background: #F8FAFC; padding: 10px 12px; border-radius: 10px;">
                    <span style="min-width: 24px; height: 24px; border-radius: 50%; background: var(--primary); color: #FFF; font-weight: 700; font-size: 12px; display: inline-flex; align-items: center; justify-content: center;">3</span>
                    <div><strong>ใส่หมายเหตุถึงร้าน (ถ้ามี)</strong><br><?= $isRetail ? 'ระบุรายละเอียด เช่น แยกถุง หรือเวลานัดมารับสินค้าที่หน้าร้าน' : 'ระบุรายละเอียดเพิ่มเติม เช่น ไม่ใส่ผัก เผ็ดน้อย แยกน้ำ' ?></div>
                </div>
                <div style="display: flex; gap: 10px; align-items: flex-start; background: #F8FAFC; padding: 10px 12px; border-radius: 10px;">
                    <span style="min-width: 24px; height: 24px; border-radius: 50%; background: var(--primary); color: #FFF; font-weight: 700; font-size: 12px; display: inline-flex; align-items: center; justify-content: center;">4</span>
                    <div><strong><?= $isRetail ? 'ยืนยันสั่งซื้อ & ชำระเงิน' : 'ส่งรายการเข้าครัว & เช็กบิล' ?></strong><br><?= $isRetail ? 'กด "ยืนยันสั่งซื้อสินค้า" ออเดอร์จะส่งถึงร้านทันที ชำระเงินสดหรือสแกน PromptPay ได้เลย' : 'กด "ส่งรายการสั่งซื้อ" ออเดอร์จะเข้าครัวทันที สั่งเพิ่มได้ตลอด และแจ้งพนักงานเช็กบิลเมื่อทานเสร็จ' ?></div>
                </div>
            </div>
            <div class="modal-footer" style="justify-content: space-between; margin-top: 16px; padding-top: 12px;">
                <a href="/guide?tab=customer" target="_blank" rel="noopener" class="btn btn-outline btn-sm">
                    <i class="fas fa-book"></i> คู่มือฉบับเต็ม
                </a>
                <button type="button" class="btn btn-primary btn-sm" onclick="closeCustomerGuideModal()">เข้าใจแล้ว</button>
            </div>
        </div>
    </div>

    <script>
        window.ORDERPOS_SHOP = {
            shopId: <?= $shopId ?>,
            shopSlug: <?= json_encode($shop['shop_slug']) ?>,
            shopName: <?= json_encode($shop['shop_name'], JSON_UNESCAPED_UNICODE) ?>,
            shopType: <?= json_encode($shopType) ?>,
            tableId: <?= json_encode($tableCode) ?>
        };

        function openCustomerGuideModal() {
            const modal = document.getElementById('customerGuideModal');
            if (modal) modal.classList.add('active');
        }

        function closeCustomerGuideModal() {
            const modal = document.getElementById('customerGuideModal');
            if (modal) modal.classList.remove('active');
        }

        (function () {
            const modal = document.getElementById('customerGuideModal');
            if (!modal) return;
            modal.addEventListener('click', function (e) {
                if (e.target === modal) closeCustomerGuideModal();
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && modal.classList.contains('active')) {
                    closeCustomerGuideModal();
                }
            });
        })();
    </script>
    <script src="/assets/js/script.js?v=10"></script>
</body>
</html>
