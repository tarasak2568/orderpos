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
        
        echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
        break;
        
    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        $category_id = intval($input['category_id']);
        $sku = escape(trim($input['sku'] ?? ''));
        $name = escape(trim($input['name'] ?? ''));
        $description = escape($input['description'] ?? '');
        $price = floatval($input['price'] ?? 0);
        $stock_raw = $input['stock_qty'] ?? '';
        $stock_sql = ($stock_raw === '' || $stock_raw === null) ? 'NULL' : intval($stock_raw);
        $image = escape($input['image'] ?? '');
        $is_recommended = intval($input['is_recommended'] ?? 0);
        
        query("INSERT INTO menu_items (shop_id, category_id, sku, name, description, price, stock_qty, image, is_recommended) 
               VALUES ($shopId, $category_id, '$sku', '$name', '$description', $price, $stock_sql, '$image', $is_recommended)");
        
        echo json_encode(['success' => true, 'message' => 'เพิ่มรายการเรียบร้อย', 'id' => $conn->insert_id], JSON_UNESCAPED_UNICODE);
        break;
        
    case 'PUT':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = intval($input['id']);
        $category_id = intval($input['category_id']);
        $sku = escape(trim($input['sku'] ?? ''));
        $name = escape(trim($input['name'] ?? ''));
        $description = escape($input['description'] ?? '');
        $price = floatval($input['price'] ?? 0);
        $stock_raw = $input['stock_qty'] ?? '';
        $stock_sql = ($stock_raw === '' || $stock_raw === null) ? 'NULL' : intval($stock_raw);
        $is_available = intval($input['is_available'] ?? 1);
        $is_recommended = intval($input['is_recommended'] ?? 0);
        
        query("UPDATE menu_items SET 
               category_id=$category_id, sku='$sku', name='$name', description='$description', 
               price=$price, stock_qty=$stock_sql, is_available=$is_available, is_recommended=$is_recommended 
               WHERE id=$id AND shop_id=$shopId");
        
        echo json_encode(['success' => true, 'message' => 'อัปเดตรายการเรียบร้อย'], JSON_UNESCAPED_UNICODE);
        break;
        
    case 'DELETE':
        $id = intval($_GET['id'] ?? 0);
        query("DELETE FROM menu_items WHERE id=$id AND shop_id=$shopId");
        echo json_encode(['success' => true, 'message' => 'ลบรายการเรียบร้อย'], JSON_UNESCAPED_UNICODE);
        break;
}
