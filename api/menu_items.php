<?php
require_once '../config/db.php';
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $where = "WHERE mi.is_available = 1";
        
        if (isset($_GET['category_id'])) {
            $catId = intval($_GET['category_id']);
            $where .= " AND mi.category_id = $catId";
        }
        
        if (isset($_GET['search'])) {
            $search = escape($_GET['search']);
            $where .= " AND mi.name LIKE '%$search%'";
        }
        
        $result = query("
            SELECT mi.*, c.name as category_name 
            FROM menu_items mi 
            LEFT JOIN categories c ON mi.category_id = c.id 
            $where 
            ORDER BY mi.sort_order ASC, mi.name ASC
        ");
        $data = fetchAll($result);
        
        // แปลง price และ id เป็นตัวเลข
        foreach ($data as &$item) {
            $item['id'] = intval($item['id']);
            $item['category_id'] = intval($item['category_id']);
            $item['price'] = floatval($item['price']);
            $item['is_available'] = intval($item['is_available']);
            $item['is_recommended'] = intval($item['is_recommended']);
        }
        
        echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
        break;
        
    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        $category_id = intval($input['category_id']);
        $name = escape($input['name']);
        $description = escape($input['description'] ?? '');
        $price = floatval($input['price']);
        $image = escape($input['image'] ?? '');
        $is_recommended = intval($input['is_recommended'] ?? 0);
        
        query("INSERT INTO menu_items (category_id, name, description, price, image, is_recommended) 
               VALUES ($category_id, '$name', '$description', $price, '$image', $is_recommended)");
        
        echo json_encode(['success' => true, 'message' => 'เพิ่มเมนูเรียบร้อย', 'id' => $conn->insert_id], JSON_UNESCAPED_UNICODE);
        break;
        
    case 'PUT':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = intval($input['id']);
        $category_id = intval($input['category_id']);
        $name = escape($input['name']);
        $description = escape($input['description'] ?? '');
        $price = floatval($input['price']);
        $is_available = intval($input['is_available'] ?? 1);
        $is_recommended = intval($input['is_recommended'] ?? 0);
        
        query("UPDATE menu_items SET 
               category_id=$category_id, name='$name', description='$description', 
               price=$price, is_available=$is_available, is_recommended=$is_recommended 
               WHERE id=$id");
        
        echo json_encode(['success' => true, 'message' => 'อัปเดตเมนูเรียบร้อย'], JSON_UNESCAPED_UNICODE);
        break;
        
    case 'DELETE':
        $id = intval($_GET['id'] ?? 0);
        query("DELETE FROM menu_items WHERE id=$id");
        echo json_encode(['success' => true, 'message' => 'ลบเมนูเรียบร้อย'], JSON_UNESCAPED_UNICODE);
        break;
}