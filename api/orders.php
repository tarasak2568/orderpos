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
        
        if (!$input || !isset($input['table_id'])) {
            echo json_encode(['success' => false, 'error' => 'ข้อมูลไม่ครบถ้วน'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (!empty($input['shop_id'])) {
            $shopId = intval($input['shop_id']);
        }
        
        $table_code = escape($input['table_id']);
        $note = escape($input['note'] ?? '');
        $items = $input['items'] ?? [];
        
        if (empty($items)) {
            echo json_encode(['success' => false, 'error' => 'ไม่มีรายการอาหาร'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // หา table id จาก table_code ของร้านนี้
        $tableResult = query("SELECT id FROM tables WHERE shop_id = $shopId AND table_code = '$table_code' LIMIT 1");
        $table = fetchOne($tableResult);
        
        if (!$table) {
            // ถ้ายังไม่มีรหัสโต๊ะนี้ในร้าน ให้หยิบโต๊ะแรกของร้านหรือสร้างให้อัตโนมัติ
            $firstTable = fetchOne(query("SELECT id FROM tables WHERE shop_id = $shopId ORDER BY id ASC LIMIT 1"));
            if ($firstTable) {
                $table = $firstTable;
            } else {
                query("INSERT INTO tables (shop_id, table_code, table_name, capacity, status) VALUES ($shopId, '$table_code', 'โต๊ะ $table_code', 4, 'occupied')");
                global $conn;
                $table = ['id' => $conn->insert_id];
            }
        }
        
        $tableId = intval($table['id']);
        
        // สร้าง order_no (ป้องกัน duplicate)
        $orderNo = 'ORD-' . $shopId . '-' . date('ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // คำนวณยอดรวม
        $totalAmount = 0;
        foreach ($items as $item) {
            $totalAmount += floatval($item['price']) * intval($item['quantity']);
        }
        
        // สร้างออเดอร์ผูกกับ shop_id
        $orderResult = query("INSERT INTO orders (shop_id, order_no, table_id, note, total_amount, status, payment_status) 
               VALUES ($shopId, '$orderNo', $tableId, '$note', $totalAmount, 'pending', 'unpaid')");
        
        if (!$orderResult) {
            global $conn;
            echo json_encode(['success' => false, 'error' => 'ไม่สามารถสร้างออเดอร์ได้: ' . $conn->error], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        global $conn;
        $orderId = $conn->insert_id;
        
        // เพิ่มรายการในออเดอร์
        foreach ($items as $item) {
            $menuItemId = intval($item['menu_item_id']);
            $quantity = intval($item['quantity']);
            $price = floatval($item['price']);
            $itemNote = escape($item['note'] ?? '');
            
            query("INSERT INTO order_items (order_id, menu_item_id, quantity, price, note) 
                   VALUES ($orderId, $menuItemId, $quantity, $price, '$itemNote')");
        }
        
        // อัปเดตสถานะโต๊ะเป็น occupied
        query("UPDATE tables SET status = 'occupied' WHERE id = $tableId AND shop_id = $shopId");
        
        echo json_encode([
            'success' => true, 
            'message' => 'สร้างออเดอร์เรียบร้อย',
            'data' => [
                'order_id' => $orderId,
                'order_no' => $orderNo
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
        
        query("UPDATE orders SET status = '$status' WHERE id = $orderId AND shop_id = $shopId");
        
        if ($status === 'paid') {
            $orderResult = query("SELECT table_id FROM orders WHERE id = $orderId AND shop_id = $shopId");
            $order = fetchOne($orderResult);
            if ($order) {
                query("UPDATE tables SET status = 'available' WHERE id = {$order['table_id']} AND shop_id = $shopId");
            }
            query("UPDATE orders SET payment_status = 'paid', paid_at = NOW() WHERE id = $orderId AND shop_id = $shopId");
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
            SELECT o.*, t.table_name, t.table_code 
            FROM orders o 
            LEFT JOIN tables t ON o.table_id = t.id 
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
        $orderId = intval($input['order_id']);
        $paymentMethod = escape($input['payment_method'] ?? 'cash');
        $cashReceived = floatval($input['cash_received'] ?? 0);
        
        $orderResult = query("SELECT * FROM orders WHERE id = $orderId AND shop_id = $shopId");
        $order = fetchOne($orderResult);
        
        if (!$order) {
            echo json_encode(['success' => false, 'error' => 'ไม่พบออเดอร์'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $changeAmount = $cashReceived - floatval($order['total_amount']);
        
        query("INSERT INTO payments (shop_id, order_id, amount, payment_method, cash_received, change_amount) 
               VALUES ($shopId, $orderId, {$order['total_amount']}, '$paymentMethod', $cashReceived, $changeAmount)");
        
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
