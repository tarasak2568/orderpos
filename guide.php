<?php
require_once __DIR__ . '/config/seo.php';
require_once __DIR__ . '/config/guide_content.php';

$baseUrl = getBaseSiteUrl();
$tabs = getOrderPosGuideData(false);
$requestedTab = (string)($_GET['tab'] ?? 'customer');
if (!isset($tabs[$requestedTab])) {
    $requestedTab = 'customer';
}

$faqItems = [];
foreach (($tabs['seo']['faqs'] ?? []) as $faq) {
    $faqItems[] = [
        '@type' => 'Question',
        'name' => $faq['q'],
        'acceptedAnswer' => [
            '@type' => 'Answer',
            'text' => $faq['a'],
        ],
    ];
}

$faqJsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => $faqItems,
];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg?v=2">
    <link rel="alternate icon" href="/favicon.ico?v=2">
<?= renderDynamicSeoTags([
    'type' => 'platform',
    'title' => 'คู่มือการใช้งาน OrderPOS | วิธีสแกน QR สั่งอาหาร & ระบบ POS ร้านอาหาร ค้าปลีก',
    'description' => 'คู่มือการใช้งาน OrderPOS ฉบับสมบูรณ์ ตั้งแต่ขั้นตอนลูกค้าสแกน QR สั่งอาหาร-ซื้อสินค้า วิธีตั้งค่าร้านอาหาร หน้าจอครัว ร้านของชำยิงบาร์โค้ด PromptPay QR และตั้งค่า SEO',
    'keywords' => 'คู่มือการใช้งาน OrderPOS, วิธีสแกน QR สั่งอาหาร, วิธีใช้ระบบ POS ร้านอาหาร, คู่มือร้านของชำ มินิมาร์ท, ยิงบาร์โค้ด POS, ตั้งค่า PromptPay QR, ตั้งค่า SEO ร้านค้า',
    'canonical' => $baseUrl . '/guide',
]) ?>
    <script type="application/ld+json"><?= json_encode($faqJsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css?v=9">
    <style>
        body {
            background: #F8FAFC;
        }
        .guide-topbar {
            background: #FFFFFF;
            border-bottom: 1px solid #E2E8F0;
            position: sticky;
            top: 0;
            z-index: 120;
            box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
        }
        .guide-topbar-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }
        .guide-brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: #1E293B;
            font-weight: 700;
            font-size: 20px;
        }
        .guide-brand img {
            width: 34px;
            height: 34px;
            border-radius: 8px;
        }
        .guide-nav-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .guide-container {
            max-width: 1200px;
            margin: 26px auto 50px;
            padding: 0 20px;
        }
        .guide-footer {
            text-align: center;
            padding: 26px 20px 38px;
            color: #64748B;
            font-size: 13.5px;
            border-top: 1px solid #E2E8F0;
            background: #FFFFFF;
        }
        .guide-footer-links {
            display: flex;
            justify-content: center;
            gap: 18px;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        .guide-footer-links a {
            color: #FF6B35;
            text-decoration: none;
            font-weight: 500;
        }
        .guide-footer-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <header class="guide-topbar">
        <div class="guide-topbar-inner">
            <a href="/" class="guide-brand">
                <img src="/favicon.svg?v=2" alt="OrderPOS Logo">
                <span>OrderPOS</span>
            </a>
            <nav class="guide-nav-actions" aria-label="ลิงก์หลัก OrderPOS">
                <a href="/menu.php" class="btn btn-outline btn-sm"><i class="fas fa-store"></i> ดูหน้าร้านตัวอย่าง</a>
                <a href="/login.php" class="btn btn-outline btn-sm"><i class="fas fa-right-to-bracket"></i> เข้าสู่ระบบร้านค้า</a>
                <a href="/register.php" class="btn btn-primary btn-sm"><i class="fas fa-rocket"></i> เปิดร้านฟรี</a>
            </nav>
        </div>
    </header>

    <main class="guide-container">
        <?php
        renderOrderPosGuideWidget([
            'isAdmin' => false,
            'defaultTab' => $requestedTab,
        ]);
        ?>
    </main>

    <footer class="guide-footer">
        <div>OrderPOS — ระบบสั่งอาหารผ่าน QR Code และขายหน้าร้าน POS สำหรับร้านอาหาร คาเฟ่ และร้านค้าปลีก</div>
        <div class="guide-footer-links">
            <a href="/guide?tab=customer">คู่มือลูกค้าสั่งซื้อ</a>
            <a href="/guide?tab=restaurant">คู่มือร้านอาหาร</a>
            <a href="/guide?tab=retail">คู่มือร้านค้าปลีก</a>
            <a href="/guide?tab=seo">SEO & คำถามพบบ่อย</a>
            <a href="/login.php">เข้าสู่ระบบ</a>
            <a href="/register.php">สมัครเปิดร้านฟรี</a>
        </div>
    </footer>
</body>
</html>
