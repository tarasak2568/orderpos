# แผนพัฒนา OrderPOS สู่ระบบบริการ Multi-Tenant SaaS + Dynamic SEO

เอกสารนี้สรุปโครงสร้างและแผนการอัปเกรดระบบ **OrderPOS** จากระบบจัดการร้านอาหารเดี่ยว (Single-Tenant) ให้กลายเป็น **แพลตฟอร์มบริการระบบ POS ออนไลน์ (Multi-Tenant SaaS)** ที่ลูกค้าสามารถสมัครสมาชิก ตั้งชื่อร้านของตัวเอง และมีระบบ **Dynamic SEO** อัตโนมัติสำหรับทั้งแพลตฟอร์มหลักและหน้าร้านของสมาชิกแต่ละราย

---

## 1. ภาพรวมสถาปัตยกรรมระบบ (Multi-Tenant SaaS Architecture)

รูปแบบที่ใช้คือ **Shared Database, Shared Schema (แยกข้อมูลด้วย `shop_id`)** ซึ่งเหมาะสมที่สุดสำหรับโฮสติ้งบน Hostinger ดูแลรักษาง่าย และขยายจำนวนร้านค้าได้ไม่จำกัดโดยไม่ต้องสร้างฐานข้อมูลใหม่ทีละร้าน

### Flow การใช้งานของลูกค้า (เจ้าของร้านอาหาร)
```
เข้าหน้าเว็บหลัก (Landing Page / Login)
    ↓
กดปุ่ม "สมัครเปิดร้านฟรี" (register.php)
    ↓
กรอก: ชื่อร้านอาหาร, รหัสร้าน (Slug URL), ชื่อผู้ใช้, รหัสผ่าน, เบอร์โทรศัพท์
    ↓
ระบบสร้างข้อมูลร้าน (shops) + สร้างบัญชีเจ้าของร้าน (users) + สร้างหมวดหมู่และโต๊ะเริ่มต้นอัตโนมัติ
    ↓
เข้าสู่ระบบหลังบ้าน (admin/dashboard.php) ของร้านตัวเองทันที
    ↓
สร้าง QR Code ประจำโต๊ะ → ลูกค้าสแกนเข้าหน้าร้าน (/shop/{slug} หรือ menu.php?shop={slug}) พร้อมติด SEO ของร้านนั้นโดยอัตโนมัติ
```

---

## 2. การออกแบบและปรับปรุงฐานข้อมูล (Database Schema Upgrade)

### 2.1 ตารางใหม่: `shops` (ข้อมูลร้านค้าสมาชิกและตั้งค่า SEO ประจำร้าน)
| คอลัมน์ | ประเภทข้อมูล | คำอธิบาย |
| :--- | :--- | :--- |
| `id` | `INT AUTO_INCREMENT PRIMARY KEY` | รหัสร้านค้าหลัก (`shop_id`) |
| `shop_name` | `VARCHAR(150) NOT NULL` | ชื่อร้านอาหาร (ที่ลูกค้าตั้งเอง) |
| `shop_slug` | `VARCHAR(100) NOT NULL UNIQUE` | ชื่อ URL ของร้าน เช่น `krapow-inter` (ใช้ทำ Friendly URL `/shop/krapow-inter`) |
| `description` | `TEXT NULL` | คำอธิบายร้านอาหาร (ใช้แสดงหน้าร้านและเป็น Meta Description อัตโนมัติ) |
| `logo_image` | `VARCHAR(255) NULL` | โลโก้ร้าน / รูปปก (ใช้แสดงผลและเป็น `og:image`) |
| `phone` | `VARCHAR(30) NULL` | เบอร์โทรติดต่อร้าน |
| `address` | `TEXT NULL` | ที่อยู่ร้านอาหาร (ใช้ใน LocalBusiness / Restaurant Schema) |
| `promptpay_number` | `VARCHAR(30) NULL` | เบอร์หรือเลขพร้อมเพย์สำหรับรับเงินของร้าน |
| `currency` | `VARCHAR(10) DEFAULT '฿'` | สกุลเงิน |
| `tax_rate` | `DECIMAL(5,2) DEFAULT 0.00` | อัตราภาษี (%) |
| `service_charge` | `DECIMAL(5,2) DEFAULT 0.00` | ค่าบริการ (%) |
| `seo_title` | `VARCHAR(200) NULL` | Custom SEO Title (ถ้าไม่กรอก ระบบจะเจนจาก `shop_name` อัตโนมัติ) |
| `seo_description` | `VARCHAR(300) NULL` | Custom SEO Description (ถ้าไม่กรอก ระบบจะเจนจากเมนูแนะนำและชื่อร้านอัตโนมัติ) |
| `seo_keywords` | `VARCHAR(255) NULL` | คีย์เวิร์ดประจำร้าน |
| `is_active` | `TINYINT(1) DEFAULT 1` | สถานะเปิดใช้งานร้าน (`1` = ใช้งานได้, `0` = ระงับชั่วคราว) |
| `plan_type` | `ENUM('free','pro','enterprise') DEFAULT 'free'` | แพ็กเกจสมาชิก |
| `created_at` | `TIMESTAMP DEFAULT CURRENT_TIMESTAMP` | วันที่สมัคร |
| `updated_at` | `TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` | วันที่แก้ไขล่าสุด |

### 2.2 เพิ่ม `shop_id` ในตารางเดิมทั้งหมดเพื่อแยกข้อมูลแต่ละร้าน (Tenant Isolation)
- **`users`**: เพิ่ม `shop_id INT NOT NULL` และปรับ `role` ให้รองรับ `superadmin` (เจ้าของแพลตฟอร์ม), `admin` (เจ้าของร้าน), `staff`, `kitchen`
- **`categories`**: เพิ่ม `shop_id INT NOT NULL`
- **`menu_items`**: เพิ่ม `shop_id INT NOT NULL`
- **`tables`**: เพิ่ม `shop_id INT NOT NULL` และเปลี่ยน Unique Index จาก `(table_code)` เป็น `(shop_id, table_code)` เพื่อให้แต่ละร้านมีโต๊ะ `T01`, `T02` ของตัวเองได้ไม่ชนกัน
- **`orders`**: เพิ่ม `shop_id INT NOT NULL`
- **`payments`**: เพิ่ม `shop_id INT NOT NULL`
- **`settings`**: เพิ่ม `shop_id INT NOT NULL` และเปลี่ยน Unique Index เป็น `(shop_id, setting_key)`

---

## 3. ระบบ Dynamic SEO แบบครบวงจร (Technical & Programmatic SEO)

เพื่อให้ทั้งตัวแพลตฟอร์ม **OrderPOS** และ **หน้าร้านค้าของลูกค้าทุกร้าน** ติดอันดับการค้นหาบน Google และ AI Search (ChatGPT, Gemini, Perplexity) ระบบจะมีโมดูล `config/seo.php` ที่สร้าง SEO Tags แบบไดนามิกตามข้อมูลจริงจากฐานข้อมูล:

### 3.1 Clean & Friendly URLs (`.htaccess` Rewrite Rules)
รองรับ URL ที่อ่านง่ายและดีต่อ SEO:
- `https://domain.com/` → หน้าแรกแนะนำระบบบริการ OrderPOS / เข้าสู่ระบบ
- `https://domain.com/register` → หน้าสมัครเปิดร้านใหม่ (`register.php`)
- `https://domain.com/shop/{shop_slug}` → หน้าโปรไฟล์และสั่งอาหารของร้านนั้นๆ (`menu.php?shop_slug={shop_slug}`)
- `https://domain.com/shop/{shop_slug}/table/{table_code}` → ลิงก์สแกนสั่งอาหารประจำโต๊ะ
- `https://domain.com/sitemap.xml` → วิ่งไปที่ `sitemap.php` เพื่อเจน XML Sitemap แบบเรียลไทม์

### 3.2 Dynamic Meta Tags, Open Graph & Twitter Cards (`config/seo.php`)
เมื่อเปิดหน้าเมนูของแต่ละร้าน (`menu.php`) ระบบจะดึงข้อมูลจากตาราง `shops` และ `menu_items` มาสร้าง `<head>` อัตโนมัติ:
- **`<title>`**: `{ชื่อร้าน} - สั่งอาหารออนไลน์ & เมนูแนะนำ | OrderPOS` (หรือใช้ `seo_title` ที่ร้านตั้งไว้)
- **`<meta name="description">`**: ดึงคำอธิบายร้าน พร้อมรายชื่อเมนูแนะนำ 3-5 รายการแรกและราคาเริ่มต้น มาสร้างคำโปรยอัตโนมัติ
- **`<link rel="canonical">`**: ลิงก์หลักที่ถูกต้องของหน้าร้าน (`https://domain.com/shop/{shop_slug}`) ป้องกันปัญหา Duplicate Content เวลามีพารามิเตอร์ `?table=T01`
- **Open Graph (`og:title`, `og:description`, `og:image`, `og:url`, `og:type="restaurant.restaurant"`)**: เวลาเจ้าของร้านแชร์ลิงก์ร้านลง Facebook / LINE จะขึ้นชื่อร้าน รูปโลโก้/อาหาร และคำอธิบายสวยงามทันที
- **Robots Meta Control**:
  - หน้าสาธารณะ (`index.php`, `login.php`, `register.php`, `menu.php`): `index, follow, max-image-preview:large`
  - หน้าภายในร้าน (`pos.php`, `kitchen.php`, `billing.php`, `admin/*`): `noindex, nofollow` เพื่อความปลอดภัยและไม่ให้หน้าหลังบ้านหลุดเข้า Google

### 3.3 Dynamic JSON-LD Schema.org Structured Data
สร้างโค้ด `<script type="application/ld+json">` อัตโนมัติตามประเภทหน้า:
1. **หน้าแพลตฟอร์มหลัก (`login.php` / `register.php`)**:
   - ใช้ Schema ประเภท `SoftwareApplication` (ApplicationCategory: `BusinessApplication`, OperatingSystem: `Web`) อธิบายบริการระบบ OrderPOS
2. **หน้าร้านของสมาชิก (`menu.php`)**:
   - ใช้ Schema ประเภท **`Restaurant`** ผูกกับ **`Menu`** และ **`MenuItem`**
   - ดึงชื่อร้าน เบอร์โทร ที่อยู่ ช่วงราคา และรายการเมนูอาหารพร้อมราคาจริงในฐานข้อมูลใส่ลงใน `hasMenu` อัตโนมัติ ช่วยให้ Google และ AI Search เข้าใจว่าหน้าร้านนี้ขายเมนูอะไรบ้างและราคาเท่าไหร่

### 3.4 Dynamic XML Sitemap (`sitemap.php`) & `robots.txt`
- สร้างไฟล์ **`sitemap.php`** ที่ดึงรายชื่อร้านค้าทั้งหมดที่ `is_active = 1` จากฐานข้อมูล รวมถึงบทความใน `astroyu_blogs` (ถ้ามี) มาสร้างเป็น XML Sitemap มาตรฐานสากล พร้อม `<lastmod>` อัตโนมัติทุกครั้งที่บอทเข้ามาเก็บข้อมูล
- ตั้งค่า `.htaccess` ให้ `/sitemap.xml` เรียกใช้งาน `sitemap.php`
- อัปเดต `robots.txt` ให้ชี้ `Sitemap:` มายังโดเมนปัจจุบันและอนุญาต AI Crawlers (GPTBot, Google-Extended, ClaudeBot)

---

## 4. รายการไฟล์ที่จะสร้างใหม่และอัปเดต

### ไฟล์สร้างใหม่ (New Files)
1. `sql/migrate_saas.sql` — สคริปต์อัปเกรดฐานข้อมูลเพิ่มตาราง `shops` และเพิ่ม `shop_id` ในตารางเดิมโดยข้อมูลเก่าไม่หาย
2. `config/seo.php` — ฟังก์ชันกลางสำหรับสร้าง Dynamic Title, Meta Tags, Open Graph และ JSON-LD Schema (`Restaurant` & `SoftwareApplication`)
3. `register.php` — หน้าสมัครสมาชิกเปิดร้านใหม่ (ดีไซน์ Modern เข้าชุดกับ `login.php`) พร้อมระบบตั้งชื่อร้าน + สุ่ม/ตั้ง `shop_slug` + สร้างข้อมูลเริ่มต้นอัตโนมัติ
4. `admin/shop_settings.php` — หน้าตั้งค่าข้อมูลร้านค้า (เปลี่ยนชื่อร้าน, URL ร้าน, เบอร์โทร, พร้อมเพย์, ที่อยู่ และตั้งค่า SEO ของร้าน)
5. `sitemap.php` — ระบบสร้าง Dynamic XML Sitemap อัตโนมัติสำหรับทุกหน้าร้านที่เปิดใช้งาน

### ไฟล์ที่ต้องอัปเดต (Modified Files)
1. `config/db.php` — เพิ่ม Helper Function สำหรับดึง `currentShopId()` และข้อมูลร้านปัจจุบัน (`getCurrentShop()`)
2. `login.php` — เก็บ `$_SESSION['shop_id']`, `$_SESSION['shop_name']`, `$_SESSION['shop_slug']` ตอนล็อกอิน + เพิ่มปุ่มลิงก์ไปหน้า **"สมัครเปิดร้านใหม่"** + ติดตั้ง Dynamic SEO
3. `admin/header.php`, `admin/dashboard.php`, `admin/menu_manage.php`, `admin/tables_manage.php`, `admin/qr_tables.php`, `admin/orders_manage.php` — แสดงชื่อร้านของลูกค้าบน Navbar/Sidebar, เพิ่มเมนูตั้งค่าร้าน และกรองข้อมูลด้วย `WHERE shop_id = ...`
4. `pos.php`, `kitchen.php`, `billing.php` — แสดงชื่อร้านของลูกค้า และดึงเฉพาะโต๊ะ/ออเดอร์ของ `shop_id` ตัวเอง
5. `menu.php` — รองรับการเปิดดูเมนูตามร้าน (`?shop=` หรือ `/shop/{slug}`) พร้อมแสดงชื่อร้านและฝัง **Dynamic SEO + Restaurant JSON-LD Schema**
6. `api/categories.php`, `api/menu_items.php`, `api/tables.php`, `api/orders.php`, `api/qr_tables.php` — กรองและบันทึกข้อมูลแยกตาม `shop_id` ทุกจุด
7. `.htaccess` & `robots.txt` — เพิ่ม Rewrite Rules สำหรับ `/shop/{slug}`, `/register`, และ `/sitemap.xml`

---

## 5. ขั้นตอนการพัฒนา (Implementation Roadmap)

- **เฟสที่ 1: Database & Core Multi-Tenancy**
  - สร้างและรันสคริปต์อัปเกรดฐานข้อมูล (`shops` + `shop_id`)
  - อัปเดต `config/db.php` ให้รองรับ Session ของร้านค้าและการดึงข้อมูลร้าน
- **เฟสที่ 2: ระบบสมัครสมาชิก & ตั้งค่าร้านค้า**
  - สร้างหน้า `register.php` ให้ลูกค้าสมัครและตั้งชื่อร้านได้ทันที
  - อัปเดต `login.php` เชื่อมต่อหน้าสมัครสมาชิก
  - สร้างหน้า `admin/shop_settings.php` ให้เจ้าของร้านแก้ไขชื่อร้านและตั้งค่า SEO ได้เอง
- **เฟสที่ 3: แยกข้อมูล POS / ครัว / หลังบ้าน ตามร้านค้า**
  - อัปเดต API ทั้งหมด (`categories`, `menu_items`, `tables`, `orders`, `qr_tables`) และหน้า `pos.php`, `kitchen.php`, `billing.php`, `admin/*` ให้แยกตาม `shop_id`
- **เฟสที่ 4: ระบบ Dynamic SEO & Friendly URLs**
  - สร้าง `config/seo.php` และ `sitemap.php`
  - อัปเดต `menu.php`, `login.php`, `register.php`, `.htaccess` ให้รองรับ Dynamic Meta, Open Graph, JSON-LD `Restaurant` Schema และ Dynamic Sitemap
- **เฟสที่ 5: ทดสอบการทำงานครบวงจร**
  - ทดสอบสมัครร้านใหม่ 2 ร้าน ตั้งชื่อร้านต่างกัน และตรวจสอบว่าเมนู โต๊ะ ออเดอร์ QR Code และ SEO ของแต่ละร้านแยกกันอย่างสมบูรณ์
