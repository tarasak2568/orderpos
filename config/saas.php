<?php
/**
 * ระบบจัดการ Multi-Tenant SaaS (แยกข้อมูลตามร้านค้า shop_id, รองรับทั้งร้านอาหารและร้านของชำ/ค้าปลีก)
 * ไฟล์นี้ถูกแยกออกมาเพื่อให้สามารถ Track ผ่าน Git ได้โดยไม่กระทบไฟล์รหัสผ่าน config/db.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('ensureSaasSchema')) {
    function ensureSaasSchema() {
        global $conn;
        if (!$conn || !empty($_SESSION['_saas_members_schema_v3'])) {
            return;
        }

        // 1. สร้างตาราง shops (รองรับ shop_type: restaurant | retail)
        $conn->query("CREATE TABLE IF NOT EXISTS shops (
            id INT AUTO_INCREMENT PRIMARY KEY,
            shop_name VARCHAR(150) NOT NULL,
            shop_slug VARCHAR(100) NOT NULL UNIQUE,
            shop_type ENUM('restaurant', 'retail') NOT NULL DEFAULT 'restaurant',
            description TEXT DEFAULT NULL,
            logo_image VARCHAR(255) DEFAULT NULL,
            phone VARCHAR(30) DEFAULT NULL,
            address TEXT DEFAULT NULL,
            promptpay_number VARCHAR(30) DEFAULT NULL,
            currency VARCHAR(10) DEFAULT '฿',
            tax_rate DECIMAL(5,2) DEFAULT 0.00,
            service_charge DECIMAL(5,2) DEFAULT 0.00,
            seo_title VARCHAR(200) DEFAULT NULL,
            seo_description VARCHAR(300) DEFAULT NULL,
            seo_keywords VARCHAR(255) DEFAULT NULL,
            is_active TINYINT(1) DEFAULT 1,
            plan_type ENUM('free', 'pro', 'enterprise') DEFAULT 'free',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // ตรวจสอบว่ามีคอลัมน์ shop_type ใน shops หรือยัง
        $shopTypeCheck = $conn->query("SHOW COLUMNS FROM `shops` LIKE 'shop_type'");
        if ($shopTypeCheck && $shopTypeCheck->num_rows === 0) {
            $conn->query("ALTER TABLE `shops` ADD COLUMN `shop_type` ENUM('restaurant', 'retail') NOT NULL DEFAULT 'restaurant' AFTER `shop_slug`");
        }

        // 2. สร้างร้านเริ่มต้น (ID = 1)
        $conn->query("INSERT IGNORE INTO shops (id, shop_name, shop_slug, shop_type, description, phone, address, promptpay_number, seo_title, seo_description)
            VALUES (
                1,
                'ร้านอาหารของเรา',
                'main-shop',
                'restaurant',
                'ร้านอาหารอร่อย สั่งอาหารผ่านมือถือสะดวกรวดเร็วด้วยระบบ OrderPOS',
                '02-123-4567',
                '123 ถนนสุขใจ กรุงเทพฯ 10100',
                '0812345678',
                'ร้านอาหารของเรา - สั่งอาหารออนไลน์ & เมนูแนะนำ | OrderPOS',
                'สั่งอาหารออนไลน์ผ่าน QR Code จากโต๊ะอาหาร เมนูหลากหลาย รวดเร็ว ทันสมัย'
            )");

        // 3. เพิ่ม shop_id ในตารางหลัก
        $tablesToUpgrade = ['users', 'categories', 'menu_items', 'tables', 'orders', 'payments', 'settings'];
        foreach ($tablesToUpgrade as $tbl) {
            $colCheck = $conn->query("SHOW COLUMNS FROM `$tbl` LIKE 'shop_id'");
            if ($colCheck && $colCheck->num_rows === 0) {
                $conn->query("ALTER TABLE `$tbl` ADD COLUMN `shop_id` INT NOT NULL DEFAULT 1 AFTER `id`");
            }
        }

        // 4. เพิ่มคอลัมน์ sku (บาร์โค้ด/รหัสสินค้า) และ stock_qty (จำนวนสต๊อก) ใน menu_items สำหรับร้านขายของชำ/ค้าปลีก
        $skuCheck = $conn->query("SHOW COLUMNS FROM `menu_items` LIKE 'sku'");
        if ($skuCheck && $skuCheck->num_rows === 0) {
            $conn->query("ALTER TABLE `menu_items` ADD COLUMN `sku` VARCHAR(60) DEFAULT NULL AFTER `category_id`");
        }
        $stockCheck = $conn->query("SHOW COLUMNS FROM `menu_items` LIKE 'stock_qty'");
        if ($stockCheck && $stockCheck->num_rows === 0) {
            $conn->query("ALTER TABLE `menu_items` ADD COLUMN `stock_qty` INT DEFAULT NULL AFTER `price`");
        }

        // 5. ปรับ role ใน users และเพิ่มสถานะเปิด-ปิดบัญชีพนักงาน
        $conn->query("ALTER TABLE `users` MODIFY COLUMN `role` ENUM('superadmin', 'admin', 'staff', 'kitchen') NOT NULL DEFAULT 'admin'");
        $userActiveCheck = $conn->query("SHOW COLUMNS FROM `users` LIKE 'is_active'");
        if ($userActiveCheck && $userActiveCheck->num_rows === 0) {
            $conn->query("ALTER TABLE `users` ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `role`");
        }

        // 5.1 เพิ่มตั้งค่าสะสมแต้มสมาชิกใน shops
        $pointRateCheck = $conn->query("SHOW COLUMNS FROM `shops` LIKE 'member_point_rate'");
        if ($pointRateCheck && $pointRateCheck->num_rows === 0) {
            $conn->query("ALTER TABLE `shops` ADD COLUMN `member_point_rate` INT NOT NULL DEFAULT 25 AFTER `service_charge`");
        }
        $redeemValCheck = $conn->query("SHOW COLUMNS FROM `shops` LIKE 'member_redeem_value'");
        if ($redeemValCheck && $redeemValCheck->num_rows === 0) {
            $conn->query("ALTER TABLE `shops` ADD COLUMN `member_redeem_value` DECIMAL(6,2) NOT NULL DEFAULT 1.00 AFTER `member_point_rate`");
        }

        // 5.2 สร้างตารางสมาชิกลูกค้า (Customer CRM & Loyalty Points)
        $conn->query("CREATE TABLE IF NOT EXISTS members (
            id INT AUTO_INCREMENT PRIMARY KEY,
            shop_id INT NOT NULL DEFAULT 1,
            member_code VARCHAR(30) DEFAULT NULL,
            full_name VARCHAR(120) NOT NULL,
            phone VARCHAR(30) NOT NULL,
            email VARCHAR(150) DEFAULT NULL,
            line_id VARCHAR(80) DEFAULT NULL,
            tier ENUM('bronze', 'silver', 'gold', 'vip') NOT NULL DEFAULT 'bronze',
            points INT NOT NULL DEFAULT 0,
            total_spent DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            visit_count INT NOT NULL DEFAULT 0,
            discount_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            note TEXT DEFAULT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_shop_member_phone (shop_id, phone),
            INDEX idx_shop_member_name (shop_id, full_name),
            INDEX idx_shop_member_code (shop_id, member_code),
            FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // 5.3 สร้างตารางประวัติการเปลี่ยนแปลงแต้มสมาชิก
        $conn->query("CREATE TABLE IF NOT EXISTS member_point_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            shop_id INT NOT NULL DEFAULT 1,
            member_id INT NOT NULL,
            order_id INT DEFAULT NULL,
            change_points INT NOT NULL,
            reason VARCHAR(255) NOT NULL,
            created_by INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_member_log_member (shop_id, member_id),
            FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
            FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // 5.4 เพิ่มคอลัมน์ผูกสมาชิกและแต้มในตาราง orders
        $orderLoyaltyCols = [
            'member_id' => "ALTER TABLE `orders` ADD COLUMN `member_id` INT DEFAULT NULL AFTER `table_id`",
            'discount_amount' => "ALTER TABLE `orders` ADD COLUMN `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `note`",
            'points_earned' => "ALTER TABLE `orders` ADD COLUMN `points_earned` INT NOT NULL DEFAULT 0 AFTER `total_amount`",
            'points_redeemed' => "ALTER TABLE `orders` ADD COLUMN `points_redeemed` INT NOT NULL DEFAULT 0 AFTER `points_earned`",
        ];
        foreach ($orderLoyaltyCols as $colName => $alterSql) {
            $colChk = $conn->query("SHOW COLUMNS FROM `orders` LIKE '$colName'");
            if ($colChk && $colChk->num_rows === 0) {
                $conn->query($alterSql);
            }
        }

        // 6. ปรับ Unique Index ของ tables ให้แยกตามร้าน (shop_id, table_code)
        $idxCheck = $conn->query("SHOW INDEX FROM `tables` WHERE Key_name = 'table_code'");
        if ($idxCheck && $idxCheck->num_rows > 0) {
            $conn->query("ALTER TABLE `tables` DROP INDEX `table_code`");
        }
        $uniqCheck = $conn->query("SHOW INDEX FROM `tables` WHERE Key_name = 'uniq_shop_table'");
        if ($uniqCheck && $uniqCheck->num_rows === 0) {
            $conn->query("ALTER TABLE `tables` ADD UNIQUE INDEX `uniq_shop_table` (`shop_id`, `table_code`)");
        }

        $_SESSION['_saas_members_schema_v3'] = true;
    }
}

ensureSaasSchema();

if (!function_exists('getShopById')) {
    function getShopById($shopId) {
        $id = intval($shopId);
        if ($id <= 0) return null;
        return fetchOne(query("SELECT * FROM shops WHERE id = $id LIMIT 1"));
    }
}

if (!function_exists('getShopBySlug')) {
    function getShopBySlug($slugOrId) {
        $clean = escape(trim($slugOrId));
        if ($clean === '') return null;
        if (ctype_digit($clean)) {
            $id = intval($clean);
            $shop = fetchOne(query("SELECT * FROM shops WHERE (id = $id OR shop_slug = '$clean') AND is_active = 1 LIMIT 1"));
            if ($shop) return $shop;
        }
        return fetchOne(query("SELECT * FROM shops WHERE shop_slug = '$clean' AND is_active = 1 LIMIT 1"));
    }
}

if (!function_exists('currentShopId')) {
    function currentShopId() {
        if (!empty($_GET['shop_id']) && intval($_GET['shop_id']) > 0) {
            return intval($_GET['shop_id']);
        }
        $slugParam = $_GET['shop'] ?? ($_GET['shop_slug'] ?? '');
        if (!empty($slugParam)) {
            $shop = getShopBySlug($slugParam);
            if ($shop) {
                return intval($shop['id']);
            }
        }
        if (!empty($_SESSION['shop_id']) && intval($_SESSION['shop_id']) > 0) {
            return intval($_SESSION['shop_id']);
        }
        return 1;
    }
}

if (!function_exists('getCurrentShop')) {
    function getCurrentShop($shopId = null) {
        $id = $shopId !== null ? intval($shopId) : currentShopId();
        $shop = getShopById($id);
        if (!$shop) {
            $shop = getShopById(1);
        }
        if (!$shop) {
            $shop = [
                'id' => 1,
                'shop_name' => 'OrderPOS ร้านค้าออนไลน์',
                'shop_slug' => 'main-shop',
                'shop_type' => 'restaurant',
                'description' => 'ระบบขายหน้าร้านและจัดการร้านค้าออนไลน์',
                'phone' => '',
                'address' => '',
                'promptpay_number' => '',
                'currency' => '฿',
                'seo_title' => '',
                'seo_description' => '',
                'seo_keywords' => ''
            ];
        }
        if (empty($shop['shop_type'])) {
            $shop['shop_type'] = 'restaurant';
        }
        return $shop;
    }
}

if (!function_exists('createUniqueShopSlug')) {
    function createUniqueShopSlug($rawInput, $excludeShopId = 0) {
        $slug = strtolower(trim($rawInput));
        $slug = preg_replace('/[^a-z0-9ก-๙\-]+/u', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');

        if ($slug === '' || strlen($slug) < 2) {
            $slug = 'shop-' . strtolower(substr(md5(uniqid('', true)), 0, 6));
        }

        $baseSlug = $slug;
        $counter = 1;
        $excludeId = intval($excludeShopId);

        while (true) {
            $escaped = escape($slug);
            $whereExclude = $excludeId > 0 ? " AND id != $excludeId" : "";
            $existing = fetchOne(query("SELECT id FROM shops WHERE shop_slug = '$escaped' $whereExclude LIMIT 1"));
            if (!$existing) {
                return $slug;
            }
            $counter++;
            $slug = $baseSlug . '-' . $counter;
        }
    }
}

if (!function_exists('seedNewShopDefaults')) {
    function seedNewShopDefaults($shopId, $shopType = 'restaurant') {
        global $conn;
        $sid = intval($shopId);
        if ($sid <= 0) return;

        if ($shopType === 'retail') {
            // ข้อมูลเริ่มต้นสำหรับร้านขายของชำ / มินิมาร์ท / ร้านค้าปลีก
            $defaultCategories = [
                ['สินค้าขายดี', 'fa-fire', 1],
                ['เครื่องดื่ม / นม', 'fa-bottle-water', 2],
                ['ขนมขบเคี้ยว', 'fa-cookie-bite', 3],
                ['อาหารแห้ง / บะหมี่', 'fa-bowl-rice', 4],
                ['ของใช้ประจำวัน', 'fa-basket-shopping', 5]
            ];

            $catIds = [];
            foreach ($defaultCategories as $cat) {
                $name = escape($cat[0]);
                $icon = escape($cat[1]);
                $sort = intval($cat[2]);
                query("INSERT INTO categories (shop_id, name, icon, sort_order, is_active) VALUES ($sid, '$name', '$icon', $sort, 1)");
                $catIds[] = $conn->insert_id;
            }

            if (!empty($catIds)) {
                $c1 = $catIds[0];
                $c2 = $catIds[1] ?? $catIds[0];
                $c3 = $catIds[2] ?? $catIds[0];
                $c4 = $catIds[3] ?? $catIds[0];
                $c5 = $catIds[4] ?? $catIds[0];

                $sampleProducts = [
                    [$c2, '8850999320001', 'น้ำดื่มสะอาด 600 มล.', 'น้ำดื่มบรรจุขวดเย็นชื่นใจ', 10, 50, 1],
                    [$c2, '8851959132012', 'โค้ก กระป๋อง 325 มล.', 'เครื่องดื่มน้ำอัดลมเย็นซ่า', 18, 36, 1],
                    [$c4, '8850987101018', 'บะหมี่กึ่งสำเร็จรูป รสต้มยำกุ้ง', 'ซองมาตรฐาน 60 กรัม', 7, 100, 1],
                    [$c3, '8850718801015', 'มันฝรั่งทอดกรอบ รสออริจินัล', 'ซองขนาดกลาง กรอบอร่อย', 20, 25, 0],
                    [$c5, '8850006321024', 'สบู่ก้อนอาบน้ำ แพ็คเดี่ยว', 'สบู่ทำความสะอาดผิวกายหอมสดชื่น', 15, 30, 0],
                    [$c1, '8850123456789', 'ไข่ไก่สด เบอร์ 2 (แผง 10 ฟอง)', 'ไข่ไก่สดใหม่จากฟาร์มทุกวัน', 48, 20, 1]
                ];

                foreach ($sampleProducts as $p) {
                    $cid = intval($p[0]);
                    $sku = escape($p[1]);
                    $pName = escape($p[2]);
                    $pDesc = escape($p[3]);
                    $pPrice = floatval($p[4]);
                    $pStock = intval($p[5]);
                    $pRec = intval($p[6]);
                    query("INSERT INTO menu_items (shop_id, category_id, sku, name, description, price, stock_qty, is_available, is_recommended)
                           VALUES ($sid, $cid, '$sku', '$pName', '$pDesc', $pPrice, $pStock, 1, $pRec)");
                }
            }

            // สร้างจุดขายหน้าร้าน (Walk-in Counter) สำหรับร้านค้าปลีก
            query("INSERT IGNORE INTO tables (shop_id, table_code, table_name, capacity, status)
                   VALUES ($sid, 'WALKIN', 'ขายหน้าร้าน (Walk-in)', 1, 'available')");
            query("INSERT IGNORE INTO tables (shop_id, table_code, table_name, capacity, status)
                   VALUES ($sid, 'ONLINE', 'สั่งซื้อออนไลน์ / จัดส่ง', 1, 'available')");
        } else {
            // ข้อมูลเริ่มต้นสำหรับร้านอาหาร / คาเฟ่
            $defaultCategories = [
                ['อาหารแนะนำ', 'fa-star', 1],
                ['อาหารจานเดียว', 'fa-utensils', 2],
                ['กับข้าว', 'fa-bowl-food', 3],
                ['เครื่องดื่ม', 'fa-mug-saucer', 4],
                ['ของหวาน', 'fa-cake-candles', 5]
            ];

            $catIds = [];
            foreach ($defaultCategories as $cat) {
                $name = escape($cat[0]);
                $icon = escape($cat[1]);
                $sort = intval($cat[2]);
                query("INSERT INTO categories (shop_id, name, icon, sort_order, is_active) VALUES ($sid, '$name', '$icon', $sort, 1)");
                $catIds[] = $conn->insert_id;
            }

            if (!empty($catIds)) {
                $c1 = $catIds[0];
                $c2 = $catIds[1] ?? $catIds[0];
                $c4 = $catIds[3] ?? $catIds[0];

                $sampleMenus = [
                    [$c1, 'ข้าวผัดกระเพราหมูกรอบ + ไข่ดาว', 'เมนูยอดฮิต รสจัดจ้านถึงเครื่องกระเพราแท้', 65, 1],
                    [$c1, 'ต้มยำกุ้งน้ำข้น', 'กุ้งสดตัวโต น้ำต้มยำเข้มข้นหอมสมุนไพร', 120, 1],
                    [$c2, 'ข้าวผัดหมู/ไก่', 'ข้าวผัดหอมกลิ่นกระทะ เม็ดข้าวร่วนสวย', 55, 0],
                    [$c4, 'น้ำดื่มสะอาด', 'น้ำดื่มเย็นชื่นใจ 600 มล.', 15, 0],
                    [$c4, 'ชาไทยเย็น', 'ชาไทยหอมเข้มข้น หวานมันกำลังดี', 40, 1]
                ];

                foreach ($sampleMenus as $m) {
                    $cid = intval($m[0]);
                    $mName = escape($m[1]);
                    $mDesc = escape($m[2]);
                    $mPrice = floatval($m[3]);
                    $mRec = intval($m[4]);
                    query("INSERT INTO menu_items (shop_id, category_id, name, description, price, is_available, is_recommended)
                           VALUES ($sid, $cid, '$mName', '$mDesc', $mPrice, 1, $mRec)");
                }
            }

            for ($i = 1; $i <= 5; $i++) {
                $code = 'T' . str_pad($i, 2, '0', STR_PAD_LEFT);
                $tName = 'โต๊ะ ' . $i;
                $cap = ($i <= 2) ? 2 : 4;
                query("INSERT IGNORE INTO tables (shop_id, table_code, table_name, capacity, status)
                       VALUES ($sid, '$code', '$tName', $cap, 'available')");
            }
        }
    }
}

if (!function_exists('currentUserRole')) {
    function currentUserRole(): string {
        return (string)($_SESSION['role'] ?? 'staff');
    }
}

if (!function_exists('hasAnyRole')) {
    function hasAnyRole(array $allowedRoles): bool {
        if (empty($_SESSION['user_id'])) {
            return false;
        }
        return in_array(currentUserRole(), $allowedRoles, true);
    }
}

if (!function_exists('requireAuthApi')) {
    function requireAuthApi(array $allowedRoles = []): void {
        if (empty($_SESSION['user_id'])) {
            jsonResponse(['success' => false, 'error' => 'กรุณาเข้าสู่ระบบก่อนใช้งาน'], 401);
        }
        if (!empty($allowedRoles) && !in_array(currentUserRole(), $allowedRoles, true)) {
            jsonResponse(['success' => false, 'error' => 'บัญชีของคุณไม่มีสิทธิ์ดำเนินการนี้'], 403);
        }
    }
}

if (!function_exists('requireShopRoles')) {
    function requireShopRoles(array $allowedRoles, string $fallbackUrl = 'dashboard.php'): void {
        if (empty($_SESSION['user_id'])) {
            header('Location: ../login.php');
            exit;
        }
        $role = currentUserRole();
        if (!in_array($role, $allowedRoles, true)) {
            $target = ($role === 'kitchen') ? '../kitchen.php' : $fallbackUrl;
            header('Location: ' . $target);
            exit;
        }
    }
}

if (!function_exists('getRoleDisplayInfo')) {
    function getRoleDisplayInfo(string $role): array {
        $map = [
            'superadmin' => ['label' => 'Super Admin (เจ้าของระบบ)', 'bg' => '#EDE9FE', 'color' => '#6D28D9', 'desc' => 'จัดการร้านค้าทั้งหมดบนแพลตฟอร์มและเข้าช่วยจัดการทุกร้านได้'],
            'admin'      => ['label' => 'เจ้าของร้าน / แอดมิน', 'bg' => '#FFF3ED', 'color' => '#C2410C', 'desc' => 'เข้าถึงทุกเมนูในร้าน จัดการพนักงาน สมาชิก เมนู โต๊ะ และ SEO'],
            'staff'      => ['label' => 'พนักงานหน้าร้าน / แคชเชียร์', 'bg' => '#E0F2FE', 'color' => '#0369A1', 'desc' => 'เปิด POS รับออเดอร์ เก็บเงิน ดูออเดอร์ และค้นหา/สมัครสมาชิกลูกค้า'],
            'kitchen'    => ['label' => 'พนักงานครัว (KDS)', 'bg' => '#ECFDF5', 'color' => '#047857', 'desc' => 'เข้าดูหน้าจอครัว (Kitchen Display) และอัปเดตสถานะปรุงอาหาร'],
        ];
        return $map[$role] ?? ['label' => $role, 'bg' => '#F1F5F9', 'color' => '#475569', 'desc' => ''];
    }
}

if (!function_exists('getMemberTierCatalog')) {
    function getMemberTierCatalog(): array {
        return [
            'bronze' => ['label' => 'Bronze', 'bg' => '#FEF3C7', 'color' => '#92400E', 'default_discount' => 0.0],
            'silver' => ['label' => 'Silver', 'bg' => '#F1F5F9', 'color' => '#334155', 'default_discount' => 3.0],
            'gold'   => ['label' => 'Gold',   'bg' => '#FEF08A', 'color' => '#854D0E', 'default_discount' => 5.0],
            'vip'    => ['label' => 'VIP',    'bg' => '#FCE7F3', 'color' => '#9D174D', 'default_discount' => 10.0],
        ];
    }
}

if (!function_exists('calculateOrderLoyaltyTotals')) {
    function calculateOrderLoyaltyTotals(float $grossAmount, ?array $member, array $shop, int $requestedPoints = 0): array {
        $gross = max(0.0, round($grossAmount, 2));
        $pointRate = max(0, intval($shop['member_point_rate'] ?? 25));
        $redeemValue = max(0.0, floatval($shop['member_redeem_value'] ?? 1.0));

        if (!$member || intval($member['is_active'] ?? 1) !== 1) {
            return [
                'gross_amount' => $gross,
                'tier_discount' => 0.0,
                'points_redeemed' => 0,
                'point_discount' => 0.0,
                'discount_amount' => 0.0,
                'net_amount' => $gross,
                'points_earned' => 0,
            ];
        }

        $discountPct = min(100.0, max(0.0, floatval($member['discount_percent'] ?? 0)));
        $tierDiscount = round($gross * ($discountPct / 100.0), 2);
        $afterTier = max(0.0, round($gross - $tierDiscount, 2));

        $availablePoints = max(0, intval($member['points'] ?? 0));
        $wantedPoints = max(0, $requestedPoints);
        $usedPoints = 0;
        $pointDiscount = 0.0;

        if ($redeemValue > 0 && $availablePoints > 0 && $wantedPoints > 0 && $afterTier > 0) {
            $maxRedeemableByTotal = intval(floor($afterTier / $redeemValue));
            $usedPoints = min($wantedPoints, $availablePoints, $maxRedeemableByTotal);
            $pointDiscount = round($usedPoints * $redeemValue, 2);
        }

        $totalDiscount = min($gross, round($tierDiscount + $pointDiscount, 2));
        $netAmount = max(0.0, round($gross - $totalDiscount, 2));
        $pointsEarned = ($pointRate > 0) ? intval(floor($netAmount / $pointRate)) : 0;

        return [
            'gross_amount' => $gross,
            'tier_discount' => $tierDiscount,
            'points_redeemed' => $usedPoints,
            'point_discount' => $pointDiscount,
            'discount_amount' => $totalDiscount,
            'net_amount' => $netAmount,
            'points_earned' => $pointsEarned,
        ];
    }
}

if (!function_exists('applyMemberLoyaltyOnOrderPaid')) {
    function applyMemberLoyaltyOnOrderPaid(int $shopId, int $orderId, int $memberId, float $grossAmount, int $requestedPoints = 0, ?int $staffUserId = null): array {
        $shop = getCurrentShop($shopId);
        $member = null;
        if ($memberId > 0) {
            $member = fetchOne(query("SELECT * FROM members WHERE id = $memberId AND shop_id = $shopId AND is_active = 1 LIMIT 1"));
        }

        $calc = calculateOrderLoyaltyTotals($grossAmount, $member, $shop, $requestedPoints);
        if (!$member) {
            return array_merge($calc, ['member_id' => null, 'member' => null]);
        }

        $mid = intval($member['id']);
        $net = floatval($calc['net_amount']);
        $discountAmt = floatval($calc['discount_amount']);
        $earned = intval($calc['points_earned']);
        $redeemed = intval($calc['points_redeemed']);
        $netPointDelta = $earned - $redeemed;
        $escCustName = escape($member['full_name']);
        $escCustPhone = escape($member['phone']);

        query("UPDATE orders
               SET member_id = $mid,
                   customer_name = '$escCustName',
                   customer_phone = '$escCustPhone',
                   discount_amount = $discountAmt,
                   total_amount = $net,
                   points_earned = $earned,
                   points_redeemed = $redeemed
               WHERE id = $orderId AND shop_id = $shopId");

        query("UPDATE members
               SET points = GREATEST(0, points + ($netPointDelta)),
                   total_spent = total_spent + $net,
                   visit_count = visit_count + 1
               WHERE id = $mid AND shop_id = $shopId");

        $bySql = $staffUserId ? intval($staffUserId) : 'NULL';
        if ($redeemed > 0) {
            $reasonRedeem = escape("แลกแต้มส่วนลดบิล #$orderId (-{$calc['point_discount']} บาท)");
            query("INSERT INTO member_point_logs (shop_id, member_id, order_id, change_points, reason, created_by)
                   VALUES ($shopId, $mid, $orderId, -$redeemed, '$reasonRedeem', $bySql)");
        }
        if ($earned > 0) {
            $reasonEarn = escape("รับแต้มสะสมจากบิล #$orderId (ยอดสุทธิ ฿" . number_format($net, 2, '.', '') . ")");
            query("INSERT INTO member_point_logs (shop_id, member_id, order_id, change_points, reason, created_by)
                   VALUES ($shopId, $mid, $orderId, $earned, '$reasonEarn', $bySql)");
        }

        return array_merge($calc, ['member_id' => $mid, 'member' => $member]);
    }
}
?>
