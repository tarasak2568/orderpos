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

$shopId = currentShopId();
$currentShop = getCurrentShop($shopId);

// ดึงข้อมูลโต๊ะที่ยังไม่ได้ชำระเงินเฉพาะร้านนี้
$unpaidOrders = fetchAll(query("
    SELECT o.*, t.table_name, t.table_code 
    FROM orders o 
    LEFT JOIN tables t ON o.table_id = t.id 
    WHERE o.shop_id = $shopId AND o.payment_status = 'unpaid' AND o.status IN ('served', 'ready')
    ORDER BY o.created_at ASC
"));
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
    'title' => 'เก็บเงิน - ' . $currentShop['shop_name']
]) ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <a href="admin/dashboard.php" class="navbar-brand">
            <i class="fas fa-store"></i>
            <span><?= htmlspecialchars($currentShop['shop_name']) ?></span>
        </a>
        <ul class="navbar-nav">
            <li><a href="admin/dashboard.php"><i class="fas fa-home"></i> หน้าแรก</a></li>
            <li><a href="pos.php"><i class="fas fa-cash-register"></i> ขายหน้าร้าน</a></li>
            <li><a href="billing.php" class="active"><i class="fas fa-receipt"></i> เก็บเงิน</a></li>
        </ul>
        <div class="navbar-right">
            <span class="user-info"><i class="fas fa-user"></i> <?= $_SESSION['full_name'] ?></span>
            <a href="logout.php" class="btn btn-sm btn-danger"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
        </div>
    </nav>

    <div style="padding: 80px 20px 20px;">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-receipt"></i> เก็บเงิน</h3>
                <span style="color: var(--gray);"><?= count($unpaidOrders) ?> บิลที่รอชำระ</span>
            </div>
            <div class="card-body">
                <?php if (empty($unpaidOrders)): ?>
                    <div class="text-center text-gray" style="padding: 40px;">
                        <i class="fas fa-check-circle" style="font-size: 48px; color: var(--success); margin-bottom: 15px; display: block;"></i>
                        <h3>ไม่มีบิลที่รอชำระ</h3>
                    </div>
                <?php else: ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px;">
                        <?php foreach ($unpaidOrders as $order): 
                            $items = fetchAll(query("
                                SELECT oi.*, mi.name 
                                FROM order_items oi 
                                LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id 
                                WHERE oi.order_id = {$order['id']}
                            "));
                        ?>
                            <div style="background: var(--white); border: 2px solid #E0E0E0; border-radius: var(--radius); padding: 20px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #E0E0E0;">
                                    <div>
                                        <h3 style="font-size: 20px; color: var(--primary);"><?= htmlspecialchars($order['table_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></h3>
                                        <span style="font-size: 12px; color: var(--gray);">#<?= htmlspecialchars($order['order_no'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <span style="font-size: 24px; font-weight: 700; color: var(--primary);">฿<?= number_format($order['total_amount'], 2) ?></span>
                                </div>
                                <div style="margin-bottom: 15px;">
                                    <?php foreach ($items as $item): ?>
                                        <div style="display: flex; justify-content: space-between; padding: 5px 0; font-size: 14px; border-bottom: 1px solid #F0F0F0;">
                                            <span><?= intval($item['quantity']) ?>x <?= htmlspecialchars($item['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                                            <span>฿<?= number_format($item['price'] * $item['quantity'], 2) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <button class="btn btn-success btn-lg btn-block" onclick="openPayment(<?= intval($order['id']) ?>, <?= floatval($order['total_amount']) ?>, <?= htmlspecialchars(json_encode((string)($order['table_name'] ?? ''), JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>)">
                                    <i class="fas fa-money-bill"></i> รับชำระเงิน
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div class="modal-overlay" id="paymentModal">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fas fa-money-bill"></i> รับชำระเงิน - <span id="payTableName"></span></h3>
                <button class="modal-close" onclick="closeModal('paymentModal')">&times;</button>
            </div>
            <div style="text-align: center; padding: 20px 0;">
                <div style="font-size: 36px; font-weight: 700; color: var(--primary);" id="payAmount">฿0</div>
                <p style="color: var(--gray);">ยอดรวมทั้งสิ้น</p>
            </div>
            <form id="paymentForm" onsubmit="processPayment(event)">
                <input type="hidden" id="payOrderId" value="">
                <div class="form-group">
                    <label>วิธีการชำระเงิน</label>
                    <select id="payMethod" required onchange="toggleCashInput()">
                        <option value="cash">เงินสด</option>
                        <option value="promptpay">พร้อมเพย์</option>
                        <option value="transfer">โอนเงิน</option>
                        <option value="card">บัตรเครดิต/เดบิต</option>
                    </select>
                </div>
                <div class="form-group" id="cashReceivedGroup">
                    <label>รับเงินมา</label>
                    <input type="number" id="cashReceived" step="0.25" placeholder="จำนวนเงินที่รับ">
                </div>
                <div id="changeDisplay" style="display: none; text-align: center; padding: 15px; background: #E8F5E9; border-radius: 8px; margin-bottom: 15px;">
                    <div style="font-size: 14px; color: var(--gray);">เงินทอน</div>
                    <div style="font-size: 28px; font-weight: 700; color: var(--success);" id="changeAmount">฿0</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('paymentModal')">ยกเลิก</button>
                    <button type="submit" class="btn btn-success btn-lg"><i class="fas fa-check"></i> ยืนยันการชำระเงิน</button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
    let currentOrderId = 0;
    let currentAmount = 0;

    function openPayment(orderId, amount, tableName) {
        currentOrderId = orderId;
        currentAmount = amount;
        document.getElementById('payOrderId').value = orderId;
        document.getElementById('payAmount').textContent = formatCurrency(amount);
        document.getElementById('payTableName').textContent = tableName;
        document.getElementById('cashReceived').value = '';
        document.getElementById('changeDisplay').style.display = 'none';
        toggleCashInput();
        openModal('paymentModal');
    }

    function toggleCashInput() {
        const method = document.getElementById('payMethod').value;
        const cashGroup = document.getElementById('cashReceivedGroup');
        if (method === 'cash') {
            cashGroup.style.display = 'block';
        } else {
            cashGroup.style.display = 'none';
        }
    }

    // คำนวณเงินทอนอัตโนมัติ
    document.addEventListener('input', function(e) {
        if (e.target.id === 'cashReceived') {
            const received = parseFloat(e.target.value) || 0;
            if (received >= currentAmount) {
                const change = received - currentAmount;
                document.getElementById('changeAmount').textContent = formatCurrency(change);
                document.getElementById('changeDisplay').style.display = 'block';
            } else {
                document.getElementById('changeDisplay').style.display = 'none';
            }
        }
    });

    async function processPayment(event) {
        event.preventDefault();
        const orderId = document.getElementById('payOrderId').value;
        const method = document.getElementById('payMethod').value;
        const cashReceived = parseFloat(document.getElementById('cashReceived').value) || 0;

        if (method === 'cash' && cashReceived < currentAmount) {
            showToast('จำนวนเงินไม่เพียงพอ', 'warning');
            return;
        }

        try {
            const result = await API.post('orders.php?action=pay', {
                order_id: orderId,
                payment_method: method,
                cash_received: cashReceived
            });

            showToast('ชำระเงินเรียบร้อย เงินทอน ' + formatCurrency(result.data.change_amount), 'success');
            closeModal('paymentModal');
            setTimeout(() => location.reload(), 1000);
        } catch (error) {
            showToast('ไม่สามารถดำเนินการชำระเงินได้', 'error');
        }
    }
    </script>
</body>
</html>