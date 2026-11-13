<?php
require_once '../config/db.php';
require_once '../config/saas.php';
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$shopId = currentShopId();

switch ($action) {
    case 'create':
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            echo json_encode(['success' => false, 'error' => 'ข้อมูลไม่ครบถ้วน'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (!empty($input['shop_id'])) {
            $shopId = intval($input['shop_id']);
        }
        
        $rawTableCode = trim($input['table_id'] ?? '');
        if ($rawTableCode === '') {
            $rawTableCode = 'WALKIN';
        }
        $table_code = escape($rawTableCode);
        $note = escape($input['note'] ?? '');
        $items = $input['items'] ?? [];
        $instantPay = !empty($input['instant_pay']);
        $paymentMethod = escape($input['payment_method'] ?? 'cash');
        $cashReceived = floatval($input['cash_received'] ?? 0);
        $memberId = intval($input['member_id'] ?? 0);
        $pointsRedeemed = max(0, intval($input['points_redeemed'] ?? 0));
        $staffUserId = !empty($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
        $createdBySql = $staffUserId ? $staffUserId : 'NULL';
        
        if (empty($items)) {
            echo json_encode(['success' => false, 'error' => 'ไม่มีรายการสินค้า'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // หา table id จาก table_code ของร้านนี้
        $tableResult = query("SELECT id FROM tables WHERE shop_id = $shopId AND table_code = '$table_code' LIMIT 1");
        $table = fetchOne($tableResult);
        
        if (!$table) {
            if ($rawTableCode === 'WALKIN') {
                query("INSERT INTO tables (shop_id, table_code, table_name, capacity, status) VALUES ($shopId, 'WALKIN', 'ขายหน้าร้าน (Walk-in)', 1, 'available')");
                global $conn;
                $table = ['id' => $conn->insert_id];
            } else {
                $firstTable = fetchOne(query("SELECT id FROM tables WHERE shop_id = $shopId ORDER BY id ASC LIMIT 1"));
                if ($firstTable) {
                    $table = $firstTable;
                } else {
                    query("INSERT INTO tables (shop_id, table_code, table_name, capacity, status) VALUES ($shopId, '$table_code', 'จุดขาย $table_code', 4, 'available')");
                    global $conn;
                    $table = ['id' => $conn->insert_id];
                }
            }
        }
        
        $tableId = intval($table['id']);
        
        // สร้าง order_no (ป้องกัน duplicate)
        $orderNo = 'ORD-' . $shopId . '-' . date('ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // คำนวณยอดรวมตั้งต้นจากรายการสินค้า
        $grossTotal = 0;
        foreach ($items as $item) {
            $grossTotal += floatval($item['price']) * intval($item['quantity']);
        }
        $grossTotal = round($grossTotal, 2);

        $attachedMember = null;
        if ($memberId > 0) {
            $attachedMember = fetchOne(query("SELECT id, full_name, phone FROM members WHERE id = $memberId AND shop_id = $shopId AND is_active = 1 LIMIT 1"));
        }
        $memberSql = $attachedMember ? intval($attachedMember['id']) : 'NULL';
        $custNameSql = $attachedMember ? "'" . escape($attachedMember['full_name']) . "'" : (!empty($input['customer_name']) ? "'" . escape(trim($input['customer_name'])) . "'" : 'NULL');
        $custPhoneSql = $attachedMember ? "'" . escape($attachedMember['phone']) . "'" : (!empty($input['customer_phone']) ? "'" . escape(trim($input['customer_phone'])) . "'" : 'NULL');
        
        $initStatus = $instantPay ? 'paid' : 'pending';
        $initPayStatus = $instantPay ? 'paid' : 'unpaid';
        $payMethodSql = $instantPay ? "'$paymentMethod'" : "NULL";
        $paidAtSql = $instantPay ? "NOW()" : "NULL";

        // สร้างออเดอร์ผูกกับ shop_id
        $orderResult = query("INSERT INTO orders (shop_id, order_no, table_id, member_id, customer_name, customer_phone, note, total_amount, status, payment_status, payment_method, paid_at, created_by) 
               VALUES ($shopId, '$orderNo', $tableId, $memberSql, $custNameSql, $custPhoneSql, '$note', $grossTotal, '$initStatus', '$initPayStatus', $payMethodSql, $paidAtSql, $createdBySql)");
        
        if (!$orderResult) {
            global $conn;
            echo json_encode(['success' => false, 'error' => 'ไม่สามารถสร้างออเดอร์ได้: ' . $conn->error], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        global $conn;
        $orderId = $conn->insert_id;
        
        // เพิ่มรายการในออเดอร์ + ตัดสต๊อกสินค้าอัตโนมัติ (กรณีระบุ stock_qty)
        foreach ($items as $item) {
            $menuItemId = intval($item['menu_item_id']);
            $quantity = intval($item['quantity']);
            $price = floatval($item['price']);
            $itemNote = escape($item['note'] ?? '');
            $itemStatus = $instantPay ? 'served' : 'pending';
            
            query("INSERT INTO order_items (order_id, menu_item_id, quantity, price, note, status) 
                   VALUES ($orderId, $menuItemId, $quantity, $price, '$itemNote', '$itemStatus')");

            // ตัดสต๊อกคงเหลือถ้าสินค้านั้นเปิดใช้งานสต๊อก
            query("UPDATE menu_items SET stock_qty = GREATEST(0, stock_qty - $quantity) 
                   WHERE id = $menuItemId AND shop_id = $shopId AND stock_qty IS NOT NULL");
        }
        
        $netAmount = $grossTotal;
        $discountAmount = 0.0;
        $pointsEarned = 0;
        $pointsUsed = 0;
        $changeAmount = 0;

        if ($instantPay) {
            if ($attachedMember) {
                $loyaltyResult = applyMemberLoyaltyOnOrderPaid($shopId, $orderId, intval($attachedMember['id']), $grossTotal, $pointsRedeemed, $staffUserId);
                $netAmount = floatval($loyaltyResult['net_amount']);
                $discountAmount = floatval($loyaltyResult['discount_amount']);
                $pointsEarned = intval($loyaltyResult['points_earned']);
                $pointsUsed = intval($loyaltyResult['points_redeemed']);
            }
            if ($cashReceived <= 0) {
                $cashReceived = $netAmount;
            }
            $changeAmount = max(0, $cashReceived - $netAmount);
            query("INSERT INTO payments (shop_id, order_id, amount, payment_method, cash_received, change_amount, paid_by) 
                   VALUES ($shopId, $orderId, $netAmount, '$paymentMethod', $cashReceived, $changeAmount, $createdBySql)");
        } else {
            // อัปเดตสถานะโต๊ะเป็น occupied เฉพาะออเดอร์ที่ยังไม่จ่ายเงิน
            if ($rawTableCode !== 'WALKIN') {
                query("UPDATE tables SET status = 'occupied' WHERE id = $tableId AND shop_id = $shopId");
            }
        }
        
        echo json_encode([
            'success' => true, 
            'message' => $instantPay ? 'ชำระเงินและบันทึกยอดขายเรียบร้อย' : 'สร้างออเดอร์เรียบร้อย',
            'data' => [
                'order_id' => $orderId,
                'order_no' => $orderNo,
                'gross_amount' => $grossTotal,
                'discount_amount' => $discountAmount,
                'total_amount' => $netAmount,
                'points_earned' => $pointsEarned,
                'points_redeemed' => $pointsUsed,
                'change_amount' => $changeAmount
            ]
        ], JSON_UNESCAPED_UNICODE);
        break;
        
    case 'kitchen':
        // ดึงออเดอร์ที่ยังไม่เสร็จสำหรับครัวเฉพาะของร้านนี้
        $result = query("
            SELECT o.*, t.table_name, t.table_code 
            FROM orders o 
            LEFT JOIN tables t ON o.table_id = t.id 
            WHERE o.shop_id = $shopId AND o.status IN ('pending', 'preparing', 'ready') 
            ORDER BY o.created_at ASC
        ");
        
        $orders = fetchAll($result);
        
        foreach ($orders as &$order) {
            $itemsResult = query("
                SELECT oi.*, mi.name 
                FROM order_items oi 
                LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id 
                WHERE oi.order_id = {$order['id']}
            ");
            $items = fetchAll($itemsResult);
            $order['items'] = json_encode($items, JSON_UNESCAPED_UNICODE);
        }
        
        echo json_encode(['success' => true, 'data' => $orders], JSON_UNESCAPED_UNICODE);
        break;
        
    case 'status':
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $orderId = intval($input['order_id']);
        $status = escape($input['status']);
        
        $validStatuses = ['pending', 'preparing', 'ready', 'served', 'paid', 'cancelled'];
        if (!in_array($status, $validStatuses)) {
            echo json_encode(['success' => false, 'error' => 'สถานะไม่ถูกต้อง'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        if ($status === 'paid') {
            $orderResult = query("SELECT id, table_id, member_id, payment_status, total_amount, discount_amount, points_redeemed FROM orders WHERE id = $orderId AND shop_id = $shopId LIMIT 1");
            $order = fetchOne($orderResult);
            if ($order) {
                if (($order['payment_status'] ?? 'unpaid') !== 'paid' && !empty($order['member_id'])) {
                    $gross = floatval($order['total_amount']) + floatval($order['discount_amount'] ?? 0);
                    $staffUid = !empty($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
                    applyMemberLoyaltyOnOrderPaid($shopId, $orderId, intval($order['member_id']), $gross, intval($order['points_redeemed'] ?? 0), $staffUid);
                }
                query("UPDATE tables SET status = 'available' WHERE id = {$order['table_id']} AND shop_id = $shopId");
            }
            query("UPDATE orders SET status = 'paid', payment_status = 'paid', paid_at = NOW() WHERE id = $orderId AND shop_id = $shopId");
        } else {
            query("UPDATE orders SET status = '$status' WHERE id = $orderId AND shop_id = $shopId");
        }
        
        echo json_encode(['success' => true, 'message' => 'อัปเดตสถานะเรียบร้อย'], JSON_UNESCAPED_UNICODE);
        break;
        
    case 'list':
        $status = escape($_GET['status'] ?? '');
        $where = "WHERE o.shop_id = $shopId";
        if ($status) {
            $where .= " AND o.status = '$status'";
        }
        
        $result = query("
            SELECT o.*, t.table_name, t.table_code, m.full_name AS member_name, m.tier AS member_tier, m.member_code
            FROM orders o 
            LEFT JOIN tables t ON o.table_id = t.id 
            LEFT JOIN members m ON o.member_id = m.id
            $where 
            ORDER BY o.created_at DESC 
            LIMIT 50
        ");
        $orders = fetchAll($result);
        
        foreach ($orders as &$order) {
            $itemsResult = query("
                SELECT oi.*, mi.name 
                FROM order_items oi 
                LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id 
                WHERE oi.order_id = {$order['id']}
            ");
            $order['items'] = fetchAll($itemsResult);
        }
        
        echo json_encode(['success' => true, 'data' => $orders], JSON_UNESCAPED_UNICODE);
        break;
        
    case 'billing':
        $tableCode = escape($_GET['table_code'] ?? '');
        $where = "WHERE o.shop_id = $shopId AND o.payment_status = 'unpaid' AND o.status IN ('served', 'ready')";
        
        if ($tableCode) {
            $where .= " AND t.table_code = '$tableCode'";
        }
        
        $result = query("
            SELECT o.*, t.table_name, t.table_code 
            FROM orders o 
            LEFT JOIN tables t ON o.table_id = t.id 
            $where 
            ORDER BY o.created_at ASC
        ");
        $orders = fetchAll($result);
        
        foreach ($orders as &$order) {
            $itemsResult = query("
                SELECT oi.*, mi.name 
                FROM order_items oi 
                LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id 
                WHERE oi.order_id = {$order['id']}
            ");
            $order['items'] = fetchAll($itemsResult);
        }
        
        echo json_encode(['success' => true, 'data' => $orders], JSON_UNESCAPED_UNICODE);
        break;
        
    case 'pay':
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $orderId = intval($input['order_id'] ?? 0);
        $paymentMethod = escape($input['payment_method'] ?? 'cash');
        $cashReceived = floatval($input['cash_received'] ?? 0);
        $memberId = intval($input['member_id'] ?? 0);
        $pointsRedeemed = max(0, intval($input['points_redeemed'] ?? 0));
        $staffUserId = !empty($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
        $paidBySql = $staffUserId ? $staffUserId : 'NULL';
        
        $orderResult = query("SELECT * FROM orders WHERE id = $orderId AND shop_id = $shopId LIMIT 1");
        $order = fetchOne($orderResult);
        
        if (!$order) {
            echo json_encode(['success' => false, 'error' => 'ไม่พบออเดอร์'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (($order['payment_status'] ?? 'unpaid') === 'paid') {
            jsonResponse(['success' => false, 'error' => 'บิลนี้ชำระเงินเรียบร้อยแล้ว'], 400);
        }

        $effectiveMemberId = $memberId > 0 ? $memberId : intval($order['member_id'] ?? 0);
        $grossTotal = floatval($order['total_amount']) + floatval($order['discount_amount'] ?? 0);
        $loyaltyResult = applyMemberLoyaltyOnOrderPaid($shopId, $orderId, $effectiveMemberId, $grossTotal, $pointsRedeemed, $staffUserId);
        $netAmount = floatval($loyaltyResult['net_amount']);

        if ($paymentMethod === 'cash' && $cashReceived < $netAmount) {
            jsonResponse(['success' => false, 'error' => 'จำนวนเงินสดที่รับมาไม่เพียงพอ'], 400);
        }
        if ($paymentMethod !== 'cash' && $cashReceived <= 0) {
            $cashReceived = $netAmount;
        }
        
        $changeAmount = max(0, $cashReceived - $netAmount);
        
        query("INSERT INTO payments (shop_id, order_id, amount, payment_method, cash_received, change_amount, paid_by) 
               VALUES ($shopId, $orderId, $netAmount, '$paymentMethod', $cashReceived, $changeAmount, $paidBySql)");
        
        query("UPDATE orders SET 
               payment_status = 'paid', 
               status = 'paid',
               payment_method = '$paymentMethod',
               paid_at = NOW() 
               WHERE id = $orderId AND shop_id = $shopId");
        
        $tableId = intval($order['table_id']);
        query("UPDATE tables SET status = 'available' WHERE id = $tableId AND shop_id = $shopId");
        
        echo json_encode([
            'success' => true, 
            'message' => 'ชำระเงินเรียบร้อย',
            'data' => [
                'net_amount' => $netAmount,
                'discount_amount' => floatval($loyaltyResult['discount_amount']),
                'points_earned' => intval($loyaltyResult['points_earned']),
                'points_redeemed' => intval($loyaltyResult['points_redeemed']),
                'change_amount' => $changeAmount
            ]
        ], JSON_UNESCAPED_UNICODE);
        break;
        
    case 'dashboard':
        $today = date('Y-m-d');
        
        // ยอดขายวันนี้เฉพาะร้านนี้
        $salesResult = query("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE shop_id = $shopId AND DATE(created_at) = '$today' AND payment_status = 'paid'");
        $sales = fetchOne($salesResult);
        
        // จำนวนออเดอร์วันนี้
        $orderCountResult = query("SELECT COUNT(*) as count FROM orders WHERE shop_id = $shopId AND DATE(created_at) = '$today'");
        $orderCount = fetchOne($orderCountResult);
        
        // ออเดอร์ที่รอดำเนินการ
        $pendingResult = query("SELECT COUNT(*) as count FROM orders WHERE shop_id = $shopId AND status IN ('pending', 'preparing', 'ready')");
        $pending = fetchOne($pendingResult);
        
        // โต๊ะที่ถูกใช้งาน
        $occupiedResult = query("SELECT COUNT(*) as count FROM tables WHERE shop_id = $shopId AND status = 'occupied'");
        $occupied = fetchOne($occupiedResult);
        
        // ออเดอร์ล่าสุด
        $recentResult = query("
            SELECT o.*, t.table_name 
            FROM orders o 
            LEFT JOIN tables t ON o.table_id = t.id 
            WHERE o.shop_id = $shopId
            ORDER BY o.created_at DESC 
            LIMIT 10
        ");
        $recentOrders = fetchAll($recentResult);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'today_sales' => floatval($sales['total']),
                'today_orders' => intval($orderCount['count']),
                'pending_orders' => intval($pending['count']),
                'occupied_tables' => intval($occupied['count']),
                'recent_orders' => $recentOrders
            ]
        ], JSON_UNESCAPED_UNICODE);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action'], JSON_UNESCAPED_UNICODE);
        break;
}
