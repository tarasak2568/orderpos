# ระบบ POS ร้านอาหาร & ร้านของชำออนไลน์ Multi-Tenant SaaS (`OrderPOS`)

แพลตฟอร์มบริหารจัดการร้านค้าแบบ Multi-Tenant Cloud POS ที่รองรับทั้ง **ร้านอาหาร / คาเฟ่** (สแกน QR Code ประจำโต๊ะ, จอห้องครัว KDS, คิดเงินรายโต๊ะ) และ **ร้านของชำ / มินิมาร์ท / ค้าปลีก** (สแกนบาร์โค้ด SKU, ตัดสต๊อกอัตโนมัติ, คิดเงินด่วนหน้า POS + PromptPay QR) พร้อมระบบ **Dynamic SEO** ติดตั้งมาในตัวสำหรับทุกร้านค้าสมาชิก

---

## 🔗 URL หลักบน Hostinger

* **เว็บไซต์หลัก**: `https://darkseagreen-dogfish-109650.hostingersite.com/`
* **หลังบ้าน (`/admin/` หรือ `/login.php`)**: `https://darkseagreen-dogfish-109650.hostingersite.com/admin/`
* **สมัครเปิดร้านใหม่ (`/register`)**: `https://darkseagreen-dogfish-109650.hostingersite.com/register`
* **Dynamic XML Sitemap**: `https://darkseagreen-dogfish-109650.hostingersite.com/sitemap.xml`

## 🔑 ข้อมูลเข้าสู่ระบบเริ่มต้น (ร้านหลัก `id = 1`)

| ชื่อผู้ใช้ | รหัสผ่าน | บทบาท | หมายเหตุ |
| :--- | :--- | :--- | :--- |
| `admin` | `admin123` | ผู้ดูแลระบบ | อ้างอิงค่าเริ่มต้นของระบบหลัก (ควรเปลี่ยนหลังเข้าสู่ระบบครั้งแรก) |

> หากสมัครเปิดร้านใหม่ผ่าน `/register` ระบบจะสร้างบัญชีผู้ดูแล (`role = admin`), ข้อมูลร้าน (`shops`), หมวดหมู่ และรายการเริ่มต้นแยกตาม `shop_id` ให้อัตโนมัติ

---

## 🏪 โหมดธุรกิจที่รองรับ (`shops.shop_type`)

| โหมด | เหมาะสำหรับ | ฟีเจอร์หลัก |
| :--- | :--- | :--- |
| `restaurant` | ร้านอาหาร, คาเฟ่, ร้านน้ำ | จัดการโต๊ะ + QR Menu ประจำโต๊ะ, ส่งออเดอร์เข้าครัว (`kitchen.php`), คิดเงินรายโต๊ะ (`billing.php`) |
| `retail` | ร้านของชำ, มินิมาร์ท, ค้าปลีก | ค้นหา/สแกนบาร์โค้ด (`sku`), ติดตามและตัดสต๊อก (`stock_qty`), คิดเงินด่วน Walk-in (`pos.php`), PromptPay QR |

---

## 📄 รายการหน้าทั้งหมดในระบบ

### หน้าสาธารณะ & การเข้าใช้งาน
| หน้า | URL | คำอธิบาย | SEO Robots |
| :--- | :--- | :--- | :--- |
| 🏠 หน้าแรก | `/` หรือ `/index.php` | พาเข้าสู่ `/login.php` | `index, follow` |
| 🔐 เข้าสู่ระบบ | `/login.php` หรือ `/admin/` | ล็อกอินแยกตามร้าน (`shop_id`) และไป `/admin/dashboard.php` | `index, follow` |
| 🆕 เปิดร้านใหม่ | `/register` หรือ `/register.php` | สมัครสมาชิกเปิดร้านอาหาร/ร้านของชำ พร้อมสร้าง Dynamic SEO อัตโนมัติ | `index, follow` |
| 📱 หน้าร้านลูกค้า | `/shop/{slug}` หรือ `/menu.php?shop={slug}` | หน้าเมนูอาหาร / แคตตาล็อกสินค้าสำหรับลูกค้าสั่งซื้อออนไลน์ | `index, follow` |
| 📱 QR ประจำโต๊ะ | `/shop/{slug}/table/{code}` | ลิงก์สแกนสั่งอาหารประจำโต๊ะของร้านอาหาร | `index, follow` |
| 🗺️ Sitemap / Robots | `/sitemap.xml` (`sitemap.php`), `/robots.txt` | สร้าง XML Sitemap ของทุกร้านที่เปิดใช้งานอัตโนมัติ | — |
| 🚪 ออกจากระบบ | `/logout.php` | ล้าง Session และกลับสู่หน้า Login | — |

### ระบบปฏิบัติการหน้าร้าน & หลังบ้าน (ต้องล็อกอิน)
| หน้า | URL | คำอธิบาย |
| :--- | :--- | :--- |
| 💻 ขายหน้าร้าน (POS) | `/pos.php` | รับออเดอร์ตามโต๊ะ หรือโหมด Walk-in + ค้นหาบาร์โค้ด + คิดเงินด่วน |
| 🍳 หน้าจอครัว (KDS) | `/kitchen.php` | ติดตามสถานะออเดอร์แบบเรียลไทม์ (`กำลังทำ`, `ทำเสร็จ`, `เสิร์ฟแล้ว`) |
| 💰 คิดเงิน (Billing) | `/billing.php` | คิดเงินออเดอร์ค้างชำระ (เงินสด / โอน / PromptPay / บัตร) |
| 📊 Dashboard | `/admin/dashboard.php` | ภาพรวมยอดขายรายวัน สถิติออเดอร์ และสถานะร้าน |
| 📖 จัดการเมนู/สินค้า | `/admin/menu_manage.php` | เพิ่ม/แก้ไข/ลบ รายการ, SKU บาร์โค้ด, จำนวนสต๊อก, ปุ่มพร้อมขาย/หยุดขาย, สินค้าแนะนำ |
| 🪑 จัดการโต๊ะ/จุดขาย | `/admin/tables_manage.php` | เพิ่ม/แก้ไข/ลบ รหัสโต๊ะ ชื่อโต๊ะ ที่นั่ง และสถานะ (`available`, `occupied`, `reserved`) |
| 📱 QR Code โต๊ะ/ร้าน | `/admin/qr_tables.php` | สร้าง พิมพ์ ดาวน์โหลด และคัดลอกลิงก์ QR Code ของร้านและแต่ละโต๊ะ |
| 📋 ดูออเดอร์ทั้งหมด | `/admin/orders_manage.php` | ประวัติออเดอร์และรายละเอียดรายการแยกตามร้าน |
| ⚙️ ตั้งค่าร้าน & SEO | `/admin/shop_settings.php` | แก้ไขชื่อร้าน, โหมดธุรกิจ, Slug URL, เบอร์โทร/พร้อมเพย์, ที่อยู่ และตัวช่วยสร้าง Dynamic SEO (1-Click) |

### API (JSON — แยกข้อมูลตาม `shop_id`)
| API | URL | เมธอดที่รองรับ |
| :--- | :--- | :--- |
| 📦 หมวดหมู่ | `/api/categories.php` | `GET`, `POST`, `PUT`, `DELETE` |
| 🍽️ เมนู / สินค้า | `/api/menu_items.php` | `GET`, `POST`, `PUT`, `DELETE` (รองรับ `?search=`, `?category_id=`, `?all=1`, `sku`, `stock_qty`, `is_available`) |
| 🪑 โต๊ะ | `/api/tables.php` | `GET`, `POST`, `PUT`, `DELETE` |
| 📋 ออเดอร์ & ชำระเงิน | `/api/orders.php` | `GET`, `POST`, `PUT` |
| 📱 QR Code โต๊ะ | `/api/qr_tables.php` | `GET` |

---

## 🗄️ โครงสร้างฐานข้อมูลหลัก (`sql/schema.sql`, `sql/migrate_saas.sql`, `config/saas.php`)

| ตาราง | คำอธิบาย |
| :--- | :--- |
| `shops` | ข้อมูลร้านค้าสมาชิก, `shop_slug`, `shop_type` (`restaurant`/`retail`), พร้อมเพย์, และค่า SEO (`seo_title`, `seo_description`, `seo_keywords`) |
| `users` | บัญชีผู้ใช้งานแยกตาม `shop_id` (`superadmin`, `admin`, `staff`, `kitchen`) |
| `categories` | หมวดหมู่เมนูอาหาร / สินค้าแยกตาม `shop_id` |
| `menu_items` | รายการอาหาร / สินค้า, `sku` (บาร์โค้ด), `stock_qty` (สต๊อก), สถานะพร้อมขายและรายการแนะนำ |
| `tables` | โต๊ะอาหารและจุดขายหน้าร้าน (`WALKIN`) พร้อม Unique Key `(shop_id, table_code)` |
| `orders` | คำสั่งซื้อแยกตาม `shop_id` |
| `order_items` | รายการย่อยภายในออเดอร์ |
| `payments` | ประวัติการรับชำระเงิน |
| `settings` | การตั้งค่าเฉพาะร้านแบบ Key-Value แยกตาม `(shop_id, setting_key)` |

---

## 🛠️ เทคโนโลยีและโครงสร้างไฟล์ปัจจุบัน

* **Backend:** PHP 8+ (Pure PHP, ไม่ใช้ Framework), MySQL / MariaDB
* **Frontend:** HTML5, CSS3, Vanilla JavaScript, Font Awesome 6, Prompt Font
* **SEO Engine (`config/seo.php`):** Dynamic Meta Tags, Open Graph, Twitter Cards, Schema.org (`SoftwareApplication`, `Restaurant`+`Menu`, `Store`/`GroceryStore`+`OfferCatalog`, `BreadcrumbList`), และ Dynamic XML Sitemap (`sitemap.php`)
* **โครงสร้างโฟลเดอร์ (เฉพาะระบบ POS):** `admin/`, `api/`, `assets/`, `config/`, `sql/` *(ตัดไฟล์ระบบบล็อก/เว็บไซต์เดิมที่ไม่เกี่ยวข้องกับ POS ออกทั้งหมดแล้ว)*
* **ฐานข้อมูล Hostinger:** `u262149987_orderpos_db` (ตั้งค่าผ่าน `config/db.php` ซึ่งไม่อยู่ใน Git tracking)
