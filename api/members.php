<?php
require_once '../config/db.php';
require_once '../config/saas.php';
header('Content-Type: application/json; charset=utf-8');

requireAuthApi(['superadmin', 'admin', 'staff']);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$shopId = intval($_SESSION['shop_id'] ?? 1);
$staffUserId = intval($_SESSION['user_id'] ?? 0) ?: null;
$tierCatalog = getMemberTierCatalog();
$allowedTiers = array_keys($tierCatalog);

function normalizeMemberPhone(string $raw): string
{
    $clean = preg_replace('/[\s\-().]+/', '', trim($raw));
    return $clean ?? trim($raw);
}

function formatMemberRow(array $row, array $tierCatalog): array
{
    $row['id'] = intval($row['id']);
    $row['shop_id'] = intval($row['shop_id']);
    $row['points'] = intval($row['points']);
    $row['total_spent'] = floatval($row['total_spent']);
    $row['visit_count'] = intval($row['visit_count']);
    $row['discount_percent'] = floatval($row['discount_percent']);
    $row['is_active'] = intval($row['is_active'] ?? 1);
    $tierKey = $row['tier'] ?? 'bronze';
    $row['tier_info'] = $tierCatalog[$tierKey] ?? $tierCatalog['bronze'];
    return $row;
}

if ($method === 'GET') {
    $shop = getCurrentShop($shopId);
    $loyaltyConfig = [
        'member_point_rate' => max(0, intval($shop['member_point_rate'] ?? 25)),
        'member_redeem_value' => max(0.0, floatval($shop['member_redeem_value'] ?? 1.0)),
    ];

    if ($action === 'lookup') {
        $q = trim($_GET['q'] ?? '');
        if ($q === '') {
            jsonResponse(['success' => true, 'data' => [], 'loyalty_config' => $loyaltyConfig]);
        }
        $normPhone = normalizeMemberPhone($q);
        $escQ = escape($q);
        $escPhone = escape($normPhone);
        $rows = fetchAll(query("
            SELECT * FROM members
            WHERE shop_id = $shopId AND is_active = 1
              AND (phone LIKE '%$escPhone%' OR full_name LIKE '%$escQ%' OR member_code LIKE '%$escQ%')
            ORDER BY CASE WHEN phone = '$escPhone' THEN 0 ELSE 1 END, visit_count DESC, id DESC
            LIMIT 10
        "));
        foreach ($rows as &$r) {
            $r = formatMemberRow($r, $tierCatalog);
        }
        unset($r);
        jsonResponse(['success' => true, 'data' => $rows, 'loyalty_config' => $loyaltyConfig]);
    }

    if ($action === 'history') {
        $memberId = intval($_GET['member_id'] ?? 0);
        if ($memberId <= 0) {
            jsonResponse(['success' => false, 'error' => 'ไม่พบรหัสสมาชิก'], 400);
        }
        $member = fetchOne(query("SELECT * FROM members WHERE id = $memberId AND shop_id = $shopId LIMIT 1"));
        if (!$member) {
            jsonResponse(['success' => false, 'error' => 'ไม่พบข้อมูลสมาชิก'], 404);
        }

        $logs = fetchAll(query("
            SELECT l.*, u.full_name AS created_by_name
            FROM member_point_logs l
            LEFT JOIN users u ON l.created_by = u.id
            WHERE l.shop_id = $shopId AND l.member_id = $memberId
            ORDER BY l.created_at DESC, l.id DESC
            LIMIT 30
        "));

        $orders = fetchAll(query("
            SELECT id, order_no, total_amount, discount_amount, points_earned, points_redeemed, payment_method, payment_status, status, created_at
            FROM orders
            WHERE shop_id = $shopId AND member_id = $memberId
            ORDER BY created_at DESC
            LIMIT 20
        "));

        jsonResponse([
            'success' => true,
            'data' => [
                'member' => formatMemberRow($member, $tierCatalog),
                'point_logs' => $logs,
                'orders' => $orders,
            ],
        ]);
    }

    // Default GET list + KPI stats
    $q = trim($_GET['q'] ?? '');
    $tier = trim($_GET['tier'] ?? '');
    $where = "WHERE shop_id = $shopId";
    if ($q !== '') {
        $escQ = escape($q);
        $escPhone = escape(normalizeMemberPhone($q));
        $where .= " AND (full_name LIKE '%$escQ%' OR phone LIKE '%$escPhone%' OR member_code LIKE '%$escQ%' OR line_id LIKE '%$escQ%')";
    }
    if ($tier !== '' && in_array($tier, $allowedTiers, true)) {
        $escTier = escape($tier);
        $where .= " AND tier = '$escTier'";
    }

    $members = fetchAll(query("SELECT * FROM members $where ORDER BY id DESC LIMIT 200"));
    foreach ($members as &$m) {
        $m = formatMemberRow($m, $tierCatalog);
    }
    unset($m);

    $statsRow = fetchOne(query("
        SELECT
            COUNT(*) AS total_members,
            COALESCE(SUM(CASE WHEN is_active = 1 THEN points ELSE 0 END), 0) AS total_points,
            COALESCE(SUM(total_spent), 0) AS total_spent,
            COALESCE(SUM(CASE WHEN tier IN ('gold', 'vip') THEN 1 ELSE 0 END), 0) AS premium_members
        FROM members
        WHERE shop_id = $shopId
    "));

    jsonResponse([
        'success' => true,
        'data' => $members,
        'stats' => [
            'total_members' => intval($statsRow['total_members'] ?? 0),
            'total_points' => intval($statsRow['total_points'] ?? 0),
            'total_spent' => floatval($statsRow['total_spent'] ?? 0),
            'premium_members' => intval($statsRow['premium_members'] ?? 0),
        ],
        'loyalty_config' => $loyaltyConfig,
        'tiers' => $tierCatalog,
    ]);
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];

    if ($action === 'loyalty_settings') {
        requireAuthApi(['superadmin', 'admin']);
        $pointRate = max(0, intval($input['member_point_rate'] ?? 25));
        $redeemValue = max(0.0, floatval($input['member_redeem_value'] ?? 1.0));
        $ok = query("UPDATE shops SET member_point_rate = $pointRate, member_redeem_value = $redeemValue WHERE id = $shopId");
        if (!$ok) {
            jsonResponse(['success' => false, 'error' => 'ไม่สามารถบันทึกกติกาสะสมแต้มได้'], 500);
        }
        jsonResponse(['success' => true, 'message' => 'บันทึกกติกาสะสมแต้มสมาชิกเรียบร้อยแล้ว']);
    }

    if ($action === 'adjust_points') {
        requireAuthApi(['superadmin', 'admin']);
        $memberId = intval($input['member_id'] ?? 0);
        $delta = intval($input['change_points'] ?? 0);
        $reason = trim($input['reason'] ?? '');

        if ($memberId <= 0 || $delta === 0) {
            jsonResponse(['success' => false, 'error' => 'กรุณาระบุสมาชิกและจำนวนแต้มที่ต้องการปรับ (ไม่เป็น 0)'], 400);
        }
        if ($reason === '') {
            $reason = $delta > 0 ? 'แอดมินเพิ่มแต้มพิเศษ' : 'แอดมินหักแต้มด้วยตนเอง';
        }

        $member = fetchOne(query("SELECT id, points FROM members WHERE id = $memberId AND shop_id = $shopId LIMIT 1"));
        if (!$member) {
            jsonResponse(['success' => false, 'error' => 'ไม่พบสมาชิกในร้านของคุณ'], 404);
        }
        if (intval($member['points']) + $delta < 0) {
            jsonResponse(['success' => false, 'error' => 'แต้มสะสมคงเหลือไม่เพียงพอสำหรับหักแต้มจำนวนนี้'], 400);
        }

        query("UPDATE members SET points = points + ($delta) WHERE id = $memberId AND shop_id = $shopId");
        $escReason = escape($reason);
        $bySql = $staffUserId ? $staffUserId : 'NULL';
        query("INSERT INTO member_point_logs (shop_id, member_id, order_id, change_points, reason, created_by)
               VALUES ($shopId, $memberId, NULL, $delta, '$escReason', $bySql)");

        $updated = fetchOne(query("SELECT * FROM members WHERE id = $memberId AND shop_id = $shopId LIMIT 1"));
        jsonResponse([
            'success' => true,
            'message' => 'ปรับยอดแต้มสมาชิกเรียบร้อยแล้ว',
            'data' => formatMemberRow($updated, $tierCatalog),
        ]);
    }

    // Create new customer member
    $fullName = trim($input['full_name'] ?? '');
    $phone = normalizeMemberPhone((string)($input['phone'] ?? ''));
    $memberCode = trim($input['member_code'] ?? '');
    $email = trim($input['email'] ?? '');
    $lineId = trim($input['line_id'] ?? '');
    $tier = trim($input['tier'] ?? 'bronze');
    $note = trim($input['note'] ?? '');

    if ($fullName === '' || strlen($phone) < 8) {
        jsonResponse(['success' => false, 'error' => 'กรุณากรอกชื่อสมาชิกและเบอร์โทรศัพท์อย่างน้อย 8 หลัก'], 400);
    }
    if (!in_array($tier, $allowedTiers, true)) {
        $tier = 'bronze';
    }

    $discountPercent = isset($input['discount_percent']) && $input['discount_percent'] !== ''
        ? min(100.0, max(0.0, floatval($input['discount_percent'])))
        : floatval($tierCatalog[$tier]['default_discount']);
    $initialPoints = max(0, intval($input['points'] ?? 0));

    $escPhone = escape($phone);
    $dup = fetchOne(query("SELECT id FROM members WHERE shop_id = $shopId AND phone = '$escPhone' LIMIT 1"));
    if ($dup) {
        jsonResponse(['success' => false, 'error' => 'เบอร์โทรศัพท์นี้ลงทะเบียนสมาชิกกับทางร้านแล้ว'], 409);
    }

    if ($memberCode === '') {
        $memberCode = 'M' . $shopId . '-' . date('ymd') . str_pad((string)mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
    }

    $escCode = escape($memberCode);
    $escName = escape($fullName);
    $escEmail = escape($email);
    $escLine = escape($lineId);
    $escTier = escape($tier);
    $escNote = escape($note);

    $ok = query("INSERT INTO members (shop_id, member_code, full_name, phone, email, line_id, tier, points, discount_percent, note, is_active)
                 VALUES ($shopId, '$escCode', '$escName', '$escPhone', '$escEmail', '$escLine', '$escTier', $initialPoints, $discountPercent, '$escNote', 1)");
    if (!$ok) {
        jsonResponse(['success' => false, 'error' => 'ไม่สามารถสมัครสมาชิกได้'], 500);
    }

    global $conn;
    $newId = intval($conn->insert_id);
    if ($initialPoints > 0) {
        $bySql = $staffUserId ? $staffUserId : 'NULL';
        query("INSERT INTO member_point_logs (shop_id, member_id, order_id, change_points, reason, created_by)
               VALUES ($shopId, $newId, NULL, $initialPoints, 'แต้มเริ่มต้นตอนสมัครสมาชิก', $bySql)");
    }

    $created = fetchOne(query("SELECT * FROM members WHERE id = $newId AND shop_id = $shopId LIMIT 1"));
    jsonResponse([
        'success' => true,
        'message' => 'สมัครสมาชิกลูกค้าเรียบร้อยแล้ว',
        'data' => formatMemberRow($created, $tierCatalog),
    ]);
}

if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = intval($input['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse(['success' => false, 'error' => 'ไม่พบรหัสสมาชิก'], 400);
    }

    $existing = fetchOne(query("SELECT * FROM members WHERE id = $id AND shop_id = $shopId LIMIT 1"));
    if (!$existing) {
        jsonResponse(['success' => false, 'error' => 'ไม่พบข้อมูลสมาชิกในร้านของคุณ'], 404);
    }

    $fullName = trim($input['full_name'] ?? $existing['full_name']);
    $phone = normalizeMemberPhone((string)($input['phone'] ?? $existing['phone']));
    $memberCode = trim($input['member_code'] ?? ($existing['member_code'] ?? ''));
    $email = trim($input['email'] ?? ($existing['email'] ?? ''));
    $lineId = trim($input['line_id'] ?? ($existing['line_id'] ?? ''));
    $tier = trim($input['tier'] ?? $existing['tier']);
    $note = trim($input['note'] ?? ($existing['note'] ?? ''));
    $isActive = isset($input['is_active']) ? (intval($input['is_active']) ? 1 : 0) : intval($existing['is_active']);

    if ($fullName === '' || strlen($phone) < 8) {
        jsonResponse(['success' => false, 'error' => 'กรุณากรอกชื่อสมาชิกและเบอร์โทรศัพท์อย่างน้อย 8 หลัก'], 400);
    }
    if (!in_array($tier, $allowedTiers, true)) {
        $tier = $existing['tier'];
    }
    $discountPercent = isset($input['discount_percent']) && $input['discount_percent'] !== ''
        ? min(100.0, max(0.0, floatval($input['discount_percent'])))
        : floatval($existing['discount_percent']);

    $escPhone = escape($phone);
    $dup = fetchOne(query("SELECT id FROM members WHERE shop_id = $shopId AND phone = '$escPhone' AND id != $id LIMIT 1"));
    if ($dup) {
        jsonResponse(['success' => false, 'error' => 'เบอร์โทรศัพท์นี้ซ้ำกับสมาชิกรายอื่นในร้าน'], 409);
    }

    $escCode = escape($memberCode);
    $escName = escape($fullName);
    $escEmail = escape($email);
    $escLine = escape($lineId);
    $escTier = escape($tier);
    $escNote = escape($note);

    $ok = query("UPDATE members SET
        member_code = '$escCode',
        full_name = '$escName',
        phone = '$escPhone',
        email = '$escEmail',
        line_id = '$escLine',
        tier = '$escTier',
        discount_percent = $discountPercent,
        note = '$escNote',
        is_active = $isActive
        WHERE id = $id AND shop_id = $shopId");

    if (!$ok) {
        jsonResponse(['success' => false, 'error' => 'ไม่สามารถอัปเดตข้อมูลสมาชิกได้'], 500);
    }

    jsonResponse(['success' => true, 'message' => 'อัปเดตข้อมูลสมาชิกเรียบร้อยแล้ว']);
}

if ($method === 'DELETE') {
    requireAuthApi(['superadmin', 'admin']);
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse(['success' => false, 'error' => 'ไม่พบรหัสสมาชิก'], 400);
    }
    $existing = fetchOne(query("SELECT id FROM members WHERE id = $id AND shop_id = $shopId LIMIT 1"));
    if (!$existing) {
        jsonResponse(['success' => false, 'error' => 'ไม่พบข้อมูลสมาชิก'], 404);
    }

    query("UPDATE orders SET member_id = NULL WHERE member_id = $id AND shop_id = $shopId");
    if (!query("DELETE FROM members WHERE id = $id AND shop_id = $shopId")) {
        jsonResponse(['success' => false, 'error' => 'ไม่สามารถลบข้อมูลสมาชิกได้'], 500);
    }
    jsonResponse(['success' => true, 'message' => 'ลบข้อมูลสมาชิกเรียบร้อยแล้ว']);
}

jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
