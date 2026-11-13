<?php
require_once 'config/db.php';
require_once 'config/seo.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shop_name = trim($_POST['shop_name'] ?? '');
    $shop_type = ($_POST['shop_type'] ?? 'restaurant') === 'retail' ? 'retail' : 'restaurant';
    $raw_slug = trim($_POST['shop_slug'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($shop_name === '' || $full_name === '' || $username === '' || $password === '') {
        $error = 'กรุณากรอกข้อมูลที่มีเครื่องหมาย * ให้ครบถ้วน';
    } elseif (strlen($username) < 3 || !preg_match('/^[a-zA-Z0-9_.\-]+$/', $username)) {
        $error = 'ชื่อผู้ใช้ (Username) ต้องเป็นภาษาอังกฤษหรือตัวเลขอย่างน้อย 3 ตัวอักษร';
    } elseif (strlen($password) < 6) {
        $error = 'รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร';
    } elseif ($password !== $confirm_password) {
        $error = 'รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน';
    } else {
        $escUsername = escape($username);
        $existingUser = fetchOne(query("SELECT id FROM users WHERE username = '$escUsername' LIMIT 1"));

        if ($existingUser) {
            $error = 'ชื่อผู้ใช้นี้ถูกใช้งานแล้ว กรุณาตั้งชื่อผู้ใช้อื่น';
        } else {
            // สร้าง shop_slug ที่ไม่ซ้ำ
            $slugSource = $raw_slug !== '' ? $raw_slug : $shop_name;
            $shopSlug = createUniqueShopSlug($slugSource);

            $escShopName = escape($shop_name);
            $escShopSlug = escape($shopSlug);
            $escShopType = escape($shop_type);
            $escPhone = escape($phone);
            $escFullName = escape($full_name);

            if ($shop_type === 'retail') {
                $defaultDesc = escape("เลือกซื้อสินค้าร้าน {$shop_name} สะดวก รวดเร็ว ครบครัน");
                $defaultSeoTitle = escape("{$shop_name} - รายการสินค้า & สั่งซื้อออนไลน์ | OrderPOS");
            } else {
                $defaultDesc = escape("สั่งอาหารออนไลน์ร้าน {$shop_name} สะดวก รวดเร็ว ผ่านระบบ QR Code");
                $defaultSeoTitle = escape("{$shop_name} - สั่งอาหารออนไลน์ & เมนูแนะนำ | OrderPOS");
            }

            $insertShop = query("INSERT INTO shops (shop_name, shop_slug, shop_type, description, phone, promptpay_number, seo_title, seo_description, is_active)
                                 VALUES ('$escShopName', '$escShopSlug', '$escShopType', '$defaultDesc', '$escPhone', '$escPhone', '$defaultSeoTitle', '$defaultDesc', 1)");

            if ($insertShop) {
                global $conn;
                $newShopId = intval($conn->insert_id);
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $escHash = escape($hashedPassword);

                $insertUser = query("INSERT INTO users (shop_id, username, password, full_name, role)
                                     VALUES ($newShopId, '$escUsername', '$escHash', '$escFullName', 'admin')");

                if ($insertUser) {
                    $newUserId = intval($conn->insert_id);

                    // สร้างหมวดหมู่ เมนู/สินค้าตัวอย่าง และจุดขายเริ่มต้นตามประเภทร้านอัตโนมัติ
                    seedNewShopDefaults($newShopId, $shop_type);

                    // ล็อกอินเข้าสู่ระบบทันที
                    $_SESSION['user_id'] = $newUserId;
                    $_SESSION['shop_id'] = $newShopId;
                    $_SESSION['shop_name'] = $shop_name;
                    $_SESSION['shop_slug'] = $shopSlug;
                    $_SESSION['shop_type'] = $shop_type;
                    $_SESSION['username'] = $username;
                    $_SESSION['full_name'] = $full_name;
                    $_SESSION['role'] = 'admin';

                    header('Location: admin/dashboard.php?welcome=1');
                    exit;
                } else {
                    $error = 'เกิดข้อผิดพลาดในการสร้างบัญชีผู้ใช้ กรุณาลองใหม่อีกครั้ง';
                }
            } else {
                $error = 'เกิดข้อผิดพลาดในการสร้างร้านค้า กรุณาลองใหม่อีกครั้ง';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
<?= renderDynamicSeoTags([
    'type' => 'platform',
    'title' => 'สมัครสมาชิกเปิดร้านอาหารฟรี - OrderPOS ระบบจัดการร้านอาหารออนไลน์',
    'description' => 'สมัครสมาชิกเพื่อใช้งาน OrderPOS ฟรี! ตั้งชื่อร้านของคุณเอง สร้าง QR Code สั่งอาหารประจำโต๊ะ ระบบรับออเดอร์หน้าร้าน และจอห้องครัวแบบเรียลไทม์',
    'canonical' => getBaseSiteUrl() . '/register.php'
]) ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=9">
    <style>
        :root {
            --brand-orange: #FF6B35;
            --brand-orange-dark: #E8531E;
            --brand-navy: #0F172A;
        }
        body.modern-register-body {
            font-family: 'Prompt', sans-serif;
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at 15% 20%, #1e293b 0%, #0f172a 55%, #090d16 100%);
            position: relative;
            overflow-x: hidden;
            padding: 28px 18px;
        }
        .bg-orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(90px);
            z-index: 0;
            pointer-events: none;
            opacity: 0.45;
        }
        .bg-orb-1 {
            width: 420px;
            height: 420px;
            background: linear-gradient(135deg, #FF6B35, #F59E0B);
            top: -120px;
            right: -80px;
        }
        .bg-orb-2 {
            width: 380px;
            height: 380px;
            background: linear-gradient(135deg, #004E89, #38BDF8);
            bottom: -120px;
            left: -80px;
        }
        .register-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 1060px;
            display: grid;
            grid-template-columns: 0.95fr 1.25fr;
            background: rgba(255, 255, 255, 0.98);
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.55), 0 0 0 1px rgba(255, 255, 255, 0.12);
        }
        .register-showcase {
            background: linear-gradient(145deg, #1E293B 0%, #0F172A 60%, #1E1B4B 100%);
            color: #ffffff;
            padding: 44px 38px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .showcase-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(74, 222, 128, 0.16);
            border: 1px solid rgba(74, 222, 128, 0.35);
            color: #4ADE80;
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 500;
            width: fit-content;
            margin-bottom: 20px;
        }
        .showcase-brand {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
        }
        .showcase-brand-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            background: linear-gradient(135deg, #FF6B35 0%, #F59E0B 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            color: #fff;
            box-shadow: 0 12px 24px -6px rgba(255, 107, 53, 0.5);
        }
        .showcase-brand h1 {
            font-size: 26px;
            font-weight: 700;
            margin: 0;
        }
        .showcase-brand span {
            display: block;
            font-size: 13px;
            color: #94A3B8;
        }
        .step-list {
            display: grid;
            gap: 14px;
            margin: 24px 0;
        }
        .step-card {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 14px 16px;
            border-radius: 14px;
        }
        .step-num {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            background: linear-gradient(135deg, #FF6B35, #F59E0B);
            color: #fff;
            font-weight: 700;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .step-card h4 {
            font-size: 14px;
            margin: 0 0 3px 0;
            color: #F8FAFC;
        }
        .step-card p {
            font-size: 12.5px;
            color: #94A3B8;
            margin: 0;
            line-height: 1.5;
        }
        .register-panel {
            padding: 40px 42px;
            background: #ffffff;
        }
        .panel-header {
            margin-bottom: 22px;
        }
        .panel-header h2 {
            font-size: 24px;
            font-weight: 700;
            color: #0F172A;
            margin: 0 0 4px 0;
        }
        .panel-header p {
            color: #64748B;
            font-size: 13.5px;
            margin: 0;
        }
        .alert-error {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #FEF2F2;
            border: 1px solid #FECACA;
            color: #DC2626;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 18px;
            font-size: 13.5px;
            font-weight: 500;
        }
        .form-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }
        .modern-form-group {
            margin-bottom: 16px;
        }
        .modern-form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
        }
        .modern-form-group label .req {
            color: #EF4444;
        }
        .input-icon-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }
        .input-icon-wrap .prefix-icon {
            position: absolute;
            left: 14px;
            color: #94A3B8;
            font-size: 14px;
            pointer-events: none;
        }
        .input-icon-wrap input {
            width: 100%;
            padding: 11.5px 14px 11.5px 40px;
            border: 1.5px solid #E2E8F0;
            border-radius: 11px;
            font-size: 14px;
            font-family: inherit;
            color: #0F172A;
            background: #F8FAFC;
            transition: all 0.2s;
        }
        .input-icon-wrap input:focus {
            outline: none;
            background: #ffffff;
            border-color: var(--brand-orange);
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.14);
        }
        .slug-preview {
            font-size: 12px;
            color: #64748B;
            margin-top: 4px;
        }
        .slug-preview code {
            color: #EA580C;
            background: #FFF7ED;
            padding: 2px 6px;
            border-radius: 4px;
        }
        .btn-register-submit {
            width: 100%;
            padding: 13.5px 24px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, #FF6B35 0%, #F97316 100%);
            color: #ffffff;
            font-size: 15.5px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 10px 22px -5px rgba(255, 107, 53, 0.45);
            transition: all 0.22s;
            margin-top: 6px;
        }
        .btn-register-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 26px -5px rgba(255, 107, 53, 0.58);
        }
        .bottom-login-link {
            text-align: center;
            margin-top: 18px;
            padding-top: 16px;
            border-top: 1px solid #E2E8F0;
            font-size: 13.5px;
            color: #475569;
        }
        .bottom-login-link a {
            color: var(--brand-orange);
            font-weight: 600;
            text-decoration: none;
        }
        .bottom-login-link a:hover {
            text-decoration: underline;
        }
        @media (max-width: 860px) {
            .register-wrapper {
                grid-template-columns: 1fr;
                max-width: 520px;
            }
            .form-grid-2 {
                grid-template-columns: 1fr;
                gap: 0;
            }
        }
    </style>
</head>
<body class="modern-register-body">
    <div class="bg-orb bg-orb-1"></div>
    <div class="bg-orb bg-orb-2"></div>

    <div class="register-wrapper">
        <!-- Left Onboarding Info -->
        <div class="register-showcase">
            <div>
                <div class="showcase-badge">
                    <i class="fas fa-rocket"></i> เปิดร้านออนไลน์ฟรีทันที
                </div>
                <div class="showcase-brand">
                    <div class="showcase-brand-icon">
                        <i class="fas fa-store"></i>
                    </div>
                    <div>
                        <h1>OrderPOS SaaS</h1>
                        <span>แพลตฟอร์มระบบ POS สำหรับทุกร้านอาหาร</span>
                    </div>
                </div>
                <p style="color:#CBD5E1;font-size:14px;line-height:1.6;">
                    เริ่มต้นใช้งานระบบจัดการร้านอาหารของคุณเองใน 1 นาที เพียงตั้งชื่อร้าน ระบบจะเตรียมเมนูตัวอย่าง โต๊ะอาหาร และ QR Code พร้อมติด SEO ให้ร้านของคุณโดยอัตโนมัติ
                </p>

                <div class="step-list">
                    <div class="step-card">
                        <div class="step-num">1</div>
                        <div>
                            <h4>ตั้งชื่อร้านอาหารของคุณ</h4>
                            <p>ระบุชื่อร้านและลิงก์หน้าร้าน (URL) เฉพาะของร้านคุณ พร้อมระบบ Dynamic SEO ติด Google ง่ายขึ้น</p>
                        </div>
                    </div>
                    <div class="step-card">
                        <div class="step-num">2</div>
                        <div>
                            <h4>รับระบบหน้าร้าน POS + จอครัวทันที</h4>
                            <p>ระบบสร้างหมวดหมู่และโต๊ะเริ่มต้นให้อัตโนมัติ ปรับแต่งเมนูและราคาของร้านได้เอง 100%</p>
                        </div>
                    </div>
                    <div class="step-card">
                        <div class="step-num">3</div>
                        <div>
                            <h4>พิมพ์ QR Code แปะโต๊ะรับออเดอร์</h4>
                            <p>ลูกค้าสแกนสั่งอาหารจากมือถือเข้าห้องครัวของร้านคุณโดยตรง ข้อมูลแยกอิสระปลอดภัย</p>
                        </div>
                    </div>
                </div>
            </div>

            <div style="font-size:12.5px;color:#94A3B8;border-top:1px solid rgba(255,255,255,0.1);padding-top:16px;display:flex;justify-content:space-between;">
                <span><i class="fas fa-shield-halved" style="color:#4ADE80;"></i> ข้อมูลแยกร้านปลอดภัย 100%</span>
                <span>OrderPOS Cloud</span>
            </div>
        </div>

        <!-- Right Registration Form -->
        <div class="register-panel">
            <div class="panel-header">
                <h2>สมัครสมาชิกเปิดร้านใหม่ 🏪</h2>
                <p>กรอกข้อมูลด้านล่างเพื่อสร้างระบบจัดการร้านอาหารของคุณ</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert-error">
                    <i class="fas fa-circle-exclamation"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" id="registerForm">
                <div class="modern-form-group">
                    <label>เลือกรูปแบบธุรกิจของคุณ <span class="req">*</span></label>
                    <div class="form-grid-2" style="gap:12px;">
                        <label style="display:flex;align-items:flex-start;gap:10px;padding:12px 14px;border:2px solid #E2E8F0;border-radius:12px;cursor:pointer;background:#F8FAFC;margin:0;" id="cardTypeRestaurant">
                            <input type="radio" name="shop_type" value="restaurant" <?= (($_POST['shop_type'] ?? 'restaurant') !== 'retail') ? 'checked' : '' ?> onchange="highlightShopType()" style="margin-top:3px;">
                            <div>
                                <div style="font-weight:700;color:#0F172A;font-size:14px;"><i class="fas fa-utensils" style="color:#FF6B35;"></i> ร้านอาหาร / คาเฟ่</div>
                                <div style="font-size:12px;color:#64748B;font-weight:400;">มีระบบโต๊ะ, จอห้องครัว, สแกน QR สั่งอาหาร</div>
                            </div>
                        </label>
                        <label style="display:flex;align-items:flex-start;gap:10px;padding:12px 14px;border:2px solid #E2E8F0;border-radius:12px;cursor:pointer;background:#F8FAFC;margin:0;" id="cardTypeRetail">
                            <input type="radio" name="shop_type" value="retail" <?= (($_POST['shop_type'] ?? '') === 'retail') ? 'checked' : '' ?> onchange="highlightShopType()" style="margin-top:3px;">
                            <div>
                                <div style="font-weight:700;color:#0F172A;font-size:14px;"><i class="fas fa-basket-shopping" style="color:#FF6B35;"></i> ร้านของชำ / ร้านค้าปลีก</div>
                                <div style="font-size:12px;color:#64748B;font-weight:400;">ขายหน้าร้านคิดเงินด่วน, ค้นหาบาร์โค้ด, ตัดสต๊อก</div>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="modern-form-group">
                    <label for="shop_name">ชื่อร้านค้าของคุณ <span class="req">*</span></label>
                    <div class="input-icon-wrap">
                        <i class="fas fa-store prefix-icon"></i>
                        <input
                            type="text"
                            id="shop_name"
                            name="shop_name"
                            required
                            placeholder="เช่น ร้านค้าเจริญสุข, กะเพราอินเตอร์, มินิมาร์ทหน้าหมู่บ้าน"
                            value="<?= htmlspecialchars($_POST['shop_name'] ?? '') ?>"
                            oninput="updateSlugPreview(this.value)"
                        >
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="modern-form-group">
                        <label for="shop_slug">ชื่อลิงก์ร้าน (URL Slug - ไม่บังคับ)</label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-link prefix-icon"></i>
                            <input
                                type="text"
                                id="shop_slug"
                                name="shop_slug"
                                placeholder="เช่น krapow-inter"
                                value="<?= htmlspecialchars($_POST['shop_slug'] ?? '') ?>"
                                oninput="updateSlugPreview(this.value)"
                            >
                        </div>
                        <div class="slug-preview">ลิงก์สั่งอาหาร: <code id="slugPreviewText">/shop/your-shop</code></div>
                    </div>

                    <div class="modern-form-group">
                        <label for="phone">เบอร์โทรศัพท์ / พร้อมเพย์ร้าน</label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-phone prefix-icon"></i>
                            <input
                                type="text"
                                id="phone"
                                name="phone"
                                placeholder="เช่น 0812345678"
                                value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                            >
                        </div>
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="modern-form-group">
                        <label for="full_name">ชื่อ-นามสกุล เจ้าของร้าน <span class="req">*</span></label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-id-card prefix-icon"></i>
                            <input
                                type="text"
                                id="full_name"
                                name="full_name"
                                required
                                placeholder="ชื่อผู้ดูแลร้าน"
                                value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
                            >
                        </div>
                    </div>

                    <div class="modern-form-group">
                        <label for="username">ชื่อผู้ใช้เข้าสู่ระบบ (Username) <span class="req">*</span></label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-user prefix-icon"></i>
                            <input
                                type="text"
                                id="username"
                                name="username"
                                required
                                autocomplete="username"
                                placeholder="ภาษาอังกฤษหรือตัวเลข เช่น myshop01"
                                value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                            >
                        </div>
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="modern-form-group">
                        <label for="password">รหัสผ่าน (อย่างน้อย 6 ตัวอักษร) <span class="req">*</span></label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-lock prefix-icon"></i>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                required
                                minlength="6"
                                autocomplete="new-password"
                                placeholder="ตั้งรหัสผ่าน"
                            >
                        </div>
                    </div>

                    <div class="modern-form-group">
                        <label for="confirm_password">ยืนยันรหัสผ่านอีกครั้ง <span class="req">*</span></label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-check-double prefix-icon"></i>
                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                required
                                minlength="6"
                                autocomplete="new-password"
                                placeholder="กรอกรหัสผ่านซ้ำ"
                            >
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-register-submit" id="regSubmitBtn">
                    <i class="fas fa-sparkles"></i>
                    <span>สร้างร้านค้าและเริ่มใช้งานทันที</span>
                </button>
            </form>

            <div class="bottom-login-link">
                มีบัญชีร้านค้าอยู่แล้วใช่ไหม? <a href="login.php"><i class="fas fa-right-to-bracket"></i> เข้าสู่ระบบที่นี่</a>
            </div>
        </div>
    </div>

    <script>
        function highlightShopType() {
            const isRetail = document.querySelector('input[name="shop_type"]:checked')?.value === 'retail';
            const cardRest = document.getElementById('cardTypeRestaurant');
            const cardRetail = document.getElementById('cardTypeRetail');
            if (cardRest && cardRetail) {
                cardRest.style.borderColor = !isRetail ? '#FF6B35' : '#E2E8F0';
                cardRest.style.background = !isRetail ? '#FFF7ED' : '#F8FAFC';
                cardRetail.style.borderColor = isRetail ? '#FF6B35' : '#E2E8F0';
                cardRetail.style.background = isRetail ? '#FFF7ED' : '#F8FAFC';
            }
        }
        highlightShopType();

        function updateSlugPreview(val) {
            const slugInput = document.getElementById('shop_slug');
            const shopInput = document.getElementById('shop_name');
            const raw = (slugInput.value.trim() !== '' ? slugInput.value : shopInput.value).trim().toLowerCase();
            const cleaned = raw.replace(/[^a-z0-9ก-๙\-]+/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '') || 'your-shop';
            document.getElementById('slugPreviewText').textContent = '/shop/' + cleaned;
        }
    </script>
</body>
</html>
