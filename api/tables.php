<?php
require_once '../config/db.php';
require_once '../config/saas.php';
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$shopId = currentShopId();
$allowedStatuses = ['available', 'occupied', 'reserved'];

switch ($method) {
    case 'GET':
        $result = query("SELECT * FROM tables WHERE shop_id = $shopId ORDER BY table_code ASC");
        $data = fetchAll($result);
        jsonResponse(['success' => true, 'data' => $data]);
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $table_code = trim($input['table_code'] ?? '');
        $table_name = trim($input['table_name'] ?? '');
        $capacity = max(1, intval($input['capacity'] ?? 4));
        $status = in_array($input['status'] ?? 'available', $allowedStatuses, true)
            ? $input['status']
            : 'available';

        if ($table_code === '' || $table_name === '') {
            jsonResponse(['success' => false, 'error' => 'กรุณากรอกรหัสโต๊ะและชื่อโต๊ะให้ครบถ้วน'], 400);
        }

        $safeCode = escape($table_code);
        $safeName = escape($table_name);
        $safeStatus = escape($status);

        $dup = fetchOne(query("SELECT id FROM tables WHERE shop_id = $shopId AND table_code = '$safeCode' LIMIT 1"));
        if ($dup) {
            jsonResponse(['success' => false, 'error' => 'รหัสโต๊ะนี้มีอยู่แล้วในร้านของคุณ'], 400);
        }

        $ok = query("INSERT INTO tables (shop_id, table_code, table_name, capacity, status) VALUES ($shopId, '$safeCode', '$safeName', $capacity, '$safeStatus')");
        if (!$ok) {
            jsonResponse(['success' => false, 'error' => 'ไม่สามารถเพิ่มโต๊ะได้'], 500);
        }
        jsonResponse(['success' => true, 'message' => 'เพิ่มโต๊ะเรียบร้อย', 'id' => $conn->insert_id]);
        break;

    case 'PUT':
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $id = intval($input['id'] ?? 0);
        $table_code = trim($input['table_code'] ?? '');
        $table_name = trim($input['table_name'] ?? '');
        $capacity = max(1, intval($input['capacity'] ?? 4));
        $status = in_array($input['status'] ?? 'available', $allowedStatuses, true)
            ? $input['status']
            : 'available';

        if ($id <= 0 || $table_name === '') {
            jsonResponse(['success' => false, 'error' => 'ข้อมูลโต๊ะไม่ครบถ้วน'], 400);
        }

        $safeName = escape($table_name);
        $safeStatus = escape($status);
        $setCodeSql = '';

        if ($table_code !== '') {
            $safeCode = escape($table_code);
            $dup = fetchOne(query("SELECT id FROM tables WHERE shop_id = $shopId AND table_code = '$safeCode' AND id != $id LIMIT 1"));
            if ($dup) {
                jsonResponse(['success' => false, 'error' => 'รหัสโต๊ะนี้ซ้ำกับโต๊ะอื่นในร้าน'], 400);
            }
            $setCodeSql = "table_code='$safeCode', ";
        }

        $ok = query("UPDATE tables SET {$setCodeSql}table_name='$safeName', capacity=$capacity, status='$safeStatus' WHERE id=$id AND shop_id=$shopId");
        if (!$ok) {
            jsonResponse(['success' => false, 'error' => 'ไม่สามารถอัปเดตข้อมูลโต๊ะได้'], 500);
        }
        jsonResponse(['success' => true, 'message' => 'อัปเดตโต๊ะเรียบร้อย']);
        break;

    case 'DELETE':
        $id = intval($_GET['id'] ?? 0);
        if ($id <= 0) {
            jsonResponse(['success' => false, 'error' => 'ไม่พบรหัสโต๊ะที่ต้องการลบ'], 400);
        }
        $ok = query("DELETE FROM tables WHERE id=$id AND shop_id=$shopId");
        if (!$ok) {
            jsonResponse(['success' => false, 'error' => 'ไม่สามารถลบโต๊ะได้ (อาจมีออเดอร์ค้างอยู่)'], 400);
        }
        jsonResponse(['success' => true, 'message' => 'ลบโต๊ะเรียบร้อย']);
        break;

    default:
        jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}
