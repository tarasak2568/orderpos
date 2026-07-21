<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// เชื่อมต่อฐานข้อมูลโดยใช้ไฟล์ db_config.php
$config_file = '../admin/db_config.php';
if (!file_exists($config_file)) {
    echo json_encode(["error" => "Database config file not found. Please setup database first at /admin/"]);
    exit;
}
include $config_file;

$conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}
$conn->set_charset("utf8mb4");

// ค้นหาข้อมูลบทความทั้งหมด
$sql = "SELECT * FROM `astroyu_blogs` ORDER BY `pubDate` DESC";
$result = $conn->query($sql);

$blogs = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // จัดโครงสร้างให้เป็นมิตรกับ JS
        $row['id'] = (int)$row['id'];
        
        // แยกแท็กออกเป็นอาร์เรย์
        $tags_str = $row['tags'];
        $row['tags'] = array_map('trim', explode(',', $tags_str));
        
        $blogs[] = $row;
    }
}

echo json_encode($blogs, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
