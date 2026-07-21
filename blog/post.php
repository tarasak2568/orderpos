<?php
// 1. เชื่อมต่อฐานข้อมูลโดยใช้ไฟล์ db_config.php
$config_file = '../admin/db_config.php';
if (!file_exists($config_file)) {
    die("Error: Database config file not found. Please setup database first at /admin/");
}
include $config_file;

$conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Error: Database connection failed");
}
$conn->set_charset("utf8mb4");

// 2. รับค่า slug
$slug = isset($_GET['slug']) ? $conn->real_escape_string($_GET['slug']) : '';

// 3. ดึงข้อมูลบทความ
$sql = "SELECT * FROM `astroyu_blogs` WHERE `slug` = '$slug' LIMIT 1";
$result = $conn->query($sql);

if (!$result || $result->num_rows == 0) {
    // หากไม่พบหน้า ให้ย้อนกลับไปหน้ารวมบทความหรือแสดง 404
    header("HTTP/1.1 404 Not Found");
    echo "<h1>ขออภัย ไม่พบหน้าบทความที่คุณค้นหา</h1><p><a href='/blog'>กลับไปหน้ารวมบทความ</a></p>";
    exit;
}

$blog = $result->fetch_assoc();

// จัดการแท็ก
$tags_str = $blog['tags'];
$tags_arr = array_map('trim', explode(',', $tags_str));

// แปลงวันที่ภาษาไทย
$pubDateObj = new DateTime($blog['pubDate']);
$thai_months = [
    1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน', 
    5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม', 
    9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
];
$formattedDate = $pubDateObj->format('j') . ' ' . $thai_months[(int)$pubDateObj->format('n')] . ' ' . ($pubDateObj->format('Y') + 543);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <meta name="viewport" content="width=device-width">
    <meta name="generator" content="Astro v7.0.5">
    
    <!-- SEO Directives -->
    <title><?php echo htmlspecialchars($blog['title']); ?> | Real Fishing Thailand</title>
    <meta name="description" content="<?php echo htmlspecialchars($blog['description']); ?>">
    <link rel="canonical" href="http://<?php echo $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="article">
    <meta property="og:url" content="http://<?php echo $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($blog['title']); ?> | Real Fishing Thailand">
    <meta property="og:description" content="<?php echo htmlspecialchars($blog['description']); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($blog['heroImage']); ?>">
    <meta property="og:site_name" content="เรียวฟิชชิ่งไทยแลนด์">
    <meta property="og:locale" content="th_TH">
    
    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="http://<?php echo $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    <meta property="twitter:title" content="<?php echo htmlspecialchars($blog['title']); ?> | Real Fishing Thailand">
    <meta property="twitter:description" content="<?php echo htmlspecialchars($blog['description']); ?>">
    <meta property="twitter:image" content="<?php echo htmlspecialchars($blog['heroImage']); ?>">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- JSON-LD Schema Structured Data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BlogPosting",
        "headline": "<?php echo htmlspecialchars($blog['title']); ?>",
        "description": "<?php echo htmlspecialchars($blog['description']); ?>",
        "image": "<?php echo htmlspecialchars($blog['heroImage']); ?>",
        "datePublished": "<?php echo $pubDateObj->format('c'); ?>",
        "author": {
            "@type": "Person",
            "name": "<?php echo htmlspecialchars($blog['author']); ?>"
        },
        "publisher": {
            "@type": "Organization",
            "name": "เรียวฟิชชิ่งไทยแลนด์",
            "logo": {
                "@type": "ImageObject",
                "url": "http://<?php echo $_SERVER['HTTP_HOST']; ?>/favicon.svg"
            }
        }
    }
    </script>
    
    <link rel="stylesheet" href="/_astro/_astro_content.CXrWP3t1.css">
    
    <style>
        :root {
            --bg-primary: #080b11;
            --bg-secondary: #0f131e;
            --bg-tertiary: #171d2c;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --accent: #ff6b35;
            --accent-glow: rgba(255, 107, 53, 0.4);
            --accent-light: rgba(255, 107, 53, 0.1);
            --accent-gradient: linear-gradient(135deg, #ff6b35 0%, #ff9f1c 100%);
            --accent-gradient-hover: linear-gradient(135deg, #e0531f 0%, #e68a00 100%);
            --border-color: rgba(255, 255, 255, 0.08);
            --glass-bg: rgba(15, 19, 30, 0.7);
            --glass-border: rgba(255, 255, 255, 0.06);
            --font-heading: 'Outfit', 'Sarabun', sans-serif;
            --font-body: 'Inter', 'Sarabun', sans-serif;
            --shadow-sm: 0 2px 8px -1px rgba(0, 0, 0, 0.2);
            --shadow-md: 0 4px 20px -2px rgba(0, 0, 0, 0.3);
            --shadow-lg: 0 10px 30px -5px rgba(0, 0, 0, 0.5), 0 0 20px -2px rgba(255, 107, 53, 0.1);
            --transition-fast: 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-normal: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-slow: 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            font-family: var(--font-body);
            background-color: var(--bg-primary);
            color: var(--text-primary);
            scroll-behavior: smooth;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        body {
            min-height: 100vh;
            position: relative;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }
        
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        /* Glassmorphism utility */
        .glass {
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
        }
        
        /* Background Decorations */
        .background-decor {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            pointer-events: none;
            overflow: hidden;
        }

        .glow {
            position: absolute;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            filter: blur(120px);
            opacity: 0.05;
        }

        .glow-1 {
            top: -10%;
            left: -10%;
            background: var(--accent);
        }

        .glow-2 {
            bottom: -10%;
            right: -10%;
            background: var(--accent);
        }

        .glow-3 {
            top: 40%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #00b4d8;
        }
        
        /* Navbar */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 100;
            transition: all var(--transition-normal);
            border-bottom: 1px solid var(--glass-border);
        }

        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 4.5rem;
        }

        .logo {
            font-family: var(--font-heading);
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text-primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            letter-spacing: -0.02em;
        }

        .logo-accent {
            color: var(--accent);
        }

        .nav-links {
            display: flex;
            gap: 2.25rem;
        }

        .nav-link {
            font-size: 0.95rem;
            font-weight: 500;
            color: var(--text-secondary);
            text-decoration: none;
            transition: color var(--transition-fast);
            position: relative;
            padding: 0.25rem 0;
        }

        .nav-link:hover {
            color: var(--text-primary);
        }
        
        /* Footer */
        .footer {
            background: #06090e;
            border-top: 1px solid var(--border-color);
            padding: 5rem 0 0;
            margin-top: auto;
            z-index: 10;
            position: relative;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr 0.8fr 1.2fr;
            gap: 3rem;
            padding-bottom: 4rem;
        }

        @media (max-width: 968px) {
            .footer-grid {
                grid-template-columns: 1fr 1fr;
                gap: 2.5rem;
            }
            .nav-links {
                display: none;
            }
        }

        @media (max-width: 576px) {
            .footer-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
        }

        .footer-col {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .brand-desc {
            font-size: 0.9rem;
            color: var(--text-secondary);
            line-height: 1.6;
        }

        .col-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.01em;
            margin-bottom: 0.5rem;
        }

        .col-links {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .col-links a {
            font-size: 0.9rem;
            color: var(--text-secondary);
            text-decoration: none;
            transition: color var(--transition-fast);
        }

        .col-links a:hover {
            color: var(--accent);
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            padding: 2rem 0;
        }

        .bottom-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .copyright {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .bottom-links {
            display: flex;
            gap: 1.25rem;
            font-size: 0.85rem;
        }

        .bottom-links a {
            color: var(--text-muted);
            text-decoration: none;
            transition: color var(--transition-fast);
        }

        .bottom-links a:hover {
            color: var(--text-primary);
        }
        
        .divider {
            color: rgba(255, 255, 255, 0.1);
        }

        /* Detail Page Styling */
        .prose-content {
            color: var(--text-secondary);
            font-size: 1.05rem;
            line-height: 1.8;
        }

        .prose-content p {
            margin-bottom: 1.5rem;
        }

        .prose-content h2 {
            color: var(--text-primary);
            letter-spacing: -0.01em;
            margin: 2.5rem 0 1rem;
            font-size: 1.75rem;
            font-weight: 700;
        }

        .prose-content h3 {
            color: var(--text-primary);
            margin: 2rem 0 1rem;
            font-size: 1.35rem;
            font-weight: 700;
        }

        .prose-content hr {
            background: var(--border-color);
            border: 0;
            height: 1px;
            margin: 2.5rem 0;
        }

        .prose-content ul, .prose-content ol {
            flex-direction: column;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            padding-left: 1.5rem;
            display: flex;
        }

        .prose-content li {
            color: var(--text-secondary);
        }

        .prose-content blockquote {
            background: var(--accent-light);
            border-left: 4px solid var(--accent);
            border-radius: 0 12px 12px 0;
            margin: 1.5rem 0;
            padding: 1rem 1.5rem;
        }

        .prose-content blockquote p {
            color: var(--text-primary);
            margin-bottom: 0;
            font-style: italic;
            font-weight: 500;
        }

        .prose-content strong {
            color: var(--text-primary);
            font-weight: 600;
        }

        .blog-detail-page {
            min-height: 100vh;
            padding-top: 5rem;
        }

        .article-header {
            padding: 5rem 0 2rem;
        }

        .text-center {
            text-align: center;
        }

        .article-tags {
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 1.25rem;
            display: flex;
        }

        .tag-badge {
            color: var(--accent);
            background: var(--accent-light);
            border: 1px solid rgba(255, 107, 53, 0.15);
            border-radius: 50px;
            padding: 0.35rem 0.85rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .article-title {
            letter-spacing: -0.02em;
            max-width: 800px;
            margin-bottom: 1.5rem;
            margin-left: auto;
            margin-right: auto;
            font-size: 2.5rem;
            font-weight: 800;
            line-height: 1.25;
        }

        @media (max-width: 768px) {
            .article-title {
                font-size: 2rem;
            }
        }

        .article-meta {
            color: var(--text-muted);
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.9rem;
            display: flex;
        }

        .meta-divider {
            color: var(--border-color);
        }

        .article-image-container {
            max-width: 900px;
            margin-bottom: 3rem;
        }

        .image-wrapper {
            aspect-ratio: 16/9;
            border-radius: 24px;
            max-height: 450px;
            overflow: hidden;
        }

        .article-hero-image {
            object-fit: cover;
            width: 100%;
            height: 100%;
        }

        .article-body-section {
            padding-bottom: 6rem;
        }

        .article-container {
            max-width: 760px;
        }

        .back-link {
            color: var(--text-muted);
            transition: color var(--transition-fast);
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 2rem;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-flex;
            text-decoration: none;
        }

        .back-link:hover {
            color: var(--accent);
        }

        .back-link svg {
            transition: transform var(--transition-fast);
        }

        .back-link:hover svg {
            transform: translateX(-3px);
        }

        .article-footer-tags {
            border-top: 1px solid var(--border-color);
            align-items: center;
            gap: 0.75rem;
            margin-top: 3.5rem;
            padding-top: 2rem;
            display: flex;
        }

        .tags-label {
            color: var(--text-muted);
            font-size: 0.9rem;
            font-weight: 600;
        }

        .footer-tags-list {
            flex-wrap: wrap;
            gap: 0.5rem;
            display: flex;
        }

        .footer-tag {
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50px;
            padding: 0.25rem 0.75rem;
            font-size: 0.8rem;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="background-decor">
        <div class="glow glow-1"></div>
        <div class="glow glow-2"></div>
        <div class="glow glow-3"></div>
    </div>
    
    <header class="header glass">
        <div class="container nav-container">
            <a href="/" class="logo">
                <span class="logo-accent">Real</span>FishingThailand
            </a>
            <nav class="nav-links">
                <a href="/" class="nav-link">หน้าแรก</a>
                <a href="/#products" class="nav-link">สินค้า</a>
                <a href="/#services" class="nav-link">บริการ</a>
                <a href="/blog" class="nav-link">บทความ</a>
                <a href="/#contact" class="nav-link">ติดต่อเรา</a>
            </nav>
        </div>
    </header>

    <article class="blog-detail-page">
        <!-- Header/Hero details of article -->
        <header class="article-header">
            <div class="container text-center">
                <div class="article-tags">
                    <?php foreach ($tags_arr as $tag): ?>
                        <span class="tag-badge"><?php echo htmlspecialchars($tag); ?></span>
                    <?php endforeach; ?>
                </div>
                
                <h1 class="article-title"><?php echo htmlspecialchars($blog['title']); ?></h1>
                
                <div class="article-meta">
                    <span class="meta-author">เขียนโดย <strong><?php echo htmlspecialchars($blog['author']); ?></strong></span>
                    <span class="meta-divider">|</span>
                    <span class="meta-date"><?php echo $formattedDate; ?></span>
                    <span class="meta-divider">|</span>
                    <span class="meta-read"><?php echo htmlspecialchars($blog['readTime']); ?></span>
                </div>
            </div>
        </header>

        <!-- Hero Image -->
        <div class="container article-image-container">
            <div class="image-wrapper glass">
                <img src="<?php echo htmlspecialchars($blog['heroImage']); ?>" alt="<?php echo htmlspecialchars($blog['title']); ?>" class="article-hero-image">
            </div>
        </div>

        <!-- Content markup body -->
        <section class="article-body-section">
            <div class="container article-container">
                <!-- Back Navigation -->
                <a href="/blog" class="back-link">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" x2="5" y1="12" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    กลับไปหน้ารวมบทความ
                </a>

                <!-- Markdown/HTML content prose -->
                <div class="prose-content">
                    <?php 
                        // แปลงขึ้นบรรทัดใหม่เป็น <p> หากไม่มี tag html
                        $body_content = $blog['content'];
                        if (strpos($body_content, '<p>') === false && strpos($body_content, '<div>') === false) {
                            $body_content = nl2br($body_content);
                        }
                        echo $body_content; 
                    ?>
                </div>
                
                <!-- Article Share & Actions -->
                <div class="article-footer-tags">
                    <span class="tags-label">แท็ก:</span>
                    <div class="footer-tags-list">
                        <?php foreach ($tags_arr as $tag): ?>
                            <span class="footer-tag"><?php echo htmlspecialchars($tag); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>
    </article>

    <footer class="footer">
        <div class="container footer-grid">
            <!-- Col 1: Brand -->
            <div class="footer-col brand-col">
                <a href="/" class="logo footer-logo">
                    <span class="logo-accent">Real</span>FishingThailand
                </a>
                <p class="brand-desc">
                    รักประมูล คือแพลตฟอร์มประมูลสินค้าออนไลน์ ภายใต้การบริหารงานของ เรียวฟิชชิ่งไทยแลนด์ จำหน่ายอุปกรณ์ตกปลา ปลีก-ส่ง คุณภาพสูง
                </p>
            </div>
            
            <!-- Col 2: Products -->
            <div class="footer-col list-col">
                <h4 class="col-title">สินค้าแนะนำ</h4>
                <ul class="col-links">
                    <li><a href="/#products">รอกสปินนิ่งสีทอง</a></li>
                    <li><a href="/#products">คันเบ็ดคาร์บอนไฟเบอร์</a></li>
                    <li><a href="/#products">เหยื่อยางกุ้ง TPE เรืองแสง</a></li>
                    <li><a href="/#products">สายเอ็นตกปลา PE Zero X8</a></li>
                </ul>
            </div>

            <!-- Col 3: Services -->
            <div class="footer-col list-col">
                <h4 class="col-title">บริการของเรา</h4>
                <ul class="col-links">
                    <li><a href="/#services">จัดชุดตกปลามือใหม่</a></li>
                    <li><a href="/#services">บริการซ่อมบำรุงและล้างรอก</a></li>
                    <li><a href="/#services">จำหน่ายสินค้าขายส่ง</a></li>
                    <li><a href="/#services">คำปรึกษาการเซ็ตอุปกรณ์</a></li>
                </ul>
            </div>
        </div>
        
        <!-- Bottom Bar -->
        <div class="footer-bottom">
            <div class="container bottom-container">
                <p class="copyright">&copy; <?php echo date('Y'); ?> เรียวฟิชชิ่งไทยแลนด์. สงวนลิขสิทธิ์ทั้งหมด.</p>
                <div class="bottom-links">
                    <a href="/blog">บทความทั้งหมด</a>
                    <span class="divider">|</span>
                    <a href="#">นโยบายความเป็นส่วนตัว</a>
                    <span class="divider">|</span>
                    <a href="#">เงื่อนไขการให้บริการ</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
