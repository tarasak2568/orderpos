# สถาปัตยกรรม Multi-Tenant SaaS & กลยุทธ์ Dynamic SEO ของ OrderPOS

เอกสารนี้รวบรวมโครงสร้างระบบ **OrderPOS Cloud SaaS** (รองรับทั้งโหมดร้านอาหาร/คาเฟ่ และโหมดร้านของชำ/ค้าปลีก), คู่มือ SEO เชิงลึกพร้อมชุดคีย์เวิร์ดแนะนำ, การปรับปรุงหน้าจัดการข้อมูล, และการทำความสะอาดโค้ดเบสให้เหลือเฉพาะระบบ POS

---

## 1. สถาปัตยกรรมระบบ Multi-Tenant SaaS (`config/saas.php`)

* ใช้โมเดล **Shared Database, Shared Schema (แยกข้อมูลด้วย `shop_id`)** ทำงานร่วมกับฐานข้อมูล `u262149987_orderpos_db`
* เมื่อมีผู้ใช้งานสมัครร้านใหม่ผ่าน `register.php`:
  1. สร้างรายการในตาราง `shops` พร้อม Slug URL เฉพาะตัว (`createUniqueShopSlug()`) และค่า SEO เริ่มต้น (`generateShopSeoPreset()`)
  2. สร้างบัญชีเจ้าของร้าน (`role = 'admin'`) ใน `users` ด้วย `password_hash()`
  3. รัน `seedNewShopDefaults($shopId, $shopType)` สร้างหมวดหมู่ รายการสินค้า/เมนูตัวอย่าง และจุดขาย/โต๊ะเริ่มต้นตามประเภทธุรกิจ (`restaurant` หรือ `retail`)
  4. เข้าสู่หน้า `/admin/dashboard.php?welcome=1` ทันที

### รองรับ 2 โหมดธุรกิจในระบบเดียว (`shops.shop_type`)
1. **`restaurant` (ร้านอาหาร / คาเฟ่)**: มีโต๊ะอาหาร, QR Menu ประจำโต๊ะ (`/shop/{slug}/table/{code}`), จอห้องครัว KDS (`kitchen.php`), และปิดบิลรายโต๊ะ (`billing.php`)
2. **`retail` (ร้านของชำ / มินิมาร์ท / ค้าปลีก)**: เพิ่มช่องบาร์โค้ด (`sku`) และสต๊อกคงเหลือ (`stock_qty`), โหมดขายหน้าร้าน Walk-in คิดเงินด่วนทันทีใน `pos.php`, ค้นหาด้วยบาร์โค้ด, และตัดสต๊อกอัตโนมัติ

---

## 2. ระบบ Dynamic SEO & Structured Data (`config/seo.php`)

โมดูล `config/seo.php` สร้างแท็ก SEO แบบไดนามิกสำหรับทุกหน้าผ่าน `renderDynamicSeoTags()`:

### 2.1 Meta Tags, Open Graph & Twitter Card
* **Robots Directive**:
  * หน้าสาธารณะ (`/login.php`, `/register.php`, `/menu.php`): `index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1`
  * หน้าภายในร้าน (`/pos.php`, `/kitchen.php`, `/billing.php`, `/admin/*`): `noindex, nofollow`
* **Canonical URL**: หน้าเมนูร้านค้ากำหนด canonical เป็น `/shop/{shop_slug}` เสมอ เพื่อป้องกัน Duplicate Content จากพารามิเตอร์ `?table_id=...`
* **Social Sharing**: สร้างครบทั้ง `og:type`, `og:title`, `og:description`, `og:image`, `og:image:alt`, `og:site_name`, `og:locale="th_TH"` และ `twitter:card="summary_large_image"` พร้อม `<meta name="theme-color" content="#FF6B35">`

### 2.2 JSON-LD Schema.org แยกตามประเภทหน้าและธุรกิจ
1. **หน้าแพลตฟอร์ม (`type = 'platform'`)**:
   * `SoftwareApplication` (`applicationCategory: BusinessApplication`, `inLanguage: th`, `featureList`, `Organization`, `Offer` ฟรี)
2. **หน้าร้านอาหาร (`shop_type = 'restaurant'`)**:
   * `Restaurant` + `Menu` → `MenuSection` → `MenuItem` พร้อมช่วงราคาและรายการเมนูจริง + `BreadcrumbList`
3. **หน้าร้านของชำ / ค้าปลีก (`shop_type = 'retail'`)**:
   * `Store` (`additionalType: https://schema.org/GroceryStore`) + `OfferCatalog` → `Product` (พร้อม `sku` และ `InStock`/`OutOfStock` ตามสต๊อกจริง) + `BreadcrumbList`

### 2.3 Clean URLs & Sitemap (`.htaccess`, `sitemap.php`, `robots.txt`)
* `.htaccess` กำหนด `DirectoryIndex index.php` และ Rewrite เฉพาะเส้นทาง POS:
  * `/sitemap.xml` → `sitemap.php` (ดึงหน้าหลักและร้านค้าที่ `is_active = 1` จาก `shops`)
  * `/register` → `register.php`
  * `/shop/{slug}/table/{table_code}` → `menu.php?shop={slug}&table_id={table_code}`
  * `/shop/{slug}` → `menu.php?shop={slug}`

---

## 3. ชุดคีย์เวิร์ด SEO คุณภาพสูง (`getOrderPosKeywordSets()`)

จัดกลุ่มคีย์เวิร์ดตามเจตนาการค้นหา (Search Intent) และความยาวไม่เกิน `VARCHAR(255)` ของคอลัมน์ `seo_keywords`:

### 3.1 คีย์เวิร์ดระดับแพลตฟอร์ม SaaS (สำหรับดึงเจ้าของร้านสมัครใช้งาน)
* **คำหลัก (Head Keywords)**: `ระบบ POS ออนไลน์`, `ระบบ POS ร้านอาหาร`, `โปรแกรมขายหน้าร้านฟรี`, `ระบบร้านของชำ`, `โปรแกรมมินิมาร์ท`
* **ฟีเจอร์เฉพาะทาง (High-Intent Long-tail)**: `สั่งอาหารผ่าน QR Code`, `ระบบสแกนบาร์โค้ดสินค้า`, `ตัดสต๊อกสินค้าออนไลน์`, `จอห้องครัว KDS`, `รับเงินพร้อมเพย์ POS`, `OrderPOS`

### 3.2 คีย์เวิร์ดสำหรับหน้าร้านอาหาร & คาเฟ่ (`restaurant`)
* **Brand + Local**: `[ชื่อร้าน]`, `เมนู[ชื่อร้าน]`, `ร้านอาหาร[ชื่อร้าน]`, ชื่อหมวดหมู่/เมนูเด่นของร้าน
* **Action Intent**: `สั่งอาหารออนไลน์`, `เมนูอาหารแนะนำ`, `สแกน QR สั่งอาหาร`, `ร้านอาหารใกล้ฉัน`, `สั่งอาหารไม่ต่อคิว`, `จองโต๊ะล่วงหน้า`, `คาเฟ่และเครื่องดื่ม`, `รับเงินพร้อมเพย์`

### 3.3 คีย์เวิร์ดสำหรับร้านของชำ / มินิมาร์ท (`retail`)
* **Brand + Local**: `[ชื่อร้าน]`, `สินค้า[ชื่อร้าน]`, `ร้านของชำ[ชื่อร้าน]`, ชื่อหมวดหมู่/สินค้ายอดนิยม
* **Buyer Intent**: `ร้านของชำออนไลน์`, `มินิมาร์ทใกล้ฉัน`, `ของใช้ประจำวันราคาประหยัด`, `สินค้าอุปโภคบริโภคครบครัน`, `ซื้อของชำใกล้บ้าน`, `สั่งซื้อสินค้าออนไลน์`, `ร้านค้าปลีกคุณภาพ`

### 3.4 คีย์เวิร์ดเสริมเฉพาะกลุ่มธุรกิจ (Quick Presets ใน `admin/shop_settings.php`)
* **อาหารตามสั่ง**: `อาหารตามสั่ง`, `กะเพราอร่อย`, `กับข้าวตามสั่ง`, `ส่งอาหารใกล้ฉัน`, `อาหารจานเดียว`
* **คาเฟ่ & เครื่องดื่ม**: `คาเฟ่ใกล้ฉัน`, `กาแฟสด`, `ชาเขียว มัทฉะ`, `เครื่องดื่มเย็น`, `เบเกอรี่โฮมเมด`
* **ร้านของชำ / มินิมาร์ท**: `ร้านของชำใกล้ฉัน`, `มินิมาร์ท`, `เครื่องดื่มแช่เย็น`, `ของใช้ในบ้านราคาถูก`, `ซื้อของชำจ่ายพร้อมเพย์`
* **อีสาน / ชาบู / ปิ้งย่าง**: `ร้านชาบู`, `หมูกระทะ ปิ้งย่าง`, `ร้านส้มตำ ยำแซ่บ`, `บุฟเฟ่ต์และอะลาคาร์ต`, `สแกนสั่งผ่านโต๊ะ`

---

## 4. สรุปการตรวจสอบหน้าจัดการข้อมูล & การทำความสะอาดโค้ดเบส

1. **`admin/shop_settings.php`**:
   * แก้ไขแท็ก `</div>` เกินที่ทำให้โครงสร้าง `.card-body` ผิดรูป
   * เปลี่ยนบันทึกเป็น Post-Redirect-Get (`?saved=1`) ก่อนโหลด `admin/header.php` เพื่อให้อัปเดตชื่อร้านและโหมดร้านอาหาร/ร้านของชำบนแถบเมนูทันที
   * เพิ่มเครื่องมือ **1-Click เติมชุด SEO แนะนำอัตโนมัติ** พร้อมตัวนับความยาวแนะนำ (`Title <= 60`, `Description <= 160`, `Keywords <= 250`) และ Live Google Search Preview ที่ปรับตาม `shop_type`
2. **`admin/tables_manage.php` & `api/tables.php`**:
   * เพิ่มปุ่ม **แก้ไขโต๊ะ (`editTable`)**, ช่องเลือกสถานะโต๊ะ, และรองรับ `PUT` ทั้งรหัสโต๊ะ ชื่อโต๊ะ จำนวนที่นั่ง และสถานะ พร้อมส่ง HTTP `400`/`500` JSON เมื่อเกิดข้อผิดพลาด
3. **`admin/menu_manage.php`, `api/menu_items.php` & `api/categories.php`**:
   * เพิ่ม checkbox `#menuAvailable` (พร้อมขาย / หยุดขาย) ใน Modal เพิ่ม/แก้ไข, รองรับ `is_available` ใน `POST`, ป้องกัน XSS ด้วย `escHtml()` และตรวจ validation ใน API
4. **`admin/index.php` (`/admin/`)**:
   * เปลี่ยนให้ `/admin/` เป็นทางเข้าสู่ระบบ OrderPOS โดยตรง (พาไป `admin/dashboard.php` เมื่อล็อกอินแล้ว หรือ `../login.php`) แทนไฟล์ CMS ของเว็บเดิม
5. **`menu.php`, `login.php`, `register.php`, `billing.php`**:
   * อัปเกรด Copywriting และ UI ให้สื่อสารทั้ง 2 โหมด (`restaurant` และ `retail`), เติม `$_SESSION['shop_type']` ใน `login.php`, และ escape พารามิเตอร์ใน `billing.php`
6. **ตัดไฟล์ระบบเก่าออกทั้งหมด**:
   * ลบโฟลเดอร์และไฟล์ที่ไม่เกี่ยวกับ OrderPOS (`_astro/`, `blog/`, `images/`, `webyu/`, `admin/blog_cms.php`, `admin/db_config.php`, `api/blogs.php`, `api/products.php`, `sitemap-0.xml`, `sitemap-index.xml`) และลบ route บล็อกออกจาก `.htaccess` และ `sitemap.php` แล้ว
