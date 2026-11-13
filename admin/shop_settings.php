<?php
require_once 'header.php';

$shopId = intval($_SESSION['shop_id'] ?? 1);
$successMsg = '';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shop_name = trim($_POST['shop_name'] ?? '');
    $shop_type = ($_POST['shop_type'] ?? 'restaurant') === 'retail' ? 'retail' : 'restaurant';
    $raw_slug = trim($_POST['shop_slug'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $promptpay_number = trim($_POST['promptpay_number'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $logo_image = trim($_POST['logo_image'] ?? '');
    $seo_title = trim($_POST['seo_title'] ?? '');
    $seo_description = trim($_POST['seo_description'] ?? '');
    $seo_keywords = trim($_POST['seo_keywords'] ?? '');

    if ($shop_name === '') {
        $errorMsg = 'กรุณาระบุชื่อร้านค้า';
    } else {
        $slugSource = $raw_slug !== '' ? $raw_slug : $shop_name;
        $uniqueSlug = createUniqueShopSlug($slugSource, $shopId);

        $escName = escape($shop_name);
        $escSlug = escape($uniqueSlug);
        $escType = escape($shop_type);
        $escDesc = escape($description);
        $escPhone = escape($phone);
        $escPromptPay = escape($promptpay_number);
        $escAddress = escape($address);
        $escLogo = escape($logo_image);
        $escSeoTitle = escape($seo_title);
        $escSeoDesc = escape($seo_description);
        $escSeoKeywords = escape($seo_keywords);

        $updated = query("UPDATE shops SET
            shop_name = '$escName',
            shop_slug = '$escSlug',
            shop_type = '$escType',
            description = '$escDesc',
            phone = '$escPhone',
            promptpay_number = '$escPromptPay',
            address = '$escAddress',
            logo_image = '$escLogo',
            seo_title = '$escSeoTitle',
            seo_description = '$escSeoDesc',
            seo_keywords = '$escSeoKeywords'
            WHERE id = $shopId");

        if ($updated) {
            if ($shop_type === 'retail') {
                query("INSERT IGNORE INTO tables (shop_id, table_code, table_name, capacity, status)
                       VALUES ($shopId, 'WALKIN', 'ขายหน้าร้าน (Walk-in)', 1, 'available')");
            }
            $_SESSION['shop_name'] = $shop_name;
            $_SESSION['shop_slug'] = $uniqueSlug;
            $_SESSION['shop_type'] = $shop_type;
            $currentShop = getCurrentShop($shopId);
            $successMsg = 'บันทึกข้อมูลร้านค้าและการตั้งค่า Dynamic SEO เรียบร้อยแล้ว';
        } else {
            $errorMsg = 'ไม่สามารถบันทึกข้อมูลได้ กรุณาลองใหม่อีกครั้ง';
        }
    }
}

$shop = getCurrentShop($shopId);
$baseUrl = getBaseSiteUrl();
$publicShopUrl = $baseUrl . '/shop/' . rawurlencode($shop['shop_slug']);
?>

<?php if ($successMsg): ?>
<div style="background:#ECFDF5;border:1px solid #A7F3D0;color:#065F46;padding:14px 18px;border-radius:12px;margin-bottom:20px;display:flex;align-items:center;gap:10px;font-weight:500;">
    <i class="fas fa-check-circle"></i>
    <span><?= htmlspecialchars($successMsg) ?></span>
</div>
<?php endif; ?>

<?php if ($errorMsg): ?>
<div style="background:#FEF2F2;border:1px solid #FECACA;color:#991B1B;padding:14px 18px;border-radius:12px;margin-bottom:20px;display:flex;align-items:center;gap:10px;font-weight:500;">
    <i class="fas fa-exclamation-circle"></i>
    <span><?= htmlspecialchars($errorMsg) ?></span>
</div>
<?php endif; ?>

<form method="POST">
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
            <h3><i class="fas fa-store"></i> ตั้งค่าข้อมูลร้านค้า (<?= htmlspecialchars($shop['shop_name']) ?>)</h3>
            <a href="../menu.php?shop=<?= urlencode($shop['shop_slug']) ?>" target="_blank" class="btn btn-sm btn-outline">
                <i class="fas fa-external-link-alt"></i> เปิดดูหน้าร้านจริง
            </a>
        </div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group">
                    <label>ชื่อร้านค้า *</label>
                    <input type="text" name="shop_name" id="inpShopName" required value="<?= htmlspecialchars($shop['shop_name'] ?? '') ?>" oninput="updateSeoPreview()">
                </div>
                <div class="form-group">
                    <label>ประเภทธุรกิจ / โหมดการทำงานของร้าน</label>
                    <select name="shop_type" id="inpShopType" onchange="updateSeoPreview()">
                        <option value="restaurant" <?= (($shop['shop_type'] ?? 'restaurant') === 'restaurant') ? 'selected' : '' ?>>🍽️ ร้านอาหาร / คาเฟ่ (มีระบบโต๊ะ + จอห้องครัว + คิดเงิน)</option>
                        <option value="retail" <?= (($shop['shop_type'] ?? '') === 'retail') ? 'selected' : '' ?>>🛒 ร้านของชำ / มินิมาร์ท / ค้าปลีก (คิดเงินด่วนหน้า POS + บาร์โค้ด + ตัดสต๊อก)</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>ลิงก์ร้านค้า (Shop Slug URL)</label>
                    <input type="text" name="shop_slug" id="inpShopSlug" value="<?= htmlspecialchars($shop['shop_slug'] ?? '') ?>" oninput="updateSeoPreview()">
                    <small style="color:var(--gray);display:block;margin-top:4px;">
                        URL หน้าร้านของคุณ: <strong id="previewUrlText"><?= htmlspecialchars($publicShopUrl) ?></strong>
                    </small>
                </div>
            </div>

            <div class="form-group">
                <label>คำอธิบายร้านอาหาร (แสดงที่หน้าร้านและใช้ทำ SEO อัตโนมัติ)</label>
                <textarea name="description" id="inpDescription" rows="2" placeholder="บอกจุดเด่นของร้าน เช่น อาหารตามสั่งรสจัดจ้าน วัตถุดิบสดใหม่ทุกวัน" oninput="updateSeoPreview()"><?= htmlspecialchars($shop['description'] ?? '') ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>เบอร์โทรศัพท์ติดต่อร้าน</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($shop['phone'] ?? '') ?>" placeholder="เช่น 081-234-5678">
                </div>
                <div class="form-group">
                    <label>เบอร์ / เลขพร้อมเพย์รับเงิน (PromptPay)</label>
                    <input type="text" name="promptpay_number" value="<?= htmlspecialchars($shop['promptpay_number'] ?? '') ?>" placeholder="เช่น 0812345678">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>ที่อยู่ร้านอาหาร (ใช้ใน Local Restaurant Schema.org)</label>
                    <input type="text" name="address" value="<?= htmlspecialchars($shop['address'] ?? '') ?>" placeholder="เลขที่ ถนน แขวง/ตำบล เขต/อำเภอ จังหวัด">
                </div>
                <div class="form-group">
                    <label>ลิงก์รูปโลโก้ / รูปปกร้าน (URL สำหรับแสดงตอนแชร์ลิงก์)</label>
                    <input type="text" name="logo_image" value="<?= htmlspecialchars($shop['logo_image'] ?? '') ?>" placeholder="https://example.com/logo.jpg หรือเว้นว่างไว้">
                </div>
            </div>
        </div>
    </div>

    <!-- Dynamic SEO Configuration Card -->
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
            <h3><i class="fas fa-magnifying-glass-chart"></i> ตั้งค่า Dynamic SEO & Google Search ประจำร้าน</h3>
            <span class="badge badge-ready">ทำงานอัตโนมัติ + ปรับแต่งเองได้</span>
        </div>
        <div class="card-body">
            <p style="color:var(--gray);font-size:13.5px;margin-bottom:16px;">
                หากเว้นว่างไว้ ระบบจะสร้าง Title, Meta Description และ Schema.org (<code>Restaurant</code> + <code>Menu</code>) จากชื่อร้านและรายการเมนูอาหารของคุณโดยอัตโนมัติ
            </p>

            <!-- Google Search Snippet Live Preview -->
            <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:12px;padding:18px 20px;margin-bottom:20px;">
                <div style="font-size:12px;color:#64748B;margin-bottom:6px;font-weight:600;">
                    <i class="fab fa-google" style="color:#4285F4;"></i> ตัวอย่างการแสดงผลบน Google Search (Live Preview)
                </div>
                <div id="serpUrl" style="font-size:13px;color:#0F172A;margin-bottom:2px;"><?= htmlspecialchars($publicShopUrl) ?></div>
                <div id="serpTitle" style="font-size:18px;color:#1a0dab;font-weight:600;margin-bottom:4px;line-height:1.3;">
                    <?= htmlspecialchars(!empty($shop['seo_title']) ? $shop['seo_title'] : ($shop['shop_name'] . ' - สั่งอาหารออนไลน์ & เมนูแนะนำ | OrderPOS')) ?>
                </div>
                <div id="serpDesc" style="font-size:13.5px;color:#4d5156;line-height:1.5;">
                    <?= htmlspecialchars(!empty($shop['seo_description']) ? $shop['seo_description'] : (!empty($shop['description']) ? $shop['description'] : ('สั่งอาหารออนไลน์ร้าน ' . $shop['shop_name'] . ' ผ่านระบบ QR Code สะดวก รวดเร็ว'))) ?>
                </div>
            </div>

            <div class="form-group">
                <label>SEO Title (หัวข้อที่แสดงบน Google และเวลาแชร์ลิงก์)</label>
                <input type="text" name="seo_title" id="inpSeoTitle" value="<?= htmlspecialchars($shop['seo_title'] ?? '') ?>" placeholder="เว้นว่างไว้เพื่อให้ระบบตั้งชื่อตามชื่อร้านอัตโนมัติ" oninput="updateSeoPreview()">
            </div>

            <div class="form-group">
                <label>SEO Meta Description (คำโปรยร้านบนผลการค้นหา Google)</label>
                <textarea name="seo_description" id="inpSeoDesc" rows="2" placeholder="เว้นว่างไว้เพื่อให้ระบบดึงคำอธิบายร้านและเมนูแนะนำมาสร้างให้อัตโนมัติ" oninput="updateSeoPreview()"><?= htmlspecialchars($shop['seo_description'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label>SEO Keywords (คีย์เวิร์ด คั่นด้วยเครื่องหมายจุลภาค ,)</label>
                <input type="text" name="seo_keywords" value="<?= htmlspecialchars($shop['seo_keywords'] ?? '') ?>" placeholder="เช่น <?= htmlspecialchars($shop['shop_name']) ?>, ร้านอาหารตามสั่ง, สั่งอาหารออนไลน์">
            </div>

            <div style="margin-top:20px;display:flex;justify-content:flex-end;">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save"></i> บันทึกการตั้งค่าร้านค้า & SEO
                </button>
            </div>
        </div>
    </div>
</form>

<script>
const baseSiteUrl = <?= json_encode($baseUrl) ?>;
function updateSeoPreview() {
    const shopName = document.getElementById('inpShopName').value.trim() || 'ชื่อร้านอาหาร';
    const rawSlug = document.getElementById('inpShopSlug').value.trim() || shopName;
    const cleanSlug = rawSlug.toLowerCase().replace(/[^a-z0-9ก-๙\-]+/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '') || 'shop';
    const desc = document.getElementById('inpDescription').value.trim();
    const seoTitle = document.getElementById('inpSeoTitle').value.trim();
    const seoDesc = document.getElementById('inpSeoDesc').value.trim();

    const fullUrl = baseSiteUrl + '/shop/' + cleanSlug;
    document.getElementById('previewUrlText').textContent = fullUrl;
    document.getElementById('serpUrl').textContent = fullUrl;
    document.getElementById('serpTitle').textContent = seoTitle || (shopName + ' - สั่งอาหารออนไลน์ & เมนูแนะนำ | OrderPOS');
    document.getElementById('serpDesc').textContent = seoDesc || desc || ('สั่งอาหารออนไลน์ร้าน ' + shopName + ' ผ่านระบบ QR Code สะดวก รวดเร็ว');
}
</script>

<?php require_once 'footer.php'; ?>
