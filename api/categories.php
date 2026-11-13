<?php
require_once '../config/db.php';
require_once '../config/saas.php';
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$shopId = currentShopId();

switch ($method) {
    case 'GET':
        $result = query("SELECT * FROM categories WHERE shop_id = $shopId AND is_active = 1 ORDER BY sort_order ASC, id ASC");
        $data = fetchAll($result);
        foreach ($data as &$cat) {
            $cat['id'] = intval($cat['id']);
            $cat['shop_id'] = intval($cat['shop_id']);
        }
        jsonResponse(['success' => true, 'data' => $data]);
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $name = trim($input['name'] ?? '');
        if ($name === '') {
            jsonResponse(['success' => false, 'error' => 'กรุณาระบุชื่อหมวดหมู่'], 400);
        }
        $safeName = escape($name);
        $icon = escape($input['icon'] ?? 'fa-utensils');
        $sort_order = intval($input['sort_order'] ?? 0);

        $ok = query("INSERT INTO categories (shop_id, name, icon, sort_order) VALUES ($shopId, '$safeName', '$icon', $sort_order)");
        if (!$ok) {
            jsonResponse(['success' => false, 'error' => 'ไม่สามารถเพิ่มหมวดหมู่ได้'], 500);
        }
        jsonResponse(['success' => true, 'message' => 'เพิ่มหมวดหมู่เรียบร้อย', 'id' => $conn->insert_id]);
        break;

    case 'PUT':
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $id = intval($input['id'] ?? 0);
        $name = trim($input['name'] ?? '');
        if ($id <= 0 || $name === '') {
            jsonResponse(['success' => false, 'error' => 'ข้อมูลหมวดหมู่ไม่ครบถ้วน'], 400);
        }
        $safeName = escape($name);
        $icon = escape($input['icon'] ?? 'fa-utensils');
        $sort_order = isset($input['sort_order']) ? ", sort_order=" . intval($input['sort_order']) : '';

        $ok = query("UPDATE categories SET name='$safeName', icon='$icon'{$sort_order} WHERE id=$id AND shop_id=$shopId");
        if (!$ok) {
            jsonResponse(['success' => false, 'error' => 'ไม่สามารถอัปเดตหมวดหมู่ได้'], 500);
        }
        jsonResponse(['success' => true, 'message' => 'อัปเดตหมวดหมู่เรียบร้อย']);
        break;

    case 'DELETE':
        $id = intval($_GET['id'] ?? 0);
        if ($id <= 0) {
            jsonResponse(['success' => false, 'error' => 'ไม่พบรหัสหมวดหมู่'], 400);
        }
        $ok = query("DELETE FROM categories WHERE id=$id AND shop_id=$shopId");
        if (!$ok) {
            jsonResponse(['success' => false, 'error' => 'ไม่สามารถลบหมวดหมู่ได้ (อาจมีรายการสินค้าผูกอยู่)'], 400);
        }
        jsonResponse(['success' => true, 'message' => 'ลบหมวดหมู่เรียบร้อย']);
        break;

    default:
        jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}
