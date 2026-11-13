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
$isRetail = (($currentShop['shop_type'] ?? 'restaurant') === 'retail');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?= renderDynamicSeoTags([
    'type' => 'noindex',
    'title' => 'ขายหน้าร้าน (POS) - ' . $currentShop['shop_name']
]) ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=9">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <a href="admin/dashboard.php" class="navbar-brand">
            <i class="fas <?= $isRetail ? 'fa-basket-shopping' : 'fa-store' ?>"></i>
            <span><?= htmlspecialchars($currentShop['shop_name']) ?></span>
        </a>
        <ul class="navbar-nav">
            <li><a href="admin/dashboard.php"><i class="fas fa-home"></i> หน้าแรก</a></li>
            <li><a href="pos.php" class="active"><i class="fas fa-cash-register"></i> ขายหน้าร้าน</a></li>
            <?php if (!$isRetail): ?>
            <li><a href="kitchen.php"><i class="fas fa-kitchen-set"></i> ครัว</a></li>
            <?php endif; ?>
            <li><a href="billing.php"><i class="fas fa-receipt"></i> เก็บเงิน</a></li>
            <li><a href="admin/menu_manage.php"><i class="fas fa-boxes-stacked"></i> <?= $isRetail ? 'สินค้า/สต๊อก' : 'จัดการเมนู' ?></a></li>
        </ul>
        <div class="navbar-right">
            <span class="user-info"><i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['full_name']) ?></span>
            <a href="logout.php" class="btn btn-sm btn-danger"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
        </div>
    </nav>

    <div class="pos-layout" data-shop-type="<?= htmlspecialchars($currentShop['shop_type'] ?? 'restaurant') ?>">
        <!-- Menu / Product Panel -->
        <div class="pos-menu">
            <!-- Search & Barcode Scanner Bar -->
            <div style="padding: 12px 16px 4px; display: flex; gap: 10px; align-items: center;">
                <div style="position: relative; flex: 1;">
                    <i class="fas fa-barcode" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #64748B;"></i>
                    <input
                        type="text"
                        id="posSearchInput"
                        placeholder="<?= $isRetail ? 'ค้นหาชื่อสินค้า หรือยิงบาร์โค้ด (กด Enter เพื่อหยิบใส่ตะกร้า)...' : 'ค้นหาเมนูอาหาร หรือรหัสสินค้า...' ?>"
                        style="width: 100%; padding: 11px 14px 11px 40px; border: 2px solid #E2E8F0; border-radius: 10px; font-family: inherit; font-size: 14px;"
                    >
                </div>
            </div>
            <div class="pos-categories"></div>
            <div class="pos-items"></div>
        </div>
        
        <!-- Cart Panel -->
        <div class="pos-cart">
            <div class="pos-cart-header">
                <select id="posTableSelect">
                    <option value="WALKIN"><?= $isRetail ? '🛒 ขายหน้าร้าน (Walk-in)' : 'เลือกโต๊ะ / ขายหน้าร้าน' ?></option>
                </select>
            </div>
            <div class="pos-cart-items"></div>
            <div class="pos-cart-footer">
                <div class="total-row">
                    <span>รวมทั้งสิ้น <small>(0 รายการ)</small></span>
                    <span>฿0</span>
                </div>
                <?php if ($isRetail): ?>
                    <button class="btn btn-success btn-lg btn-block" onclick="staffPos.openInstantCheckout()">
                        <i class="fas fa-cash-register"></i> คิดเงินทันที (รับชำระเงิน)
                    </button>
                <?php else: ?>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                        <button class="btn btn-primary btn-lg" onclick="staffPos.submitOrder()">
                            <i class="fas fa-paper-plane"></i> ส่งเข้าครัว
                        </button>
                        <button class="btn btn-success btn-lg" onclick="staffPos.openInstantCheckout()">
                            <i class="fas fa-money-bill-wave"></i> คิดเงินทันที
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Instant Payment Modal for POS (Retail & Walk-in Checkout) -->
    <div class="modal-overlay" id="posCheckoutModal">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fas fa-cash-register"></i> รับชำระเงินหน้าร้าน</h3>
                <button class="modal-close" onclick="closeModal('posCheckoutModal')">&times;</button>
            </div>
            <div style="text-align: center; padding: 18px 0;">
                <div style="font-size: 38px; font-weight: 700; color: var(--primary);" id="posPayTotalDisplay">฿0.00</div>
                <p style="color: var(--gray); margin: 0;">ยอดรวมที่ต้องชำระ</p>
            </div>
            <form id="posCheckoutForm" onsubmit="staffPos.confirmInstantCheckout(event)">
                <div class="form-group">
                    <label>วิธีการชำระเงิน</label>
                    <select id="posPayMethod" required onchange="staffPos.toggleCashInput()">
                        <option value="cash">💵 เงินสด (Cash)</option>
                        <option value="promptpay">📱 พร้อมเพย์ (PromptPay QR)</option>
                        <option value="transfer">🏦 โอนเงินธนาคาร</option>
                        <option value="card">💳 บัตรเครดิต / เดบิต</option>
                    </select>
                </div>
                <div class="form-group" id="posCashGroup">
                    <label>รับเงินสดมา (บาท)</label>
                    <input type="number" id="posCashReceived" step="0.25" placeholder="ระบุจำนวนเงินที่รับจากลูกค้า" oninput="staffPos.updateChangeDisplay()">
                    <div style="display:flex;gap:6px;margin-top:8px;flex-wrap:wrap;">
                        <button type="button" class="btn btn-sm btn-outline" onclick="staffPos.setQuickCash('exact')">พอดี</button>
                        <button type="button" class="btn btn-sm btn-outline" onclick="staffPos.setQuickCash(20)">฿20</button>
                        <button type="button" class="btn btn-sm btn-outline" onclick="staffPos.setQuickCash(50)">฿50</button>
                        <button type="button" class="btn btn-sm btn-outline" onclick="staffPos.setQuickCash(100)">฿100</button>
                        <button type="button" class="btn btn-sm btn-outline" onclick="staffPos.setQuickCash(500)">฿500</button>
                        <button type="button" class="btn btn-sm btn-outline" onclick="staffPos.setQuickCash(1000)">฿1,000</button>
                    </div>
                </div>
                <div id="posChangeBox" style="display: none; text-align: center; padding: 14px; background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 10px; margin-bottom: 15px;">
                    <div style="font-size: 13px; color: #065F46;">เงินทอนลูกค้า</div>
                    <div style="font-size: 28px; font-weight: 700; color: #059669;" id="posChangeAmount">฿0.00</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('posCheckoutModal')">ยกเลิก</button>
                    <button type="submit" class="btn btn-success btn-lg"><i class="fas fa-check-circle"></i> ยืนยันรับเงิน & จบรายการ</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        window.ORDERPOS_POS_CONFIG = {
            shopType: <?= json_encode($currentShop['shop_type'] ?? 'restaurant') ?>
        };
    </script>
    <script src="assets/js/script.js?v=9"></script>
</body>
</html>
