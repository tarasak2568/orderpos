<?php
require_once '../config/db.php';
require_once '../config/saas.php';
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$shopId = currentShopId();

switch ($method) {
    case 'GET':
        $includeUnavailable = isset($_GET['all']) && $_GET['all'] == '1';
        $where = "WHERE mi.shop_id = $shopId" . ($includeUnavailable ? "" : " AND mi.is_available = 1");

        if (isset($_GET['category_id']) && $_GET['category_id'] !== '') {
            $catId = intval($_GET['category_id']);
            $where .= " AND mi.category_id = $catId";
        }

        if (isset($_GET['search']) && trim($_GET['search']) !== '') {
            $search = escape(trim($_GET['search']));
            $where .= " AND (mi.name LIKE '%$search%' OR mi.sku LIKE '%$search%')";
        }

        $result = query("
            SELECT mi.*, c.name as category_name 
            FROM menu_items mi 
            LEFT JOIN categories c ON mi.category_id = c.id 
            $where 
            ORDER BY mi.sort_order ASC, mi.name ASC
        ");
        $data = fetchAll($result);

        foreach ($data as &$item) {
            $item['id'] = intval($item['id']);
            $item['shop_id'] = intval($item['shop_id']);
            $item['category_id'] = intval($item['category_id']);
            $item['sku'] = $item['sku'] ?? '';
            $item['price'] = floatval($item['price']);
            $item['stock_qty'] = ($item['stock_qty'] !== null && $item['stock_qty'] !== '') ? intval($item['stock_qty']) : null;
            $item['is_available'] = intval($item['is_available']);
            $item['is_recommended'] = intval($item['is_recommended']);
        }

        jsonResponse(['success' => true, 'data' => $data]);
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $category_id = intval($input['category_id'] ?? 0);
        $rawName = trim($input['name'] ?? '');
        if ($category_id <= 0 || $rawName === '') {
            jsonResponse(['success' => false, 'error' => 'กรุณาเลือกหมวดหมู่และระบุชื่อรายการ'], 400);
        }

        $sku = escape(trim($input['sku'] ?? ''));
        $name = escape($rawName);
        $description = escape($input['description'] ?? '');
        $price = max(0, floatval($input['price'] ?? 0));
        $stock_raw = $input['stock_qty'] ?? '';
        $stock_sql = ($stock_raw === '' || $stock_raw === null) ? 'NULL' : max(0, intval($stock_raw));
        $image = escape($input['image'] ?? '');
        $is_available = intval($input['is_available'] ?? 1) ? 1 : 0;
        $is_recommended = intval($input['is_recommended'] ?? 0) ? 1 : 0;

        $ok = query("INSERT INTO menu_items (shop_id, category_id, sku, name, description, price, stock_qty, image, is_available, is_recommended) 
               VALUES ($shopId, $category_id, '$sku', '$name', '$description', $price, $stock_sql, '$image', $is_available, $is_recommended)");
        if (!$ok) {
            jsonResponse(['success' => false, 'error' => 'ไม่สามารถบันทึกข้อมูลได้'], 500);
        }

        jsonResponse(['success' => true, 'message' => 'เพิ่มรายการเรียบร้อย', 'id' => $conn->insert_id]);
        break;

    case 'PUT':
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $id = intval($input['id'] ?? 0);
        $category_id = intval($input['category_id'] ?? 0);
        $rawName = trim($input['name'] ?? '');
        if ($id <= 0 || $category_id <= 0 || $rawName === '') {
            jsonResponse(['success' => false, 'error' => 'ข้อมูลไม่ครบถ้วนสำหรับการแก้ไข'], 400);
        }

        $sku = escape(trim($input['sku'] ?? ''));
        $name = escape($rawName);
        $description = escape($input['description'] ?? '');
        $price = max(0, floatval($input['price'] ?? 0));
        $stock_raw = $input['stock_qty'] ?? '';
        $stock_sql = ($stock_raw === '' || $stock_raw === null) ? 'NULL' : max(0, intval($stock_raw));
        $is_available = intval($input['is_available'] ?? 1) ? 1 : 0;
        $is_recommended = intval($input['is_recommended'] ?? 0) ? 1 : 0;

        $ok = query("UPDATE menu_items SET 
               category_id=$category_id, sku='$sku', name='$name', description='$description', 
               price=$price, stock_qty=$stock_sql, is_available=$is_available, is_recommended=$is_recommended 
               WHERE id=$id AND shop_id=$shopId");
        if (!$ok) {
            jsonResponse(['success' => false, 'error' => 'ไม่สามารถอัปเดตข้อมูลได้'], 500);
        }

        jsonResponse(['success' => true, 'message' => 'อัปเดตรายการเรียบร้อย']);
        break;

    case 'DELETE':
        $id = intval($_GET['id'] ?? 0);
        if ($id <= 0) {
            jsonResponse(['success' => false, 'error' => 'ไม่พบรหัสรายการที่ต้องการลบ'], 400);
        }
        $ok = query("DELETE FROM menu_items WHERE id=$id AND shop_id=$shopId");
        if (!$ok) {
            jsonResponse(['success' => false, 'error' => 'ไม่สามารถลบรายการได้'], 400);
        }
        jsonResponse(['success' => true, 'message' => 'ลบรายการเรียบร้อย']);
        break;

    default:
        jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}
