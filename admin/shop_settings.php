<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/seo.php';

$shopId = intval($_SESSION['shop_id'] ?? 1);
$successMsg = isset($_GET['saved']) ? 'บันทึกข้อมูลร้านค้าและการตั้งค่า Dynamic SEO เรียบร้อยแล้ว' : '';
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
    $seo_title = seoTruncateText(trim($_POST['seo_title'] ?? ''), 195, '');
    $seo_description = seoTruncateText(trim($_POST['seo_description'] ?? ''), 295, '');
    $raw_keywords = trim($_POST['seo_keywords'] ?? '');
    $seo_keywords = $raw_keywords === ''
        ? ''
        : seoJoinKeywordsWithinLimit(array_map('trim', explode(',', $raw_keywords)), 250);

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
            header('Location: shop_settings.php?saved=1');
            exit;
        } else {
            $errorMsg = 'ไม่สามารถบันทึกข้อมูลได้ กรุณาลองใหม่อีกครั้ง';
        }
    }
}

require_once 'header.php';

$shop = getCurrentShop($shopId);
$isRetail = (($shop['shop_type'] ?? 'restaurant') === 'retail');
$baseUrl = getBaseSiteUrl();
$publicShopUrl = $baseUrl . '/shop/' . rawurlencode($shop['shop_slug']);

$catRows = fetchAll(query("SELECT name FROM categories WHERE shop_id = $shopId AND is_active = 1 ORDER BY sort_order ASC LIMIT 6"));
$itemRows = fetchAll(query("SELECT name FROM menu_items WHERE shop_id = $shopId AND is_available = 1 ORDER BY is_recommended DESC, sort_order ASC LIMIT 6"));
$shopCategoryNames = array_values(array_filter(array_column($catRows, 'name')));
$shopItemNames = array_values(array_filter(array_column($itemRows, 'name')));
$initialPreset = generateShopSeoPreset($shop['shop_name'] ?? 'ร้านค้า', $shop['shop_type'] ?? 'restaurant', $shopCategoryNames, $shopItemNames);
$keywordSets = getOrderPosKeywordSets();
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
                    <select name="shop_type" id="inpShopType" onchange="updateBusinessModeLabels(); updateSeoPreview();">
                        <option value="restaurant" <?= !$isRetail ? 'selected' : '' ?>>🍽️ ร้านอาหาร / คาเฟ่ (มีระบบโต๊ะ + จอห้องครัว + คิดเงิน)</option>
                        <option value="retail" <?= $isRetail ? 'selected' : '' ?>>🛒 ร้านของชำ / มินิมาร์ท / ค้าปลีก (คิดเงินด่วนหน้า POS + บาร์โค้ด + ตัดสต๊อก)</option>
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

            <div class="form-group">
                <label id="lblDescription"><?= $isRetail ? 'คำอธิบายร้านค้า / จุดเด่นของร้าน (แสดงที่หน้าร้านและใช้ทำ SEO อัตโนมัติ)' : 'คำอธิบายร้านอาหาร / จุดเด่นของร้าน (แสดงที่หน้าร้านและใช้ทำ SEO อัตโนมัติ)' ?></label>
                <textarea name="description" id="inpDescription" rows="2" placeholder="<?= $isRetail ? 'เช่น ของใช้ประจำวัน อาหารแห้ง เครื่องดื่มครบครัน ราคาเป็นกันเอง เปิดบริการทุกวัน' : 'เช่น อาหารตามสั่งรสจัดจ้าน วัตถุดิบสดใหม่ทุกวัน สั่งผ่าน QR ไม่ต้องรอนาน' ?>" oninput="updateSeoPreview()"><?= htmlspecialchars($shop['description'] ?? '') ?></textarea>
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
                    <label id="lblAddress"><?= $isRetail ? 'ที่อยู่ร้านค้า (ใช้ใน LocalBusiness Store Schema.org)' : 'ที่อยู่ร้านอาหาร (ใช้ใน Local Restaurant Schema.org)' ?></label>
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
            <p id="seoSchemaHint" style="color:var(--gray);font-size:13.5px;margin-bottom:16px;">
                <?= $isRetail
                    ? 'หากเว้นว่างไว้ ระบบจะสร้าง Title, Meta Description และ Schema.org (<code>Store / GroceryStore</code> + <code>OfferCatalog</code>) จากชื่อร้านและสินค้าของคุณโดยอัตโนมัติ'
                    : 'หากเว้นว่างไว้ ระบบจะสร้าง Title, Meta Description และ Schema.org (<code>Restaurant</code> + <code>Menu</code>) จากชื่อร้านและรายการเมนูอาหารของคุณโดยอัตโนมัติ' ?>
            </p>

            <!-- 1-Click SEO Content & Keyword Generator -->
            <div style="background:#FFF7ED;border:1px solid #FDBA74;border-radius:12px;padding:16px 18px;margin-bottom:18px;">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:10px;">
                    <div>
                        <strong style="color:#9A3412;font-size:14px;"><i class="fas fa-wand-magic-sparkles"></i> ตัวช่วยคิดคีย์เวิร์ด &amp; เนื้อหา SEO คุณภาพสูง (1-Click)</strong>
                        <div style="font-size:12.5px;color:#7C2D12;">ดึงชื่อร้าน หมวดหมู่ และสินค้า/เมนูจริงของร้านคุณ มาสร้างคำโปรยที่ติดคำค้นหา Google ได้ดีกว่า</div>
                    </div>
                    <button type="button" class="btn btn-sm btn-primary" onclick="applySmartSeoPreset(false)">
                        <i class="fas fa-bolt"></i> เติมชุด SEO แนะนำอัตโนมัติ
                    </button>
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                    <span style="font-size:12px;color:#9A3412;font-weight:600;">เติมคีย์เวิร์ดเฉพาะสายธุรกิจ:</span>
                    <button type="button" class="btn btn-sm btn-outline" style="padding:4px 10px;font-size:12px;background:#fff;" onclick="appendKeywordPreset('อาหารตามสั่ง')">+ ร้านอาหารตามสั่ง</button>
                    <button type="button" class="btn btn-sm btn-outline" style="padding:4px 10px;font-size:12px;background:#fff;" onclick="appendKeywordPreset('คาเฟ่')">+ คาเฟ่ &amp; เครื่องดื่ม</button>
                    <button type="button" class="btn btn-sm btn-outline" style="padding:4px 10px;font-size:12px;background:#fff;" onclick="appendKeywordPreset('ของชำ')">+ ร้านของชำ / มินิมาร์ท</button>
                    <button type="button" class="btn btn-sm btn-outline" style="padding:4px 10px;font-size:12px;background:#fff;" onclick="appendKeywordPreset('ปิ้งย่างชาบู')">+ อีสาน / ชาบู / ปิ้งย่าง</button>
                </div>
            </div>

            <!-- Google Search Snippet Live Preview -->
            <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:12px;padding:18px 20px;margin-bottom:20px;">
                <div style="font-size:12px;color:#64748B;margin-bottom:6px;font-weight:600;">
                    <i class="fab fa-google" style="color:#4285F4;"></i> ตัวอย่างการแสดงผลบน Google Search (Live Preview)
                </div>
                <div id="serpUrl" style="font-size:13px;color:#0F172A;margin-bottom:2px;"><?= htmlspecialchars($publicShopUrl) ?></div>
                <div id="serpTitle" style="font-size:18px;color:#1a0dab;font-weight:600;margin-bottom:4px;line-height:1.3;">
                    <?= htmlspecialchars(!empty($shop['seo_title']) ? $shop['seo_title'] : $initialPreset['seo_title']) ?>
                </div>
                <div id="serpDesc" style="font-size:13.5px;color:#4d5156;line-height:1.5;">
                    <?= htmlspecialchars(!empty($shop['seo_description']) ? $shop['seo_description'] : (!empty($shop['description']) ? $shop['description'] : $initialPreset['seo_description'])) ?>
                </div>
            </div>

            <div class="form-group">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;">
                    <label style="margin-bottom:0;">SEO Title (หัวข้อที่แสดงบน Google และเวลาแชร์ลิงก์)</label>
                    <small id="seoTitleCounter" style="color:#64748B;font-size:12px;">0 / 60 ตัวอักษรแนะนำ</small>
                </div>
                <input type="text" name="seo_title" id="inpSeoTitle" maxlength="195" value="<?= htmlspecialchars($shop['seo_title'] ?? '') ?>" placeholder="เว้นว่างไว้เพื่อให้ระบบตั้งชื่อตามชื่อร้านอัตโนมัติ" oninput="updateSeoPreview()" style="margin-top:6px;">
            </div>

            <div class="form-group">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;">
                    <label style="margin-bottom:0;">SEO Meta Description (คำโปรยร้านบนผลการค้นหา Google)</label>
                    <small id="seoDescCounter" style="color:#64748B;font-size:12px;">0 / 160 ตัวอักษรแนะนำ</small>
                </div>
                <textarea name="seo_description" id="inpSeoDesc" maxlength="295" rows="2" placeholder="เว้นว่างไว้เพื่อให้ระบบดึงคำอธิบายร้านและเมนู/สินค้าแนะนำมาสร้างให้อัตโนมัติ" oninput="updateSeoPreview()" style="margin-top:6px;"><?= htmlspecialchars($shop['seo_description'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label>SEO Keywords (คีย์เวิร์ด คั่นด้วยเครื่องหมายจุลภาค , สูงสุด 250 ตัวอักษร)</label>
                <input type="text" name="seo_keywords" id="inpSeoKeywords" maxlength="250" value="<?= htmlspecialchars($shop['seo_keywords'] ?? '') ?>" placeholder="เช่น <?= htmlspecialchars($shop['shop_name']) ?>, ร้านของชำใกล้ฉัน, สั่งอาหารออนไลน์, QR Menu">
            </div>

            <div style="margin-top:20px;display:flex;justify-content:flex-end;">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save"></i> บันทึกการตั้งค่าร้านค้า &amp; SEO
                </button>
            </div>
        </div>
    </div>
</form>

<script>
const baseSiteUrl = <?= json_encode($baseUrl) ?>;
const shopCategories = <?= json_encode($shopCategoryNames, JSON_UNESCAPED_UNICODE) ?>;
const shopTopItems = <?= json_encode($shopItemNames, JSON_UNESCAPED_UNICODE) ?>;
const defaultKwRestaurant = <?= json_encode($keywordSets['restaurant'], JSON_UNESCAPED_UNICODE) ?>;
const defaultKwRetail = <?= json_encode($keywordSets['retail'], JSON_UNESCAPED_UNICODE) ?>;

const nicheKeywords = {
    'อาหารตามสั่ง': ['อาหารตามสั่ง', 'กะเพราอร่อย', 'กับข้าวตามสั่ง', 'ส่งอาหารใกล้ฉัน', 'อาหารจานเดียว'],
    'คาเฟ่': ['คาเฟ่ใกล้ฉัน', 'กาแฟสด', 'ชาเขียว มัทฉะ', 'เครื่องดื่มเย็น', 'เบเกอรี่โฮมเมด'],
    'ของชำ': ['ร้านของชำใกล้ฉัน', 'มินิมาร์ท', 'เครื่องดื่มแช่เย็น', 'ของใช้ในบ้านราคาถูก', 'ซื้อของชำจ่ายพร้อมเพย์'],
    'ปิ้งย่างชาบู': ['ร้านชาบู', 'หมูกระทะ ปิ้งย่าง', 'ร้านส้มตำ ยำแซ่บ', 'บุฟเฟ่ต์และอะลาคาร์ต', 'สแกนสั่งผ่านโต๊ะ']
};

function joinKeywordsWithinLimit(list, maxLen = 245) {
    let out = '';
    const seen = new Set();
    for (const raw of list) {
        const kw = (raw || '').trim();
        if (!kw || seen.has(kw)) continue;
        seen.add(kw);
        const candidate = out ? (out + ', ' + kw) : kw;
        if (candidate.length > maxLen) break;
        out = candidate;
    }
    return out;
}

function buildShopPreset(shopName, shopType) {
    const cleanName = shopName || 'ร้านค้าของเรา';
    const isRetail = (shopType === 'retail');
    const cats = shopCategories.slice(0, 4);
    const items = shopTopItems.slice(0, 4);
    const itemText = items.length ? items.join(', ') : (isRetail ? 'ของใช้ประจำวัน, เครื่องดื่ม, ขนม' : 'เมนูซิกเนเจอร์, เครื่องดื่ม');

    if (isRetail) {
        return {
            title: cleanName + ' | ร้านของชำ & สั่งซื้อสินค้าออนไลน์',
            storefrontDesc: 'ครบทุกของใช้ประจำวัน เครื่องดื่ม และสินค้าอุปโภคบริโภคที่ ' + cleanName + ' คัดสรรคุณภาพ ราคาเป็นกันเอง คิดเงินไว รองรับพร้อมเพย์',
            seoDesc: ('ช้อปสินค้าอุปโภคบริโภค ของใช้ประจำวัน และเครื่องดื่มที่ ' + cleanName + ' สินค้ายอดนิยม: ' + itemText + ' จ่ายพร้อมเพย์สะดวก').slice(0, 160),
            keywords: joinKeywordsWithinLimit([cleanName, 'สินค้า' + cleanName, 'ร้านของชำ' + cleanName, ...cats, ...items, ...defaultKwRetail.split(',')], 245)
        };
    }

    return {
        title: cleanName + ' | เมนูแนะนำ & สแกนสั่งอาหารออนไลน์',
        storefrontDesc: 'อิ่มอร่อยกับเมนูคัดสรรจากร้าน ' + cleanName + ' ปรุงสดใหม่ทุกจาน สั่งง่ายผ่าน QR Code ส่งตรงเข้าครัวทันที รวดเร็วทันใจ',
        seoDesc: ('ดูเมนูแนะนำและสั่งอาหารออนไลน์ร้าน ' + cleanName + ' เมนูแนะนำ: ' + itemText + ' สแกน QR สั่งได้ทันที ส่งตรงเข้าครัว จ่ายพร้อมเพย์สะดวก').slice(0, 160),
        keywords: joinKeywordsWithinLimit([cleanName, 'เมนู' + cleanName, 'ร้านอาหาร' + cleanName, ...cats, ...items, ...defaultKwRestaurant.split(',')], 245)
    };
}

function updateBusinessModeLabels() {
    const isRetail = document.getElementById('inpShopType').value === 'retail';
    document.getElementById('lblDescription').textContent = isRetail
        ? 'คำอธิบายร้านค้า / จุดเด่นของร้าน (แสดงที่หน้าร้านและใช้ทำ SEO อัตโนมัติ)'
        : 'คำอธิบายร้านอาหาร / จุดเด่นของร้าน (แสดงที่หน้าร้านและใช้ทำ SEO อัตโนมัติ)';
    document.getElementById('inpDescription').placeholder = isRetail
        ? 'เช่น ของใช้ประจำวัน อาหารแห้ง เครื่องดื่มครบครัน ราคาเป็นกันเอง เปิดบริการทุกวัน'
        : 'เช่น อาหารตามสั่งรสจัดจ้าน วัตถุดิบสดใหม่ทุกวัน สั่งผ่าน QR ไม่ต้องรอนาน';
    document.getElementById('lblAddress').textContent = isRetail
        ? 'ที่อยู่ร้านค้า (ใช้ใน LocalBusiness Store Schema.org)'
        : 'ที่อยู่ร้านอาหาร (ใช้ใน Local Restaurant Schema.org)';
    document.getElementById('seoSchemaHint').innerHTML = isRetail
        ? 'หากเว้นว่างไว้ ระบบจะสร้าง Title, Meta Description และ Schema.org (<code>Store / GroceryStore</code> + <code>OfferCatalog</code>) จากชื่อร้านและสินค้าของคุณโดยอัตโนมัติ'
        : 'หากเว้นว่างไว้ ระบบจะสร้าง Title, Meta Description และ Schema.org (<code>Restaurant</code> + <code>Menu</code>) จากชื่อร้านและรายการเมนูอาหารของคุณโดยอัตโนมัติ';
}

function updateCounter(elId, len, minIdeal, maxIdeal) {
    const el = document.getElementById(elId);
    if (!el) return;
    let statusColor = '#64748B';
    if (len > 0 && len <= maxIdeal && len >= minIdeal) {
        statusColor = '#059669';
    } else if (len > maxIdeal) {
        statusColor = '#D97706';
    }
    el.style.color = statusColor;
    el.textContent = `${len} / ${maxIdeal} ตัวอักษร` + (len > maxIdeal ? ' (ยาวเกินแนะนำ)' : (len >= minIdeal ? ' (กำลังพอดี ✓)' : ''));
}

function updateSeoPreview() {
    const shopName = document.getElementById('inpShopName').value.trim() || 'ร้านค้าของเรา';
    const shopType = document.getElementById('inpShopType').value;
    const rawSlug = document.getElementById('inpShopSlug').value.trim() || shopName;
    const cleanSlug = rawSlug.toLowerCase().replace(/[^a-z0-9ก-๙\-]+/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '') || 'shop';
    const desc = document.getElementById('inpDescription').value.trim();
    const seoTitle = document.getElementById('inpSeoTitle').value.trim();
    const seoDesc = document.getElementById('inpSeoDesc').value.trim();

    const preset = buildShopPreset(shopName, shopType);
    const fullUrl = baseSiteUrl + '/shop/' + cleanSlug;
    document.getElementById('previewUrlText').textContent = fullUrl;
    document.getElementById('serpUrl').textContent = fullUrl;

    const previewTitle = seoTitle || preset.title;
    const previewDesc = seoDesc || desc || preset.seoDesc;
    document.getElementById('serpTitle').textContent = previewTitle;
    document.getElementById('serpDesc').textContent = previewDesc;

    updateCounter('seoTitleCounter', seoTitle.length, 30, 60);
    updateCounter('seoDescCounter', seoDesc.length, 90, 160);
}

function applySmartSeoPreset(onlyEmpty) {
    const shopName = document.getElementById('inpShopName').value.trim() || 'ร้านค้าของเรา';
    const shopType = document.getElementById('inpShopType').value;
    const preset = buildShopPreset(shopName, shopType);

    const descEl = document.getElementById('inpDescription');
    const titleEl = document.getElementById('inpSeoTitle');
    const seoDescEl = document.getElementById('inpSeoDesc');
    const kwEl = document.getElementById('inpSeoKeywords');

    if (!onlyEmpty || !descEl.value.trim()) descEl.value = preset.storefrontDesc;
    if (!onlyEmpty || !titleEl.value.trim()) titleEl.value = preset.title;
    if (!onlyEmpty || !seoDescEl.value.trim()) seoDescEl.value = preset.seoDesc;
    if (!onlyEmpty || !kwEl.value.trim()) kwEl.value = preset.keywords;

    updateSeoPreview();
    if (typeof showToast === 'function') {
        showToast('สร้างเนื้อหาและคีย์เวิร์ด SEO คุณภาพสูงเรียบร้อย (อย่าลืมกดบันทึก)', 'success');
    }
}

function appendKeywordPreset(niche) {
    const words = nicheKeywords[niche] || [];
    const kwEl = document.getElementById('inpSeoKeywords');
    const existing = kwEl.value.split(',').map(s => s.trim()).filter(Boolean);
    kwEl.value = joinKeywordsWithinLimit([...existing, ...words], 250);
    if (typeof showToast === 'function') {
        showToast(`เพิ่มชุดคีย์เวิร์ด "${niche}" เรียบร้อย`, 'success');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    updateBusinessModeLabels();
    updateSeoPreview();
});
</script>

<?php require_once 'footer.php'; ?>
