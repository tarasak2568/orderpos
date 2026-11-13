-- ============================================================
-- OrderPOS Multi-Tenant SaaS + Dynamic SEO Migration Script
-- ============================================================

-- 1. สร้างตารางร้านค้า (shops)
CREATE TABLE IF NOT EXISTS shops (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. สร้างร้านค้าเริ่มต้น (ID = 1) สำหรับข้อมูลเดิมในระบบ
INSERT IGNORE INTO shops (id, shop_name, shop_slug, description, phone, address, promptpay_number, seo_title, seo_description)
VALUES (
    1,
    'ร้านอาหารของเรา',
    'main-shop',
    'ร้านอาหารอร่อย สั่งอาหารผ่านมือถือสะดวกรวดเร็วด้วยระบบ OrderPOS',
    '02-123-4567',
    '123 ถนนสุขใจ กรุงเทพฯ 10100',
    '0812345678',
    'ร้านอาหารของเรา - สั่งอาหารออนไลน์ & เมนูแนะนำ | OrderPOS',
    'สั่งอาหารออนไลน์ผ่าน QR Code จากโต๊ะอาหาร เมนูหลากหลาย รวดเร็ว ทันสมัย'
);

-- 3. เพิ่มคอลัมน์ shop_id ให้ตารางหลักทั้งหมด (หากยังไม่มี)
ALTER TABLE users ADD COLUMN IF NOT EXISTS shop_id INT NOT NULL DEFAULT 1 AFTER id;
ALTER TABLE users MODIFY COLUMN role ENUM('superadmin', 'admin', 'staff', 'kitchen') NOT NULL DEFAULT 'admin';

ALTER TABLE shops ADD COLUMN IF NOT EXISTS shop_type ENUM('restaurant', 'retail') NOT NULL DEFAULT 'restaurant' AFTER shop_slug;
ALTER TABLE categories ADD COLUMN IF NOT EXISTS shop_id INT NOT NULL DEFAULT 1 AFTER id;
ALTER TABLE menu_items ADD COLUMN IF NOT EXISTS shop_id INT NOT NULL DEFAULT 1 AFTER id;
ALTER TABLE menu_items ADD COLUMN IF NOT EXISTS sku VARCHAR(60) DEFAULT NULL AFTER category_id;
ALTER TABLE menu_items ADD COLUMN IF NOT EXISTS stock_qty INT DEFAULT NULL AFTER price;
ALTER TABLE tables ADD COLUMN IF NOT EXISTS shop_id INT NOT NULL DEFAULT 1 AFTER id;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS shop_id INT NOT NULL DEFAULT 1 AFTER id;
ALTER TABLE payments ADD COLUMN IF NOT EXISTS shop_id INT NOT NULL DEFAULT 1 AFTER id;
ALTER TABLE settings ADD COLUMN IF NOT EXISTS shop_id INT NOT NULL DEFAULT 1 AFTER id;

-- 4. ปรับ Unique Index ของ tables ให้แยกตามร้านค้า (shop_id, table_code)
-- หมายเหตุ: เพื่อให้แต่ละร้านสามารถใช้รหัสโต๊ะ T01, T02 ซ้ำกันคนละร้านได้
ALTER TABLE tables DROP INDEX IF EXISTS table_code;
ALTER TABLE tables ADD UNIQUE INDEX IF NOT EXISTS uniq_shop_table (shop_id, table_code);

-- 5. ปรับ Unique Index ของ settings ให้แยกตามร้านค้า (shop_id, setting_key)
ALTER TABLE settings DROP INDEX IF EXISTS setting_key;
ALTER TABLE settings ADD UNIQUE INDEX IF NOT EXISTS uniq_shop_setting (shop_id, setting_key);
