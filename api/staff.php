<?php
require_once '../config/db.php';
require_once '../config/saas.php';
header('Content-Type: application/json; charset=utf-8');

requireAuthApi(['superadmin', 'admin']);

$method = $_SERVER['REQUEST_METHOD'];
$shopId = intval($_SESSION['shop_id'] ?? 1);
$currentUserId = intval($_SESSION['user_id'] ?? 0);
$allowedStaffRoles = ['admin', 'staff', 'kitchen'];

switch ($method) {
    case 'GET':
        $q = trim($_GET['q'] ?? '');
        $roleFilter = trim($_GET['role'] ?? '');
        $where = "WHERE shop_id = $shopId";

        if ($q !== '') {
            $escQ = escape($q);
            $where .= " AND (username LIKE '%$escQ%' OR full_name LIKE '%$escQ%')";
        }
        if ($roleFilter !== '' && in_array($roleFilter, ['superadmin', 'admin', 'staff', 'kitchen'], true)) {
            $escRole = escape($roleFilter);
            $where .= " AND role = '$escRole'";
        }

        $users = fetchAll(query("
            SELECT id, shop_id, username, full_name, role, is_active, created_at, updated_at
            FROM users
            $where
            ORDER BY FIELD(role, 'superadmin', 'admin', 'staff', 'kitchen'), id ASC
        "));

        foreach ($users as &$u) {
            $u['id'] = intval($u['id']);
            $u['shop_id'] = intval($u['shop_id']);
            $u['is_active'] = intval($u['is_active'] ?? 1);
            $u['is_self'] = ($u['id'] === $currentUserId);
            $u['role_info'] = getRoleDisplayInfo($u['role']);
        }
        unset($u);

        jsonResponse(['success' => true, 'data' => $users]);
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $username = trim($input['username'] ?? '');
        $fullName = trim($input['full_name'] ?? '');
        $password = (string)($input['password'] ?? '');
        $role = trim($input['role'] ?? 'staff');
        $isActive = isset($input['is_active']) ? (intval($input['is_active']) ? 1 : 0) : 1;

        if (!preg_match('/^[a-zA-Z0-9_.-]{3,50}$/', $username)) {
            jsonResponse(['success' => false, 'error' => 'ชื่อผู้ใช้ต้องเป็นภาษาอังกฤษ ตัวเลข หรือ _ . - ยาว 3-50 ตัวอักษร'], 400);
        }
        if ($fullName === '') {
            jsonResponse(['success' => false, 'error' => 'กรุณาระบุชื่อ-นามสกุลพนักงาน'], 400);
        }
        if (strlen($password) < 6) {
            jsonResponse(['success' => false, 'error' => 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร'], 400);
        }
        if (!in_array($role, $allowedStaffRoles, true)) {
            jsonResponse(['success' => false, 'error' => 'สิทธิ์ผู้ใช้งานไม่ถูกต้อง'], 400);
        }

        $escUsername = escape($username);
        $dup = fetchOne(query("SELECT id FROM users WHERE username = '$escUsername' LIMIT 1"));
        if ($dup) {
            jsonResponse(['success' => false, 'error' => 'ชื่อผู้ใช้นี้ถูกใช้งานแล้วในระบบ กรุณาใช้ชื่ออื่น'], 409);
        }

        $escFullName = escape($fullName);
        $escRole = escape($role);
        $hash = escape(password_hash($password, PASSWORD_DEFAULT));

        $ok = query("INSERT INTO users (shop_id, username, password, full_name, role, is_active)
                     VALUES ($shopId, '$escUsername', '$hash', '$escFullName', '$escRole', $isActive)");
        if (!$ok) {
            jsonResponse(['success' => false, 'error' => 'ไม่สามารถเพิ่มพนักงานได้'], 500);
        }

        global $conn;
        jsonResponse([
            'success' => true,
            'message' => 'เพิ่มบัญชีพนักงานเรียบร้อยแล้ว',
            'id' => intval($conn->insert_id)
        ]);
        break;

    case 'PUT':
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $id = intval($input['id'] ?? 0);
        if ($id <= 0) {
            jsonResponse(['success' => false, 'error' => 'ไม่พบรหัสผู้ใช้งาน'], 400);
        }

        $target = fetchOne(query("SELECT id, shop_id, role, is_active FROM users WHERE id = $id AND shop_id = $shopId LIMIT 1"));
        if (!$target) {
            jsonResponse(['success' => false, 'error' => 'ไม่พบพนักงานในร้านของคุณ'], 404);
        }
        if ($target['role'] === 'superadmin' && !hasAnyRole(['superadmin'])) {
            jsonResponse(['success' => false, 'error' => 'ไม่สามารถแก้ไขบัญชี Super Admin จากหน้าจัดการร้านได้'], 403);
        }

        $updates = [];
        if (isset($input['full_name'])) {
            $fullName = trim($input['full_name']);
            if ($fullName === '') {
                jsonResponse(['success' => false, 'error' => 'กรุณาระบุชื่อ-นามสกุล'], 400);
            }
            $updates[] = "full_name = '" . escape($fullName) . "'";
        }

        if (isset($input['role']) && $target['role'] !== 'superadmin') {
            $newRole = trim($input['role']);
            if (!in_array($newRole, $allowedStaffRoles, true)) {
                jsonResponse(['success' => false, 'error' => 'สิทธิ์ผู้ใช้งานไม่ถูกต้อง'], 400);
            }
            if ($id === $currentUserId && $newRole !== 'admin') {
                jsonResponse(['success' => false, 'error' => 'ไม่สามารถลดสิทธิ์บัญชีที่กำลังใช้งานอยู่ได้'], 400);
            }
            $updates[] = "role = '" . escape($newRole) . "'";
        }

        if (isset($input['is_active'])) {
            $newActive = intval($input['is_active']) ? 1 : 0;
            if ($id === $currentUserId && $newActive === 0) {
                jsonResponse(['success' => false, 'error' => 'ไม่สามารถระงับบัญชีของตัวเองที่กำลังใช้งานอยู่ได้'], 400);
            }
            $updates[] = "is_active = $newActive";
        }

        if (!empty($input['password'])) {
            $newPass = (string)$input['password'];
            if (strlen($newPass) < 6) {
                jsonResponse(['success' => false, 'error' => 'รหัสผ่านใหม่ต้องมีอย่างน้อย 6 ตัวอักษร'], 400);
            }
            $hash = escape(password_hash($newPass, PASSWORD_DEFAULT));
            $updates[] = "password = '$hash'";
        }

        if (empty($updates)) {
            jsonResponse(['success' => false, 'error' => 'ไม่มีข้อมูลที่ต้องการอัปเดต'], 400);
        }

        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = $id AND shop_id = $shopId";
        if (!query($sql)) {
            jsonResponse(['success' => false, 'error' => 'ไม่สามารถอัปเดตข้อมูลได้'], 500);
        }

        if ($id === $currentUserId && isset($input['full_name'])) {
            $_SESSION['full_name'] = trim($input['full_name']);
        }

        jsonResponse(['success' => true, 'message' => 'อัปเดตข้อมูลพนักงานเรียบร้อยแล้ว']);
        break;

    case 'DELETE':
        $id = intval($_GET['id'] ?? 0);
        if ($id <= 0) {
            jsonResponse(['success' => false, 'error' => 'ไม่พบรหัสพนักงาน'], 400);
        }
        if ($id === $currentUserId) {
            jsonResponse(['success' => false, 'error' => 'ไม่สามารถลบบัญชีของตัวเองที่กำลังล็อกอินอยู่ได้'], 400);
        }

        $target = fetchOne(query("SELECT id, role FROM users WHERE id = $id AND shop_id = $shopId LIMIT 1"));
        if (!$target) {
            jsonResponse(['success' => false, 'error' => 'ไม่พบพนักงานในร้านของคุณ'], 404);
        }
        if ($target['role'] === 'superadmin') {
            jsonResponse(['success' => false, 'error' => 'ไม่สามารถลบบัญชี Super Admin ได้'], 403);
        }

        if (!query("DELETE FROM users WHERE id = $id AND shop_id = $shopId")) {
            jsonResponse(['success' => false, 'error' => 'ไม่สามารถลบบัญชีพนักงานได้'], 500);
        }

        jsonResponse(['success' => true, 'message' => 'ลบบัญชีพนักงานเรียบร้อยแล้ว']);
        break;

    default:
        jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}
