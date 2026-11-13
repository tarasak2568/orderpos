-- ============================================
-- ระบบ POS ร้านอาหาร (Multi-Tenant SaaS + Dynamic SEO)
-- โครงสร้างฐานข้อมูลหลัก
-- ============================================

CREATE DATABASE IF NOT EXISTS orderpos_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE orderpos_db;

-- ============================================
-- ตารางร้านค้าสมาชิก (Multi-Tenant Shops)
-- ============================================
CREATE TABLE IF NOT EXISTS shops (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shop_name VARCHAR(150) NOT NULL,
    shop_slug VARCHAR(100) NOT NULL UNIQUE,
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

-- ============================================
-- ตารางผู้ใช้งานระบบ (แยกตามร้าน shop_id)
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shop_id INT NOT NULL DEFAULT 1,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('superadmin', 'admin', 'staff', 'kitchen') NOT NULL DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- ตารางหมวดหมู่เมนู (แยกตามร้าน shop_id)
-- ============================================
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shop_id INT NOT NULL DEFAULT 1,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(50) DEFAULT 'fa-utensils',
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- ตารางเมนูอาหาร (แยกตามร้าน shop_id)
-- ============================================
CREATE TABLE IF NOT EXISTS menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shop_id INT NOT NULL DEFAULT 1,
    category_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    is_available TINYINT(1) DEFAULT 1,
    is_recommended TINYINT(1) DEFAULT 0,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- ตารางโต๊ะอาหาร (แยกตามร้าน shop_id)
-- ============================================
CREATE TABLE IF NOT EXISTS tables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shop_id INT NOT NULL DEFAULT 1,
    table_code VARCHAR(10) NOT NULL,
    table_name VARCHAR(50) NOT NULL,
    qr_code VARCHAR(255),
    status ENUM('available', 'occupied', 'reserved') DEFAULT 'available',
    capacity INT DEFAULT 4,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_shop_table (shop_id, table_code),
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- ตารางออเดอร์ (แยกตามร้าน shop_id)
-- ============================================
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shop_id INT NOT NULL DEFAULT 1,
    order_no VARCHAR(20) NOT NULL UNIQUE,
    table_id INT NOT NULL,
    customer_name VARCHAR(100),
    customer_phone VARCHAR(20),
    note TEXT,
    total_amount DECIMAL(10,2) DEFAULT 0,
    status ENUM('pending', 'preparing', 'ready', 'served', 'paid', 'cancelled') DEFAULT 'pending',
    payment_status ENUM('unpaid', 'paid') DEFAULT 'unpaid',
    payment_method ENUM('cash', 'transfer', 'promptpay', 'card') DEFAULT NULL,
    paid_at TIMESTAMP NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    FOREIGN KEY (table_id) REFERENCES tables(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- ตารางรายการในออเดอร์
-- ============================================
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    menu_item_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    note TEXT,
    status ENUM('pending', 'preparing', 'ready', 'served', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- ตารางการชำระเงิน (แยกตามร้าน shop_id)
-- ============================================
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shop_id INT NOT NULL DEFAULT 1,
    order_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'transfer', 'promptpay', 'card') NOT NULL,
    cash_received DECIMAL(10,2),
    change_amount DECIMAL(10,2),
    slip_image VARCHAR(255),
    paid_by INT,
    paid_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (paid_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- ตารางการตั้งค่าร้าน (แยกตามร้าน shop_id)
-- ============================================
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shop_id INT NOT NULL DEFAULT 1,
    setting_key VARCHAR(50) NOT NULL,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_shop_setting (shop_id, setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- ข้อมูลเริ่มต้น (ร้านที่ 1)
-- ============================================
INSERT INTO shops (id, shop_name, shop_slug, description, phone, address, promptpay_number, seo_title, seo_description) VALUES
(1, 'ร้านอาหารของเรา', 'main-shop', 'ร้านอาหารอร่อย สั่งอาหารผ่านมือถือสะดวกรวดเร็วด้วยระบบ OrderPOS', '02-123-4567', '123 ถนนสุขใจ แขวงสนุก เขตบันเทิง กรุงเทพฯ 10100', '0812345678', 'ร้านอาหารของเรา - สั่งอาหารออนไลน์ & เมนูแนะนำ | OrderPOS', 'สั่งอาหารออนไลน์ผ่าน QR Code จากโต๊ะอาหาร เมนูหลากหลาย รวดเร็ว ทันสมัย');

-- ผู้ดูแลระบบเริ่มต้น (รหัสผ่าน: admin123)
INSERT INTO users (shop_id, username, password, full_name, role) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ผู้ดูแลระบบ', 'admin');

-- หมวดหมู่เริ่มต้น
INSERT INTO categories (shop_id, name, icon, sort_order) VALUES
(1, 'อาหารแนะนำ', 'fa-star', 1),
(1, 'อาหารจานเดียว', 'fa-utensils', 2),
(1, 'กับข้าว', 'fa-bowl-food', 3),
(1, 'เครื่องดื่ม', 'fa-mug-saucer', 4),
(1, 'ของหวาน', 'fa-cake-candles', 5),
(1, 'สลัด/ยำ', 'fa-leaf', 6);

-- เมนูตัวอย่าง
INSERT INTO menu_items (shop_id, category_id, name, description, price, is_recommended) VALUES
(1, 1, 'ข้าวผัดกระเพราหมูกรอบ', 'ข้าวผัดกระเพราหมูกรอบ ไข่ดาว', 65, 1),
(1, 1, 'ต้มยำกุ้งน้ำข้น', 'ต้มยำกุ้งน้ำข้น ใส่กุ้งสด เห็ดฟาง', 120, 1),
(1, 2, 'ข้าวมันไก่ต้ม', 'ข้าวมันไก่ต้ม น้ำจิ้มเต้าเจี้ยว', 55, 0),
(1, 2, 'ข้าวหมูแดง', 'ข้าวหมูแดง หมูกรอบ ไข่ต้ม', 60, 0),
(1, 3, 'ผัดผักรวมมิตร', 'ผัดผักรวมมิตรใส่กุ้ง', 80, 0),
(1, 3, 'แกงเขียวหวานไก่', 'แกงเขียวหวานไก่ ใส่ถั่วฝักยาว', 70, 0),
(1, 4, 'น้ำเปล่า', 'น้ำดื่มสะอาด 600ml', 10, 0),
(1, 4, 'โค้ก', 'โค้ก กระป๋อง 325ml', 20, 0),
(1, 4, 'ชาเขียว', 'ชาเขียวเย็น หวานน้อย', 35, 0),
(1, 5, 'ไอศครีมกะทิ', 'ไอศครีมกะทิสด ใส่ถั่วแดง', 45, 0),
(1, 5, 'บัวลอย', 'บัวลอยไข่หวาน น้ำกะทิ', 40, 0);

-- โต๊ะตัวอย่าง
INSERT INTO tables (shop_id, table_code, table_name, capacity) VALUES
(1, 'T01', 'โต๊ะ 1', 2),
(1, 'T02', 'โต๊ะ 2', 2),
(1, 'T03', 'โต๊ะ 3', 4),
(1, 'T04', 'โต๊ะ 4', 4),
(1, 'T05', 'โต๊ะ 5', 6),
(1, 'T06', 'โต๊ะ 6', 6),
(1, 'T07', 'โต๊ะ 7', 8),
(1, 'T08', 'โต๊ะ 8', 8),
(1, 'T09', 'โต๊ะ 9', 4),
(1, 'T10', 'โต๊ะ 10', 4);
