<?php
require_once '../config/db.php';
require_once '../config/saas.php';
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$shopId = currentShopId();

switch ($method) {
    case 'GET':
        $result = query("SELECT * FROM tables WHERE shop_id = $shopId ORDER BY table_code ASC");
        $data = fetchAll($result);
        echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
        break;
        
    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        $table_code = escape($input['table_code']);
        $table_name = escape($input['table_name']);
        $capacity = intval($input['capacity'] ?? 4);
        
        query("INSERT INTO tables (shop_id, table_code, table_name, capacity) VALUES ($shopId, '$table_code', '$table_name', $capacity)");
        echo json_encode(['success' => true, 'message' => 'เพิ่มโต๊ะเรียบร้อย'], JSON_UNESCAPED_UNICODE);
        break;
        
    case 'PUT':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = intval($input['id']);
        $table_name = escape($input['table_name']);
        $capacity = intval($input['capacity'] ?? 4);
        $status = escape($input['status'] ?? 'available');
        
        query("UPDATE tables SET table_name='$table_name', capacity=$capacity, status='$status' WHERE id=$id AND shop_id=$shopId");
        echo json_encode(['success' => true, 'message' => 'อัปเดตโต๊ะเรียบร้อย'], JSON_UNESCAPED_UNICODE);
        break;
        
    case 'DELETE':
        $id = intval($_GET['id'] ?? 0);
        query("DELETE FROM tables WHERE id=$id AND shop_id=$shopId");
        echo json_encode(['success' => true, 'message' => 'ลบโต๊ะเรียบร้อย'], JSON_UNESCAPED_UNICODE);
        break;
}
