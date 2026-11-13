<?php
session_start();
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = escape($_POST['username']);
    $password = $_POST['password'];
    
    $result = query("SELECT * FROM users WHERE username = '$username'");
    $user = fetchOne($result);
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        header('Location: admin/dashboard.php');
        exit;
    } else {
        $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - OrderPOS ระบบจัดการร้านอาหารยุคใหม่</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=8">
    <style>
        :root {
            --brand-orange: #FF6B35;
            --brand-orange-dark: #E8531E;
            --brand-navy: #0F172A;
            --brand-navy-light: #1E293B;
            --brand-gold: #F59E0B;
        }

        body.modern-login-body {
            font-family: 'Prompt', sans-serif;
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at 15% 20%, #1e293b 0%, #0f172a 55%, #090d16 100%);
            position: relative;
            overflow-x: hidden;
            padding: 24px;
        }

        /* Ambient glowing orbs */
        .bg-orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(90px);
            z-index: 0;
            pointer-events: none;
            opacity: 0.45;
            animation: floatOrb 12s ease-in-out infinite alternate;
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
            animation-delay: -6s;
        }

        @keyframes floatOrb {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(-25px, 25px) scale(1.08); }
        }

        .login-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 980px;
            display: grid;
            grid-template-columns: 1.08fr 1fr;
            background: rgba(255, 255, 255, 0.98);
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.55), 0 0 0 1px rgba(255, 255, 255, 0.12);
            animation: cardAppear 0.55s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes cardAppear {
            from { opacity: 0; transform: translateY(20px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* Left Hero Showcase */
        .login-showcase {
            background: linear-gradient(145deg, #1E293B 0%, #0F172A 60%, #1E1B4B 100%);
            color: #ffffff;
            padding: 48px 42px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        .login-showcase::before {
            content: '';
            position: absolute;
            width: 320px;
            height: 320px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255, 107, 53, 0.28) 0%, transparent 70%);
            top: -60px;
            left: -60px;
            pointer-events: none;
        }

        .showcase-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 107, 53, 0.16);
            border: 1px solid rgba(255, 107, 53, 0.35);
            color: #FF8C61;
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 500;
            width: fit-content;
            margin-bottom: 24px;
        }

        .showcase-brand {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 18px;
        }

        .showcase-brand-icon {
            width: 60px;
            height: 60px;
            border-radius: 18px;
            background: linear-gradient(135deg, #FF6B35 0%, #F59E0B 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: #fff;
            box-shadow: 0 12px 24px -6px rgba(255, 107, 53, 0.5);
        }

        .showcase-brand h1 {
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -0.5px;
            margin: 0;
            line-height: 1.2;
        }

        .showcase-brand span {
            display: block;
            font-size: 13px;
            color: #94A3B8;
            font-weight: 400;
            margin-top: 2px;
        }

        .showcase-desc {
            color: #CBD5E1;
            font-size: 14.5px;
            line-height: 1.65;
            margin-bottom: 30px;
        }

        .feature-list {
            display: grid;
            gap: 14px;
            margin-bottom: 32px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 14px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 13px 16px;
            border-radius: 14px;
            transition: transform 0.25s, background 0.25s;
        }

        .feature-item:hover {
            transform: translateX(4px);
            background: rgba(255, 255, 255, 0.09);
        }

        .feature-icon {
            width: 40px;
            height: 40px;
            border-radius: 11px;
            background: rgba(255, 107, 53, 0.18);
            color: #FF8559;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
            flex-shrink: 0;
        }

        .feature-text h4 {
            font-size: 14px;
            font-weight: 600;
            margin: 0 0 2px 0;
            color: #F8FAFC;
        }

        .feature-text p {
            font-size: 12.5px;
            color: #94A3B8;
            margin: 0;
        }

        .showcase-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 12.5px;
            color: #94A3B8;
        }

        .status-dot {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #4ADE80;
            font-weight: 500;
        }

        .status-dot::before {
            content: '';
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #4ADE80;
            box-shadow: 0 0 10px #4ADE80;
        }

        /* Right Login Form Panel */
        .login-panel {
            padding: 48px 44px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: #ffffff;
        }

        .panel-header {
            margin-bottom: 28px;
        }

        .panel-header h2 {
            font-size: 26px;
            font-weight: 700;
            color: #0F172A;
            margin: 0 0 6px 0;
        }

        .panel-header p {
            color: #64748B;
            font-size: 14px;
            margin: 0;
        }

        .alert-error {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #FEF2F2;
            border: 1px solid #FECACA;
            color: #DC2626;
            padding: 13px 16px;
            border-radius: 12px;
            margin-bottom: 22px;
            font-size: 13.5px;
            font-weight: 500;
            animation: shakeError 0.4s ease-in-out;
        }

        @keyframes shakeError {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-6px); }
            75% { transform: translateX(6px); }
        }

        .modern-form-group {
            margin-bottom: 20px;
        }

        .modern-form-group label {
            display: block;
            font-size: 13.5px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
        }

        .input-icon-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon-wrap .prefix-icon {
            position: absolute;
            left: 16px;
            color: #94A3B8;
            font-size: 15px;
            transition: color 0.2s;
            pointer-events: none;
        }

        .input-icon-wrap input {
            width: 100%;
            padding: 13.5px 44px 13.5px 44px;
            border: 1.5px solid #E2E8F0;
            border-radius: 12px;
            font-size: 15px;
            font-family: inherit;
            color: #0F172A;
            background: #F8FAFC;
            transition: all 0.22s ease;
        }

        .input-icon-wrap input::placeholder {
            color: #94A3B8;
        }

        .input-icon-wrap input:focus {
            outline: none;
            background: #ffffff;
            border-color: var(--brand-orange);
            box-shadow: 0 0 0 4px rgba(255, 107, 53, 0.14);
        }

        .input-icon-wrap:focus-within .prefix-icon {
            color: var(--brand-orange);
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            background: none;
            border: none;
            color: #94A3B8;
            cursor: pointer;
            padding: 6px;
            font-size: 15px;
            transition: color 0.2s;
        }

        .toggle-password:hover {
            color: #475569;
        }

        .btn-login-submit {
            width: 100%;
            padding: 14px 24px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, #FF6B35 0%, #F97316 100%);
            color: #ffffff;
            font-size: 16px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 10px 22px -5px rgba(255, 107, 53, 0.45);
            transition: all 0.25s ease;
            margin-top: 8px;
        }

        .btn-login-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 26px -5px rgba(255, 107, 53, 0.58);
            background: linear-gradient(135deg, #F97316 0%, #EA580C 100%);
        }

        .btn-login-submit:active {
            transform: translateY(0);
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 26px 0 18px;
            color: #94A3B8;
            font-size: 12.5px;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #E2E8F0;
        }

        .quick-links {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .quick-link-card {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 11px 14px;
            border-radius: 11px;
            border: 1.5px solid #E2E8F0;
            background: #F8FAFC;
            color: #334155;
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .quick-link-card i {
            color: var(--brand-orange);
            font-size: 15px;
        }

        .quick-link-card:hover {
            border-color: var(--brand-orange);
            background: #FFF7ED;
            color: #EA580C;
            transform: translateY(-1px);
        }

        @media (max-width: 820px) {
            .login-wrapper {
                grid-template-columns: 1fr;
                max-width: 460px;
            }
            .login-showcase {
                padding: 32px 28px;
            }
            .feature-list {
                display: none;
            }
            .showcase-desc {
                margin-bottom: 12px;
            }
            .login-panel {
                padding: 34px 28px;
            }
        }
    </style>
</head>
<body class="modern-login-body">
    <div class="bg-orb bg-orb-1"></div>
    <div class="bg-orb bg-orb-2"></div>

    <div class="login-wrapper">
        <!-- Left Brand & Feature Showcase -->
        <div class="login-showcase">
            <div>
                <div class="showcase-badge">
                    <i class="fas fa-bolt"></i> Smart Restaurant POS System
                </div>
                <div class="showcase-brand">
                    <div class="showcase-brand-icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <div>
                        <h1>OrderPOS</h1>
                        <span>ระบบบริหารจัดการร้านอาหารครบวงจร</span>
                    </div>
                </div>
                <p class="showcase-desc">
                    ยกระดับการจัดการร้านอาหารของคุณให้รวดเร็ว แม่นยำ และทันสมัย เชื่อมต่อหน้าร้าน ห้องครัว และระบบสั่งอาหารผ่าน QR Code แบบเรียลไทม์
                </p>

                <div class="feature-list">
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-cash-register"></i></div>
                        <div class="feature-text">
                            <h4>ขายหน้าร้าน & คิดเงินไว</h4>
                            <p>รองรับเงินสด โอน และ PromptPay พร้อมสรุปยอดทันที</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-qrcode"></i></div>
                        <div class="feature-text">
                            <h4>สั่งอาหารผ่านมือถือ (QR Table)</h4>
                            <p>ลูกค้าสแกนสั่งอาหารจากโต๊ะ ส่งตรงเข้าครัวอัตโนมัติ</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-kitchen-set"></i></div>
                        <div class="feature-text">
                            <h4>ระบบจอห้องครัว Real-time</h4>
                            <p>ติดตามสถานะออเดอร์ กำลังทำ เสิร์ฟแล้ว แบบไม่มีตกหล่น</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="showcase-footer">
                <span class="status-dot">ระบบพร้อมใช้งานออนไลน์</span>
                <span>OrderPOS v2.0</span>
            </div>
        </div>

        <!-- Right Login Form -->
        <div class="login-panel">
            <div class="panel-header">
                <h2>ยินดีต้อนรับกลับมา 👋</h2>
                <p>กรุณาเข้าสู่ระบบเพื่อจัดการร้านและรับออเดอร์</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert-error">
                    <i class="fas fa-circle-exclamation"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" id="loginForm">
                <div class="modern-form-group">
                    <label for="username">ชื่อผู้ใช้ (Username)</label>
                    <div class="input-icon-wrap">
                        <i class="fas fa-user prefix-icon"></i>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            required
                            autocomplete="username"
                            placeholder="กรอกชื่อผู้ใช้ เช่น admin"
                            value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>"
                        >
                    </div>
                </div>

                <div class="modern-form-group">
                    <label for="password">รหัสผ่าน (Password)</label>
                    <div class="input-icon-wrap">
                        <i class="fas fa-lock prefix-icon"></i>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            placeholder="กรอกรหัสผ่านของคุณ"
                        >
                        <button type="button" class="toggle-password" onclick="togglePassword()" aria-label="แสดงหรือซ่อนรหัสผ่าน">
                            <i class="fas fa-eye" id="toggleEyeIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-login-submit" id="submitBtn">
                    <i class="fas fa-right-to-bracket"></i>
                    <span>เข้าสู่ระบบจัดการร้าน</span>
                </button>
            </form>

            <div class="divider">หรือทางลัดสำหรับหน้าร้าน</div>

            <div class="quick-links">
                <a href="menu.php" class="quick-link-card">
                    <i class="fas fa-book-open"></i>
                    <span>ดูเมนูอาหารลูกค้า</span>
                </a>
                <a href="kitchen.php" class="quick-link-card">
                    <i class="fas fa-fire-burner"></i>
                    <span>หน้าจอห้องครัว</span>
                </a>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const pwdInput = document.getElementById('password');
            const eyeIcon = document.getElementById('toggleEyeIcon');
            if (pwdInput.type === 'password') {
                pwdInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                pwdInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }

        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            btn.style.opacity = '0.85';
            btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> <span>กำลังเข้าสู่ระบบ...</span>';
        });
    </script>
</body>
</html>
