<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
define('WP_USE_THEMES', false);

// 1. เชื่อมต่อฐานข้อมูลโดยใช้ไฟล์ db_config.php
$config_file = 'db_config.php';
$db_host = 'localhost';
$db_user = 'u262149987_testweb';
$db_name = 'u262149987_testweb';
$db_pass = '';

if (file_exists($config_file)) {
    include $config_file;
    $db_host = DB_HOST;
    $db_user = DB_USER;
    $db_pass = DB_PASS;
    $db_name = DB_NAME;
}

// ทดสอบการเชื่อมต่อฐานข้อมูล
$db_connected = false;
$conn = null;
try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn && !$conn->connect_error) {
        $db_connected = true;
    }
} catch (Exception $e) {
    $db_connected = false;
}

// จัดการการกรอกรหัสผ่านฐานข้อมูล (Setup wizard)
if (!$db_connected) {
    if (isset($_POST['action']) && $_POST['action'] == 'setup_db') {
        $submitted_pass = $_POST['db_pass'];
        try {
            $test_conn = new mysqli($db_host, $db_user, $submitted_pass, $db_name);
            if ($test_conn && !$test_conn->connect_error) {
                // เขียนข้อมูลลงไฟล์ db_config.php
                $config_content = "<?php\n"
                                . "define('DB_HOST', 'localhost');\n"
                                . "define('DB_USER', 'u262149987_testweb');\n"
                                . "define('DB_PASS', '" . addslashes($submitted_pass) . "');\n"
                                . "define('DB_NAME', 'u262149987_testweb');\n"
                                . "?>";
                file_put_contents($config_file, $config_content);
                header("Location: index.php");
                exit;
            } else {
                $setup_error = "ไม่สามารถเชื่อมต่อฐานข้อมูลได้: " . $test_conn->connect_error;
            }
        } catch (Exception $e) {
            $setup_error = "ไม่สามารถเชื่อมต่อฐานข้อมูลได้: " . $e->getMessage();
        }
    }
    
    // แสดงฟอร์มกรอกรหัสผ่านฐานข้อมูล
    ?>
    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>ตั้งค่าฐานข้อมูล | Real Fishing</title>
        <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <style>
            :root {
                --bg-primary: #080b11;
                --accent: #ff6b35;
                --text-primary: #f8fafc;
            }
            body {
                font-family: 'Sarabun', sans-serif;
                background-color: var(--bg-primary);
                color: var(--text-primary);
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                margin: 0;
            }
            .setup-card {
                background: rgba(15, 19, 30, 0.7);
                border: 1px solid rgba(255, 255, 255, 0.08);
                padding: 2.5rem;
                border-radius: 16px;
                width: 100%;
                max-width: 450px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
                backdrop-filter: blur(10px);
            }
            h2 {
                text-align: center;
                margin-bottom: 1.5rem;
                color: var(--text-primary);
                font-size: 1.6rem;
            }
            h2 span {
                color: var(--accent);
            }
            p {
                color: #94a3b8;
                font-size: 0.9rem;
                line-height: 1.5;
                margin-bottom: 1.5rem;
            }
            .form-group {
                margin-bottom: 1.25rem;
            }
            label {
                display: block;
                margin-bottom: 0.5rem;
                font-size: 0.9rem;
                color: #94a3b8;
            }
            input {
                width: 100%;
                padding: 0.75rem 1rem;
                background: rgba(255, 255, 255, 0.03);
                border: 1px solid rgba(255, 255, 255, 0.08);
                border-radius: 8px;
                color: var(--text-primary);
                font-size: 1rem;
                box-sizing: border-box;
            }
            input:focus {
                outline: none;
                border-color: var(--accent);
                box-shadow: 0 0 0 2px rgba(255, 107, 53, 0.2);
            }
            .btn {
                width: 100%;
                padding: 0.75rem;
                background: linear-gradient(135deg, #ff6b35 0%, #ff9f1c 100%);
                border: none;
                border-radius: 8px;
                color: #fff;
                font-size: 1rem;
                font-weight: 600;
                cursor: pointer;
                transition: opacity 0.2s;
                margin-top: 1rem;
            }
            .btn:hover {
                opacity: 0.9;
            }
            .error-msg {
                color: #f43f5e;
                background: rgba(244, 63, 94, 0.1);
                border: 1px solid #f43f5e;
                padding: 0.75rem;
                border-radius: 6px;
                margin-bottom: 1.25rem;
                font-size: 0.85rem;
                line-height: 1.4;
            }
        </style>
    </head>
    <body>
        <div class="setup-card">
            <h2>ตั้งค่าฐานข้อมูล <span>Real Fishing</span></h2>
            <p>
                ระบบหลังบ้านต้องการเชื่อมต่อฐานข้อมูลใหม่ <strong>u262149987_testweb</strong> กรุณากรอกรหัสผ่าน (Password) ของฐานข้อมูลดังกล่าว เพื่อให้ระบบทำการติดตั้งตารางให้อัตโนมัติครับ
            </p>
            
            <?php if (isset($setup_error)): ?>
                <div class="error-msg"><?php echo $setup_error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <input type="hidden" name="action" value="setup_db">
                <div class="form-group">
                    <label>MySQL Host</label>
                    <input type="text" value="localhost" disabled>
                </div>
                <div class="form-group">
                    <label>MySQL Database & User</label>
                    <input type="text" value="u262149987_testweb" disabled>
                </div>
                <div class="form-group">
                    <label>รหัสผ่านฐานข้อมูล (MySQL Password)</label>
                    <input type="password" name="db_pass" placeholder="กรอกรหัสผ่านของฐานข้อมูล" required autofocus>
                </div>
                <button type="submit" class="btn">เชื่อมต่อและติดตั้งระบบ</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ตั้งค่าเอนโคดดิ้งและโซนเวลา
$conn->set_charset("utf8mb4");
$conn->query("SET time_zone = '+07:00'");

// 2. ติดตั้งตารางฐานข้อมูลและบันทึกข้อมูลตัวอย่างโดยอัตโนมัติ (Self-installer)
// ตารางบล็อก/บทความ
$conn->query("
    CREATE TABLE IF NOT EXISTS `astroyu_blogs` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `slug` varchar(255) NOT NULL,
        `title` varchar(255) NOT NULL,
        `description` text NOT NULL,
        `content` text NOT NULL,
        `heroImage` varchar(255) DEFAULT '/images/fishing_reel.png',
        `author` varchar(255) DEFAULT 'เรียวฟิชชิ่ง',
        `tags` varchar(255) DEFAULT 'อุปกรณ์ตกปลา',
        `readTime` varchar(50) DEFAULT '5 นาที',
        `pubDate` datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `slug` (`slug`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// ตารางสินค้า
$conn->query("
    CREATE TABLE IF NOT EXISTS `astroyu_products` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `prod_id` varchar(50) NOT NULL,
        `name` varchar(255) NOT NULL,
        `price` int(11) NOT NULL,
        `description` text NOT NULL,
        `image` varchar(255) DEFAULT '/images/fishing_reel.png',
        `badge` varchar(50) DEFAULT '',
        `category` varchar(100) DEFAULT 'อุปกรณ์ตกปลา',
        `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `prod_id` (`prod_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// เพิ่มบทความจำลองเริ่มต้นหากยังไม่มีข้อมูล
$check_empty = $conn->query("SELECT id FROM astroyu_blogs LIMIT 1");
if ($check_empty && $check_empty->num_rows == 0) {
    $conn->query("
        INSERT INTO `astroyu_blogs` (`slug`, `title`, `description`, `content`, `heroImage`, `author`, `tags`, `readTime`) VALUES
        ('choosing-fishing-reel-for-beginners', 
         '5 วิธีเลือกซื้อรอกตกปลาสำหรับมือใหม่ เลือกแบบไหนดี?', 
         'เจาะลึกวิธีการเลือกรอกตกปลาประเภทต่างๆ สำหรับมือใหม่ ทั้งรอกสปินนิ่ง รอกเบทคาสติ้ง และรอกสปินคาสท์', 
         'สำหรับมือใหม่ที่เพิ่งก้าวเข้าสู่วงการตกปลา การเลือกรอกตกปลาถือเป็นสิ่งสำคัญที่สุด...', 
         '/images/fishing_reel.png', 'เรียวฟิชชิ่ง', 'รอกตกปลา,มือใหม่', '5 นาที'),
        ('pe-vs-nylon-lines-guide', 
         'สาย PE vs สายเอ็นไนลอน แตกต่างกันอย่างไร และควรเลือกใช้ตอนไหน?', 
         'เปรียบเทียบข้อดีข้อเสียระหว่างสาย PE ถัก และสายเอ็นไนลอนธรรมดา ไขข้อข้องใจยอดฮิตของนักตกปลา', 
         'การเลือกใช้สายเมนในรอกตกปลามีผลอย่างมากต่อระยะตีและแอคชันของเหยื่อ...', 
         '/images/pe_line.png', 'เรียวฟิชชิ่ง', 'สายเบ็ด,สายPE', '7 นาที'),
        ('snakehead-lure-fishing-techniques', 
         'เทคนิคการตกปลาช่อนด้วยเหยื่อยาง/เหยื่อปลอม ให้ได้ผลลัพธ์สูงสุด', 
         'อัปเดตเทคนิคเด็ดในการลากเหยื่อยาง เคาะเหยื่อปลอม และเลือกจังหวะวัดเบ็ดปลาช่อนธรรมชาติ', 
         'ปลาช่อนเป็นปลาซุ่มโจมตีที่นักตกปลานิยมล่าเหยื่อปลอมมากที่สุด...', 
         '/images/shrimp_lure.png', 'เรียวฟิชชิ่ง', 'เทคนิคการตกปลา,เหยื่อยาง', '6 นาที')
    ");
}

// เพิ่มสินค้าจำลองเริ่มต้นหากยังไม่มีข้อมูล
$check_prod_empty = $conn->query("SELECT id FROM astroyu_products LIMIT 1");
if ($check_prod_empty && $check_prod_empty->num_rows == 0) {
    $conn->query("
        INSERT INTO `astroyu_products` (`prod_id`, `name`, `price`, `description`, `image`, `badge`, `category`) VALUES
        ('prod-001', 'รอกสปินนิ่งเรียวฟิชชิ่งรุ่น Gold Limited', 1890, 'รอกสปินนิ่งสีทองสุดหรู บอดี้คาร์บอนน้ำหนักเบา ระบบลูกปืนสแตนเลส 9+1 ลื่นไหลทุกการหมุน ดับเบิ้ลแดร็กกำลังเบรกสูง', '/images/fishing_reel.png', 'แนะนำ', 'รอกตกปลา'),
        ('prod-002', 'คันเบ็ดตีเหยื่อปลอม Carbon Fiber X-Treme', 2450, 'คันคาร์บอนความเหนียวพิเศษ ไกด์ซิลิคอนคาร์ไบด์ลดการเสียดสีของสาย PE ได้ดีเยี่ยม เซนส์สัมผัสไว ดีไซน์จับกระชับมือ', '/images/fishing_rod.png', 'ขายดี', 'คันเบ็ด'),
        ('prod-003', 'เหยื่อยางกุ้ง TPE เรืองแสง Glow Shrimp 3D', 120, 'เหยื่อปลอมเนื้อกุ้งนุ่มพิเศษ ยืดหยุ่นทนทานต่อแรงกัดของปลาล่าเหยื่อ ตัวเบ็ดถ่วงท้องพร้อมใช้งาน เรืองแสงในน้ำขุ่นและตอนกลางคืน', '/images/shrimp_lure.png', 'ของแท้', 'เหยื่อปลอม'),
        ('prod-004', 'สายเอ็นตกปลาถักแปด PE Zero X8 (150m)', 350, 'สาย PE ถัก 8 เส้นความละเอียดสูง เคลือบผิวเรียบลื่นพิเศษ ช่วยให้ตีเหยื่อได้ไกล ทนแรงดึงและแรงขูดขีดสูง ไม่อมน้ำ', '/images/pe_line.png', 'สินค้าใหม่', 'สายเบ็ด')
    ");
}

// 3. กำหนดข้อมูลบัญชีผู้ดูแลระบบ
$admin_user = 'admin';
$admin_pass = 'realfishing2026';

// ตรวจสอบการเข้าสู่ระบบ
if (isset($_POST['action']) && $_POST['action'] == 'login') {
    $user = $_POST['username'];
    $pass = $_POST['password'];
    if ($user === $admin_user && $pass === $admin_pass) {
        $_SESSION['astroyu_admin'] = true;
        header("Location: index.php");
        exit;
    } else {
        $error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
    }
}

// ตรวจสอบการออกจากระบบ
if (isset($_GET['logout'])) {
    unset($_SESSION['astroyu_admin']);
    header("Location: index.php");
    exit;
}

// ถ้าไม่ได้เข้าสู่ระบบ ให้แสดงฟอร์ม Login
if (!isset($_SESSION['astroyu_admin'])) {
    ?>
    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>เข้าสู่ระบบจัดการหลังบ้าน | Real Fishing</title>
        <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <style>
            :root {
                --bg-primary: #080b11;
                --accent: #ff6b35;
                --text-primary: #f8fafc;
            }
            body {
                font-family: 'Sarabun', sans-serif;
                background-color: var(--bg-primary);
                color: var(--text-primary);
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                margin: 0;
            }
            .login-card {
                background: rgba(15, 19, 30, 0.7);
                border: 1px solid rgba(255, 255, 255, 0.08);
                padding: 2.5rem;
                border-radius: 16px;
                width: 100%;
                max-width: 400px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
                backdrop-filter: blur(10px);
            }
            h2 {
                text-align: center;
                margin-bottom: 2rem;
                color: var(--text-primary);
                font-size: 1.75rem;
            }
            h2 span {
                color: var(--accent);
            }
            .form-group {
                margin-bottom: 1.25rem;
            }
            label {
                display: block;
                margin-bottom: 0.5rem;
                font-size: 0.9rem;
                color: #94a3b8;
            }
            input {
                width: 100%;
                padding: 0.75rem 1rem;
                background: rgba(255, 255, 255, 0.03);
                border: 1px solid rgba(255, 255, 255, 0.08);
                border-radius: 8px;
                color: var(--text-primary);
                font-size: 1rem;
                box-sizing: border-box;
            }
            input:focus {
                outline: none;
                border-color: var(--accent);
                box-shadow: 0 0 0 2px rgba(255, 107, 53, 0.2);
            }
            .btn {
                width: 100%;
                padding: 0.75rem;
                background: linear-gradient(135deg, #ff6b35 0%, #ff9f1c 100%);
                border: none;
                border-radius: 8px;
                color: #fff;
                font-size: 1rem;
                font-weight: 600;
                cursor: pointer;
                transition: opacity 0.2s;
                margin-top: 1rem;
            }
            .btn:hover {
                opacity: 0.9;
            }
            .error-msg {
                color: #f43f5e;
                text-align: center;
                margin-bottom: 1rem;
                font-size: 0.9rem;
            }
        </style>
    </head>
    <body>
        <div class="login-card">
            <h2><span>Real</span> Fishing</h2>
            <?php if (isset($error)): ?>
                <div class="error-msg"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label>ชื่อผู้ใช้งาน (Username)</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label>รหัสผ่าน (Password)</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="btn">เข้าสู่ระบบ</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// 4. ส่วน CRUD การจัดการข้อมูล
$success_msg = "";
$error_msg = "";
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'blogs';

// ดำเนินการ CRUD สำหรับบทความ (Blogs)
if ($current_tab == 'blogs') {
    // เพิ่มบทความ
    if (isset($_POST['action']) && $_POST['action'] == 'add') {
        $title = $conn->real_escape_string($_POST['title']);
        $description = $conn->real_escape_string($_POST['description']);
        $content = $conn->real_escape_string($_POST['content']);
        $heroImage = $conn->real_escape_string($_POST['heroImage']);
        $author = $conn->real_escape_string($_POST['author']);
        $tags = $conn->real_escape_string($_POST['tags']);
        $readTime = $conn->real_escape_string($_POST['readTime']);
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $_POST['slug'] ?: $_POST['title'])));
        
        $insert = $conn->query("
            INSERT INTO `astroyu_blogs` (`slug`, `title`, `description`, `content`, `heroImage`, `author`, `tags`, `readTime`) 
            VALUES ('$slug', '$title', '$description', '$content', '$heroImage', '$author', '$tags', '$readTime')
        ");
        
        if ($insert) {
            $success_msg = "เพิ่มบทความสำเร็จเรียบร้อยแล้ว!";
        } else {
            $error_msg = "เกิดข้อผิดพลาดในการบันทึก: " . $conn->error;
        }
    }

    // แก้ไขบทความ
    if (isset($_POST['action']) && $_POST['action'] == 'edit') {
        $id = (int)$_POST['id'];
        $title = $conn->real_escape_string($_POST['title']);
        $description = $conn->real_escape_string($_POST['description']);
        $content = $conn->real_escape_string($_POST['content']);
        $heroImage = $conn->real_escape_string($_POST['heroImage']);
        $author = $conn->real_escape_string($_POST['author']);
        $tags = $conn->real_escape_string($_POST['tags']);
        $readTime = $conn->real_escape_string($_POST['readTime']);
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $_POST['slug'])));
        
        $update = $conn->query("
            UPDATE `astroyu_blogs` 
            SET `slug` = '$slug', `title` = '$title', `description` = '$description', `content` = '$content', 
                `heroImage` = '$heroImage', `author` = '$author', `tags` = '$tags', `readTime` = '$readTime' 
            WHERE `id` = $id
        ");
        
        if ($update) {
            $success_msg = "อัปเดตบทความสำเร็จเรียบร้อยแล้ว!";
        } else {
            $error_msg = "เกิดข้อผิดพลาดในการอัปเดต: " . $conn->error;
        }
    }

    // ลบบทความ
    if (isset($_GET['delete'])) {
        $id = (int)$_GET['delete'];
        $delete = $conn->query("DELETE FROM `astroyu_blogs` WHERE `id` = $id");
        if ($delete) {
            $success_msg = "ลบบทความเรียบร้อยแล้ว!";
        } else {
            $error_msg = "ไม่สามารถลบบทความได้: " . $conn->error;
        }
    }
}

// ดำเนินการ CRUD สำหรับสินค้า (Products)
if ($current_tab == 'products') {
    // เพิ่มสินค้า
    if (isset($_POST['action']) && $_POST['action'] == 'add_product') {
        $prod_id = $conn->real_escape_string($_POST['prod_id']);
        $name = $conn->real_escape_string($_POST['name']);
        $price = (int)$_POST['price'];
        $description = $conn->real_escape_string($_POST['description']);
        $image = $conn->real_escape_string($_POST['image']);
        $badge = $conn->real_escape_string($_POST['badge']);
        $category = $conn->real_escape_string($_POST['category']);
        
        $insert = $conn->query("
            INSERT INTO `astroyu_products` (`prod_id`, `name`, `price`, `description`, `image`, `badge`, `category`) 
            VALUES ('$prod_id', '$name', $price, '$description', '$image', '$badge', '$category')
        ");
        
        if ($insert) {
            $success_msg = "เพิ่มสินค้าสำเร็จเรียบร้อยแล้ว!";
        } else {
            $error_msg = "เกิดข้อผิดพลาดในการบันทึกสินค้า: " . $conn->error;
        }
    }

    // แก้ไขสินค้า
    if (isset($_POST['action']) && $_POST['action'] == 'edit_product') {
        $id = (int)$_POST['id'];
        $prod_id = $conn->real_escape_string($_POST['prod_id']);
        $name = $conn->real_escape_string($_POST['name']);
        $price = (int)$_POST['price'];
        $description = $conn->real_escape_string($_POST['description']);
        $image = $conn->real_escape_string($_POST['image']);
        $badge = $conn->real_escape_string($_POST['badge']);
        $category = $conn->real_escape_string($_POST['category']);
        
        $update = $conn->query("
            UPDATE `astroyu_products` 
            SET `prod_id` = '$prod_id', `name` = '$name', `price` = $price, 
                `description` = '$description', `image` = '$image', `badge` = '$badge', `category` = '$category' 
            WHERE `id` = $id
        ");
        
        if ($update) {
            $success_msg = "อัปเดตสินค้าสำเร็จเรียบร้อยแล้ว!";
        } else {
            $error_msg = "เกิดข้อผิดพลาดในการอัปเดตสินค้า: " . $conn->error;
        }
    }

    // ลบสินค้า
    if (isset($_GET['delete_product'])) {
        $id = (int)$_GET['delete_product'];
        $delete = $conn->query("DELETE FROM `astroyu_products` WHERE `id` = $id");
        if ($delete) {
            $success_msg = "ลบสินค้าเรียบร้อยแล้ว!";
        } else {
            $error_msg = "ไม่สามารถลบสินค้าได้: " . $conn->error;
        }
    }
}

// ค้นหาข้อมูลเพื่อทำการแก้ไข (Edit fetching)
$edit_data = null;
$edit_product_data = null;

if ($current_tab == 'blogs' && isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $edit_data = $conn->query("SELECT * FROM `astroyu_blogs` WHERE `id` = $edit_id")->fetch_assoc();
}

if ($current_tab == 'products' && isset($_GET['edit_prod_id'])) {
    $edit_prod_id = (int)$_GET['edit_prod_id'];
    $edit_product_data = $conn->query("SELECT * FROM `astroyu_products` WHERE `id` = $edit_prod_id")->fetch_assoc();
}

// ดึงรายการข้อมูลทั้งหมด
$blogs = $conn->query("SELECT * FROM `astroyu_blogs` ORDER BY `pubDate` DESC");
$products = $conn->query("SELECT * FROM `astroyu_products` ORDER BY `created_at` DESC");
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบหลังบ้านจัดการร้านค้า | Real Fishing Thailand</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-primary: #080b11;
            --bg-secondary: #0f131e;
            --accent: #ff6b35;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --border-color: rgba(255, 255, 255, 0.08);
        }
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            margin: 0;
            padding: 0;
        }
        .header-bar {
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header-bar h1 {
            margin: 0;
            font-size: 1.5rem;
        }
        .header-bar h1 span {
            color: var(--accent);
        }
        .logout-link {
            color: #f43f5e;
            text-decoration: none;
            font-weight: 600;
        }
        
        /* Navigation Tabs */
        .tabs-container {
            max-width: 1200px;
            margin: 2rem auto 0;
            padding: 0 1rem;
            display: flex;
            gap: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        .tab-btn {
            padding: 0.75rem 1.5rem;
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 600;
            border-radius: 8px 8px 0 0;
            border: 1px solid transparent;
            margin-bottom: -1px;
            transition: all 0.2s;
        }
        .tab-btn.active {
            color: var(--accent);
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-bottom-color: var(--bg-primary);
        }
        .tab-btn:hover:not(.active) {
            color: var(--text-primary);
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 2rem;
        }
        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
            }
        }
        .card {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
        }
        .card h2 {
            margin-top: 0;
            margin-bottom: 1.5rem;
            font-size: 1.25rem;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 0.5rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        label {
            display: block;
            margin-bottom: 0.35rem;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }
        input, textarea, select {
            width: 100%;
            padding: 0.6rem 0.8rem;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            color: var(--text-primary);
            font-size: 0.95rem;
            box-sizing: border-box;
            font-family: inherit;
        }
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: var(--accent);
        }
        textarea {
            resize: vertical;
            min-height: 100px;
        }
        .btn {
            display: inline-block;
            padding: 0.6rem 1.2rem;
            background: linear-gradient(135deg, #ff6b35 0%, #ff9f1c 100%);
            border: none;
            border-radius: 6px;
            color: #fff;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
        }
        .btn-secondary {
            background: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-primary);
        }
        .alert {
            padding: 0.75rem 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid #10b981;
            color: #10b981;
        }
        .alert-danger {
            background: rgba(244, 63, 94, 0.15);
            border: 1px solid #f43f5e;
            color: #f43f5e;
        }
        .list-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        .list-item:last-child {
            border-bottom: none;
        }
        .item-info h3 {
            margin: 0 0 0.25rem 0;
            font-size: 1.05rem;
        }
        .item-info p {
            margin: 0;
            font-size: 0.8rem;
            color: var(--text-secondary);
        }
        .item-actions {
            display: flex;
            gap: 0.5rem;
        }
        .btn-edit {
            background: #00b4d8;
            color: #fff;
            padding: 0.4rem 0.8rem;
            font-size: 0.8rem;
            border-radius: 4px;
        }
        .btn-delete {
            background: #f43f5e;
            color: #fff;
            padding: 0.4rem 0.8rem;
            font-size: 0.8rem;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="header-bar">
        <h1>ระบบหลังบ้าน <span>Real Fishing Thailand</span></h1>
        <a href="?logout" class="logout-link">ออกจากระบบ</a>
    </div>

    <!-- แถบเลือกสลับเมนู จัดการบทความ / จัดการสินค้า -->
    <div class="tabs-container">
        <a href="index.php?tab=blogs" class="tab-btn <?php echo $current_tab == 'blogs' ? 'active' : ''; ?>">จัดการบทความ (Blogs)</a>
        <a href="index.php?tab=products" class="tab-btn <?php echo $current_tab == 'products' ? 'active' : ''; ?>">จัดการสินค้าแนะนำ (Products)</a>
    </div>

    <div class="container">
        
        <!-- แท็บที่ 1: ส่วนบทความ -->
        <?php if ($current_tab == 'blogs'): ?>
            <!-- ฝั่งซ้าย: ฟอร์มบทความ -->
            <div class="card">
                <h2><?php echo $edit_data ? 'แก้ไขบทความ' : 'เขียนบทความใหม่'; ?></h2>
                
                <?php if (!empty($success_msg)): ?>
                    <div class="alert alert-success"><?php echo $success_msg; ?></div>
                <?php endif; ?>
                <?php if (!empty($error_msg)): ?>
                    <div class="alert alert-danger"><?php echo $error_msg; ?></div>
                <?php endif; ?>

                <form method="POST" action="index.php?tab=blogs">
                    <input type="hidden" name="action" value="<?php echo $edit_data ? 'edit' : 'add'; ?>">
                    <?php if ($edit_data): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label>หัวข้อบทความ (Title)</label>
                        <input type="text" name="title" value="<?php echo $edit_data ? htmlspecialchars($edit_data['title']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label>สลักลิงก์ย่อ (Slug - ห้ามซ้ำและห้ามเว้นวรรค เช่น pe-line-guide)</label>
                        <input type="text" name="slug" value="<?php echo $edit_data ? htmlspecialchars($edit_data['slug']) : ''; ?>" placeholder="เช่น pe-line-guide">
                    </div>

                    <div class="form-group">
                        <label>คำอธิบายย่อสำหรับการ์ด (Short Description)</label>
                        <textarea name="description" required><?php echo $edit_data ? htmlspecialchars($edit_data['description']) : ''; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>เนื้อหาบทความทั้งหมด (Content - รองรับ HTML หรือ Markdown)</label>
                        <textarea name="content" style="min-height: 250px;" required><?php echo $edit_data ? htmlspecialchars($edit_data['content']) : ''; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>ภาพหน้าปก (Image URL)</label>
                        <input type="text" name="heroImage" value="<?php echo $edit_data ? htmlspecialchars($edit_data['heroImage']) : '/images/fishing_reel.png'; ?>" required>
                    </div>

                    <div class="form-group">
                        <label>ผู้เขียน (Author)</label>
                        <input type="text" name="author" value="<?php echo $edit_data ? htmlspecialchars($edit_data['author']) : 'เรียวฟิชชิ่ง'; ?>" required>
                    </div>

                    <div class="form-group">
                        <label>แท็กคำค้นหา (Tags - แยกด้วยจุลภาค เช่น รอกตกปลา,มือใหม่)</label>
                        <input type="text" name="tags" value="<?php echo $edit_data ? htmlspecialchars($edit_data['tags']) : 'อุปกรณ์ตกปลา'; ?>" required>
                    </div>

                    <div class="form-group">
                        <label>เวลาที่ใช้อ่าน (Read Time)</label>
                        <input type="text" name="readTime" value="<?php echo $edit_data ? htmlspecialchars($edit_data['readTime']) : '5 นาที'; ?>" required>
                    </div>

                    <button type="submit" class="btn"><?php echo $edit_data ? 'บันทึกการแก้ไข' : 'บันทึกบทความ'; ?></button>
                    <?php if ($edit_data): ?>
                        <a href="index.php?tab=blogs" class="btn btn-secondary" style="margin-left: 0.5rem;">ยกเลิก</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- ฝั่งขวา: รายการบทความ -->
            <div class="card">
                <h2>รายการบทความทั้งหมด</h2>
                <div class="list-container">
                    <?php if ($blogs && $blogs->num_rows > 0): ?>
                        <?php while($row = $blogs->fetch_assoc()): ?>
                            <div class="list-item">
                                <div class="item-info">
                                    <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                                    <p>สลัก: <code>/blog/<?php echo htmlspecialchars($row['slug']); ?></code> | โดย: <?php echo htmlspecialchars($row['author']); ?></p>
                                </div>
                                <div class="item-actions">
                                    <a href="index.php?tab=blogs&edit_id=<?php echo $row['id']; ?>" class="btn-edit" style="text-decoration: none;">แก้ไข</a>
                                    <a href="index.php?tab=blogs&delete=<?php echo $row['id']; ?>" class="btn-delete" style="text-decoration: none;" onclick="return confirm('คุณต้องการลบบทความนี้ใช่หรือไม่?')">ลบ</a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="text-align: center; color: var(--text-secondary);">ยังไม่มีบทความในระบบ</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- แท็บที่ 2: ส่วนสินค้า -->
        <?php if ($current_tab == 'products'): ?>
            <!-- ฝั่งซ้าย: ฟอร์มสินค้า -->
            <div class="card">
                <h2><?php echo $edit_product_data ? 'แก้ไขสินค้า' : 'เพิ่มสินค้าใหม่'; ?></h2>
                
                <?php if (!empty($success_msg)): ?>
                    <div class="alert alert-success"><?php echo $success_msg; ?></div>
                <?php endif; ?>
                <?php if (!empty($error_msg)): ?>
                    <div class="alert alert-danger"><?php echo $error_msg; ?></div>
                <?php endif; ?>

                <form method="POST" action="index.php?tab=products">
                    <input type="hidden" name="action" value="<?php echo $edit_product_data ? 'edit_product' : 'add_product'; ?>">
                    <?php if ($edit_product_data): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_product_data['id']; ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label>รหัสสินค้า (Product ID - เช่น prod-001)</label>
                        <input type="text" name="prod_id" value="<?php echo $edit_product_data ? htmlspecialchars($edit_product_data['prod_id']) : ''; ?>" placeholder="เช่น prod-005" required>
                    </div>

                    <div class="form-group">
                        <label>ชื่อสินค้า (Product Name)</label>
                        <input type="text" name="name" value="<?php echo $edit_product_data ? htmlspecialchars($edit_product_data['name']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label>ราคาสินค้า (Price - ตัวเลขบาท เช่น 1500)</label>
                        <input type="number" name="price" value="<?php echo $edit_product_data ? $edit_product_data['price'] : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label>คำอธิบายสินค้า (Description)</label>
                        <textarea name="description" required><?php echo $edit_product_data ? htmlspecialchars($edit_product_data['description']) : ''; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>ที่อยู่ลิงก์รูปภาพสินค้า (Image URL)</label>
                        <input type="text" name="image" value="<?php echo $edit_product_data ? htmlspecialchars($edit_product_data['image']) : '/images/fishing_reel.png'; ?>" required>
                    </div>

                    <div class="form-group">
                        <label>ป้ายสถานะสินค้า (Badge - เช่น แนะนำ, ขายดี, สินค้าใหม่, ว่างไว้ได้)</label>
                        <input type="text" name="badge" value="<?php echo $edit_product_data ? htmlspecialchars($edit_product_data['badge']) : ''; ?>" placeholder="เช่น แนะนำ">
                    </div>

                    <div class="form-group">
                        <label>หมวดหมู่สินค้า (Category)</label>
                        <select name="category">
                            <option value="รอกตกปลา" <?php echo ($edit_product_data && $edit_product_data['category'] == 'รอกตกปลา') ? 'selected' : ''; ?>>รอกตกปลา</option>
                            <option value="คันเบ็ด" <?php echo ($edit_product_data && $edit_product_data['category'] == 'คันเบ็ด') ? 'selected' : ''; ?>>คันเบ็ด</option>
                            <option value="เหยื่อปลอม" <?php echo ($edit_product_data && $edit_product_data['category'] == 'เหยื่อปลอม') ? 'selected' : ''; ?>>เหยื่อปลอม</option>
                            <option value="สายเบ็ด" <?php echo ($edit_product_data && $edit_product_data['category'] == 'สายเบ็ด') ? 'selected' : ''; ?>>สายเบ็ด</option>
                            <option value="อุปกรณ์เสริม" <?php echo ($edit_product_data && $edit_product_data['category'] == 'อุปกรณ์เสริม') ? 'selected' : ''; ?>>อุปกรณ์เสริม</option>
                        </select>
                    </div>

                    <button type="submit" class="btn"><?php echo $edit_product_data ? 'บันทึกการแก้ไขสินค้า' : 'เพิ่มสินค้า'; ?></button>
                    <?php if ($edit_product_data): ?>
                        <a href="index.php?tab=products" class="btn btn-secondary" style="margin-left: 0.5rem;">ยกเลิก</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- ฝั่งขวา: รายการสินค้า -->
            <div class="card">
                <h2>รายการสินค้าแนะนำทั้งหมด</h2>
                <div class="list-container">
                    <?php if ($products && $products->num_rows > 0): ?>
                        <?php while($row = $products->fetch_assoc()): ?>
                            <div class="list-item">
                                <div class="item-info">
                                    <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                                    <p>รหัส: <code><?php echo htmlspecialchars($row['prod_id']); ?></code> | ราคา: ฿<?php echo number_format($row['price']); ?> | หมวดหมู่: <?php echo htmlspecialchars($row['category']); ?></p>
                                </div>
                                <div class="item-actions">
                                    <a href="index.php?tab=products&edit_prod_id=<?php echo $row['id']; ?>" class="btn-edit" style="text-decoration: none;">แก้ไข</a>
                                    <a href="index.php?tab=products&delete_product=<?php echo $row['id']; ?>" class="btn-delete" style="text-decoration: none;" onclick="return confirm('คุณต้องการลบสินค้าชิ้นนี้ใช่หรือไม่?')">ลบ</a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="text-align: center; color: var(--text-secondary);">ยังไม่มีสินค้าในฐานข้อมูล</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>
