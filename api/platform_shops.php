<?php
require_once '../config/db.php';
require_once '../config/saas.php';
require_once '../config/seo.php';
header('Content-Type: application/json; charset=utf-8');

requireAuthApi(['superadmin']);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$allowedPlans = ['free', 'pro', 'enterprise'];
$allowedShopTypes = ['restaurant', 'retail'];

if ($method === 'GET') {
    if ($action === 'shop_users') {
        $targetShopId = intval($_GET['shop_id'] ?? 0);
        if ($targetShopId <= 0) {
            jsonResponse(['success' => false, 'error' => 'ไม่พบรหัสร้านค้า'], 400);
        }
        $users = fetchAll(query("
            SELECT id, shop_id, username, full_name, role, is_active, created_at
            FROM users
            WHERE shop_id = $targetShopId
            ORDER BY FIELD(role, 'superadmin', 'admin', 'staff', 'kitchen'), id ASC
        "));
        foreach ($users as &$u) {
            $u['id'] = intval($u['id']);
            $u['is_active'] = intval($u['is_active'] ?? 1);
            $u['role_info'] = getRoleDisplayInfo($u['role']);
        }
        unset($u);
        jsonResponse(['success' => true, 'data' => $users]);
    }

    $q = trim($_GET['q'] ?? '');
    $shopType = trim($_GET['shop_type'] ?? '');
    $planType = trim($_GET['plan_type'] ?? '');
    $statusFilter = $_GET['status'] ?? '';

    $where = "WHERE 1=1";
    if ($q !== '') {
        $escQ = escape($q);
        $where .= " AND (s.shop_name LIKE '%$escQ%' OR s.shop_slug LIKE '%$escQ%' OR s.phone LIKE '%$escQ%')";
    }
    if ($shopType !== '' && in_array($shopType, $allowedShopTypes, true)) {
        $escType = escape($shopType);
        $where .= " AND s.shop_type = '$escType'";
    }
    if ($planType !== '' && in_array($planType, $allowedPlans, true)) {
        $escPlan = escape($planType);
        $where .= " AND s.plan_type = '$escPlan'";
    }
    if ($statusFilter === '1' || $statusFilter === '0') {
        $where .= " AND s.is_active = " . intval($statusFilter);
    }

    $shops = fetchAll(query("
        SELECT
            s.*,
            (SELECT COUNT(*) FROM users u WHERE u.shop_id = s.id) AS users_count,
            (SELECT u.username FROM users u WHERE u.shop_id = s.id ORDER BY FIELD(u.role, 'superadmin', 'admin', 'staff', 'kitchen'), u.id ASC LIMIT 1) AS owner_username,
            (SELECT COUNT(*) FROM members m WHERE m.shop_id = s.id) AS members_count,
            (SELECT COUNT(*) FROM orders o WHERE o.shop_id = s.id) AS orders_count,
            (SELECT COALESCE(SUM(o.total_amount), 0) FROM orders o WHERE o.shop_id = s.id AND o.payment_status = 'paid') AS paid_revenue
        FROM shops s
        $where
        ORDER BY s.id DESC
    "));

    $activeShopId = intval($_SESSION['shop_id'] ?? 1);
    foreach ($shops as &$shop) {
        $shop['id'] = intval($shop['id']);
        $shop['is_active'] = intval($shop['is_active'] ?? 1);
        $shop['users_count'] = intval($shop['users_count'] ?? 0);
        $shop['members_count'] = intval($shop['members_count'] ?? 0);
        $shop['orders_count'] = intval($shop['orders_count'] ?? 0);
        $shop['paid_revenue'] = floatval($shop['paid_revenue'] ?? 0);
        $shop['is_current_session'] = ($shop['id'] === $activeShopId);
    }
    unset($shop);

    $summary = fetchOne(query("
        SELECT
            (SELECT COUNT(*) FROM shops) AS total_shops,
            (SELECT COUNT(*) FROM shops WHERE is_active = 1) AS active_shops,
            (SELECT COUNT(*) FROM users) AS total_users,
            (SELECT COUNT(*) FROM members) AS total_members,
            (SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE payment_status = 'paid') AS total_revenue
    "));

    jsonResponse([
        'success' => true,
        'data' => $shops,
        'stats' => [
            'total_shops' => intval($summary['total_shops'] ?? 0),
            'active_shops' => intval($summary['active_shops'] ?? 0),
            'total_users' => intval($summary['total_users'] ?? 0),
            'total_members' => intval($summary['total_members'] ?? 0),
            'total_revenue' => floatval($summary['total_revenue'] ?? 0),
        ],
    ]);
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];

    if ($action === 'switch_shop') {
        $targetShopId = intval($input['shop_id'] ?? 0);
        $shop = getShopById($targetShopId);
        if (!$shop) {
            jsonResponse(['success' => false, 'error' => 'ไม่พบร้านค้าที่เลือก'], 404);
        }
        $_SESSION['shop_id'] = intval($shop['id']);
        $_SESSION['shop_name'] = $shop['shop_name'];
        $_SESSION['shop_slug'] = $shop['shop_slug'];
        $_SESSION['shop_type'] = $shop['shop_type'] ?? 'restaurant';
        jsonResponse([
            'success' => true,
            'message' => 'สลับมุมมองจัดการไปยังร้าน "' . $shop['shop_name'] . '" เรียบร้อยแล้ว',
        ]);
    }

    if ($action === 'reset_user_password') {
        $userId = intval($input['user_id'] ?? 0);
        $newPassword = (string)($input['password'] ?? '');
        if ($userId <= 0 || strlen($newPassword) < 6) {
            jsonResponse(['success' => false, 'error' => 'กรุณาระบุรหัสผ่านใหม่อย่างน้อย 6 ตัวอักษร'], 400);
        }
        $target = fetchOne(query("SELECT id FROM users WHERE id = $userId LIMIT 1"));
        if (!$target) {
            jsonResponse(['success' => false, 'error' => 'ไม่พบบัญชีผู้ใช้งาน'], 404);
        }
        $hash = escape(password_hash($newPassword, PASSWORD_DEFAULT));
        if (!query("UPDATE users SET password = '$hash' WHERE id = $userId")) {
            jsonResponse(['success' => false, 'error' => 'ไม่สามารถเปลี่ยนรหัสผ่านได้'], 500);
        }
        jsonResponse(['success' => true, 'message' => 'รีเซ็ตรหัสผ่านเรียบร้อยแล้ว']);
    }

    if ($action === 'create_shop') {
        $shopName = trim($input['shop_name'] ?? '');
        $shopType = ($input['shop_type'] ?? 'restaurant') === 'retail' ? 'retail' : 'restaurant';
        $planType = in_array($input['plan_type'] ?? 'free', $allowedPlans, true) ? $input['plan_type'] : 'free';
        $phone = trim($input['phone'] ?? '');
        $address = trim($input['address'] ?? '');
        $ownerUsername = trim($input['owner_username'] ?? '');
        $ownerName = trim($input['owner_name'] ?? $shopName);
        $ownerPassword = (string)($input['owner_password'] ?? '');

        if ($shopName === '' || !preg_match('/^[a-zA-Z0-9_.-]{3,50}$/', $ownerUsername) || strlen($ownerPassword) < 6) {
            jsonResponse(['success' => false, 'error' => 'กรุณากรอกชื่อร้าน ชื่อผู้ใช้เจ้าของร้าน (3-50 ตัวอักษร) และรหัสผ่านอย่างน้อย 6 ตัวอักษร'], 400);
        }

        $escUsername = escape($ownerUsername);
        if (fetchOne(query("SELECT id FROM users WHERE username = '$escUsername' LIMIT 1"))) {
            jsonResponse(['success' => false, 'error' => 'ชื่อผู้ใช้เจ้าของร้านนี้ถูกใช้งานแล้ว'], 409);
        }

        $slug = createUniqueShopSlug(trim($input['shop_slug'] ?? '') ?: $shopName);
        $preset = generateShopSeoPreset([
            'shop_name' => $shopName,
            'shop_type' => $shopType,
            'address' => $address,
        ]);

        $escShopName = escape($shopName);
        $escSlug = escape($slug);
        $escType = escape($shopType);
        $escPlan = escape($planType);
        $escPhone = escape($phone);
        $escAddress = escape($address);
        $escSeoTitle = escape($preset['seo_title']);
        $escSeoDesc = escape($preset['seo_description']);
        $escSeoKeywords = escape($preset['seo_keywords']);

        $okShop = query("INSERT INTO shops (shop_name, shop_slug, shop_type, description, phone, address, seo_title, seo_description, seo_keywords, is_active, plan_type)
                         VALUES ('$escShopName', '$escSlug', '$escType', '$escSeoDesc', '$escPhone', '$escAddress', '$escSeoTitle', '$escSeoDesc', '$escSeoKeywords', 1, '$escPlan')");
        if (!$okShop) {
            jsonResponse(['success' => false, 'error' => 'ไม่สามารถสร้างร้านค้าได้'], 500);
        }

        global $conn;
        $newShopId = intval($conn->insert_id);
        $escFullName = escape($ownerName ?: $shopName);
        $hash = escape(password_hash($ownerPassword, PASSWORD_DEFAULT));
        query("INSERT INTO users (shop_id, username, password, full_name, role, is_active)
               VALUES ($newShopId, '$escUsername', '$hash', '$escFullName', 'admin', 1)");
        seedNewShopDefaults($newShopId, $shopType);

        jsonResponse([
            'success' => true,
            'message' => 'สร้างร้านค้าและบัญชีเจ้าของร้านเรียบร้อยแล้ว',
            'id' => $newShopId,
        ]);
    }

    jsonResponse(['success' => false, 'error' => 'Action not found'], 400);
}

if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = intval($input['id'] ?? 0);
    $shop = getShopById($id);
    if (!$shop) {
        jsonResponse(['success' => false, 'error' => 'ไม่พบร้านค้า'], 404);
    }

    $updates = [];
    if (isset($input['shop_name'])) {
        $name = trim($input['shop_name']);
        if ($name === '') {
            jsonResponse(['success' => false, 'error' => 'ชื่อร้านห้ามว่าง'], 400);
        }
        $updates[] = "shop_name = '" . escape($name) . "'";
    }
    if (isset($input['shop_type']) && in_array($input['shop_type'], $allowedShopTypes, true)) {
        $updates[] = "shop_type = '" . escape($input['shop_type']) . "'";
    }
    if (isset($input['plan_type']) && in_array($input['plan_type'], $allowedPlans, true)) {
        $updates[] = "plan_type = '" . escape($input['plan_type']) . "'";
    }
    if (isset($input['is_active'])) {
        $active = intval($input['is_active']) ? 1 : 0;
        if ($id === 1 && $active === 0) {
            jsonResponse(['success' => false, 'error' => 'ไม่สามารถระงับร้านค้าหลัก (ID 1) ได้'], 400);
        }
        $updates[] = "is_active = $active";
    }

    if (empty($updates)) {
        jsonResponse(['success' => false, 'error' => 'ไม่มีข้อมูลที่ต้องการอัปเดต'], 400);
    }

    if (!query("UPDATE shops SET " . implode(', ', $updates) . " WHERE id = $id")) {
        jsonResponse(['success' => false, 'error' => 'ไม่สามารถอัปเดตร้านค้าได้'], 500);
    }

    if (intval($_SESSION['shop_id'] ?? 0) === $id) {
        $refreshed = getShopById($id);
        if ($refreshed) {
            $_SESSION['shop_name'] = $refreshed['shop_name'];
            $_SESSION['shop_type'] = $refreshed['shop_type'] ?? 'restaurant';
        }
    }

    jsonResponse(['success' => true, 'message' => 'อัปเดตข้อมูลร้านค้าเรียบร้อยแล้ว']);
}

if ($method === 'DELETE') {
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 1) {
        jsonResponse(['success' => false, 'error' => 'ไม่อนุญาตให้ลบร้านค้าหลักของระบบ (ID 1)'], 400);
    }
    if (intval($_SESSION['shop_id'] ?? 0) === $id) {
        jsonResponse(['success' => false, 'error' => 'กรุณาสลับออกจากร้านนี้ก่อนทำการลบร้านค้า'], 400);
    }
    if (!query("DELETE FROM shops WHERE id = $id")) {
        jsonResponse(['success' => false, 'error' => 'ไม่สามารถลบร้านค้าได้'], 500);
    }
    jsonResponse(['success' => true, 'message' => 'ลบร้านค้าและข้อมูลที่เกี่ยวข้องเรียบร้อยแล้ว']);
}

jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
