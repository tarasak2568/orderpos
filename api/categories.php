<?php
require_once '../config/db.php';
require_once '../config/saas.php';
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$shopId = currentShopId();

switch ($method) {
    case 'GET':
        $result = query("SELECT * FROM categories WHERE shop_id = $shopId AND is_active = 1 ORDER BY sort_order ASC");
        $data = fetchAll($result);
        foreach ($data as &$cat) {
            $cat['id'] = intval($cat['id']);
            $cat['shop_id'] = intval($cat['shop_id']);
        }
        echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
        break;
        
    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        $name = escape($input['name']);
        $icon = escape($input['icon'] ?? 'fa-utensils');
        $sort_order = intval($input['sort_order'] ?? 0);
        
        query("INSERT INTO categories (shop_id, name, icon, sort_order) VALUES ($shopId, '$name', '$icon', $sort_order)");
        echo json_encode(['success' => true, 'message' => 'เพิ่มหมวดหมู่เรียบร้อย'], JSON_UNESCAPED_UNICODE);
        break;
        
    case 'PUT':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = intval($input['id']);
        $name = escape($input['name']);
        $icon = escape($input['icon'] ?? 'fa-utensils');
        
        query("UPDATE categories SET name='$name', icon='$icon' WHERE id=$id AND shop_id=$shopId");
        echo json_encode(['success' => true, 'message' => 'อัปเดตหมวดหมู่เรียบร้อย'], JSON_UNESCAPED_UNICODE);
        break;
        
    case 'DELETE':
        $id = intval($_GET['id'] ?? 0);
        query("DELETE FROM categories WHERE id=$id AND shop_id=$shopId");
        echo json_encode(['success' => true, 'message' => 'ลบหมวดหมู่เรียบร้อย'], JSON_UNESCAPED_UNICODE);
        break;
}
