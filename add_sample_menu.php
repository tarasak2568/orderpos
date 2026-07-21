<?php
$DB_HOST = 'localhost';
$DB_USER = 'u262149987_orderpos_db';
$DB_PASS = '&bhfk*0f1M8';
$DB_NAME = 'u262149987_orderpos_db';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    die("ERROR: " . $conn->connect_error);
}

echo "<h2>เพิ่มข้อมูลเมนูตัวอย่าง</h2>";

// Sample menu items
$menu_items = [
    [1, 'ข้าวผัดกระเพราหมูกรอบ', 'ข้าวผัดกระเพราหมูกรอบ ไข่ดาว', 65, 1],
    [1, 'ต้มยำกุ้งน้ำข้น', 'ต้มยำกุ้งน้ำข้น ใส่กุ้งสด เห็ดฟาง', 120, 1],
    [2, 'ข้าวมันไก่ต้ม', 'ข้าวมันไก่ต้ม น้ำจิ้มเต้าเจี้ยว', 55, 0],
    [2, 'ข้าวหมูแดง', 'ข้าวหมูแดง หมูกรอบ ไข่ต้ม', 60, 0],
    [3, 'ผัดผักรวมมิตร', 'ผัดผักรวมมิตรใส่กุ้ง', 80, 0],
    [3, 'แกงเขียวหวานไก่', 'แกงเขียวหวานไก่ ใส่ถั่วฝักยาว', 70, 0],
    [4, 'น้ำเปล่า', 'น้ำดื่ม 600ml', 10, 0],
    [4, 'โค้ก', 'โค้ก กระป๋อง 325ml', 20, 0],
    [4, 'ชาเขียว', 'ชาเขียวเย็น หวานน้อย', 35, 0],
    [5, 'ไอศครีมกะทิ', 'ไอศครีมกะทิสด ใส่ถั่วแดง', 45, 0],
    [5, 'บัวลอย', 'บัวลอยไข่หวาน น้ำกะทิ', 40, 0],
];

$added = 0;
$skipped = 0;

foreach ($menu_items as $item) {
    list($cat_id, $name, $desc, $price, $recommended) = $item;
    
    // Check if already exists
    $check = $conn->query("SELECT id FROM menu_items WHERE name = '" . $conn->real_escape_string($name) . "'");
    if ($check->num_rows > 0) {
        $skipped++;
        continue;
    }
    
    $sql = "INSERT INTO menu_items (category_id, name, description, price, is_available, is_recommended) 
            VALUES ($cat_id, '" . $conn->real_escape_string($name) . "', '" . $conn->real_escape_string($desc) . "', $price, 1, $recommended)";
    
    if ($conn->query($sql)) {
        $added++;
    } else {
        echo "<p style='color:red;'>❌ Error adding '$name': " . $conn->error . "</p>";
    }
}

echo "<p style='color:green;'>✅ เพิ่มเมนูใหม่: $added รายการ</p>";
echo "<p style='color:gray;'>⚠️ ข้ามเมนูที่มีอยู่แล้ว: $skipped รายการ</p>";

// Verify
$result = $conn->query("SELECT COUNT(*) as total FROM menu_items");
$total = $result->fetch_assoc();
echo "<p>📊 จำนวนเมนูทั้งหมด: {$total['total']} รายการ</p>";

echo "<hr>";
echo "<p><a href='login.php' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>ไปหน้า Login</a></p>";

$conn->close();
?>