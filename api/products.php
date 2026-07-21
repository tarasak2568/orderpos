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

// ค้นหาข้อมูลสินค้าทั้งหมด
$sql = "SELECT * FROM `astroyu_products` ORDER BY `created_at` DESC";
$result = $conn->query($sql);

$products = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $row['id'] = (int)$row['id'];
        $row['price'] = (int)$row['price'];
        $products[] = $row;
    }
}

echo json_encode($products, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
