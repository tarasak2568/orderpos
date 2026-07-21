<?php
/**
 * cleanup.php - ล้างข้อมูลซ้ำในฐานข้อมูล
 * เรียกใช้ครั้งเดียวแล้วลบทิ้ง
 */
require_once '../config/db.php';
header('Content-Type: text/html; charset=utf-8');

echo "<h2>ล้างข้อมูลซ้ำ</h2>";

// ลบเมนูซ้ำ - เก็บไว้เฉพาะ id ที่เล็กที่สุดสำหรับแต่ละชื่อ
$result = query("
    DELETE m1 FROM menu_items m1
    INNER JOIN menu_items m2 
    WHERE m1.name = m2.name 
    AND m1.category_id = m2.category_id 
    AND m1.id > m2.id
");

global $conn;
echo "<p>✅ ลบเมนูซ้ำ: " . $conn->affected_rows . " รายการ</p>";

// ลบเมนู Test
$result2 = query("DELETE FROM menu_items WHERE name LIKE 'Test Menu%'");
echo "<p>✅ ลบเมนู Test: " . $conn->affected_rows . " รายการ</p>";

// ลบหมวดหมู่ซ้ำ - เก็บไว้เฉพาะ id ที่เล็กที่สุด
$result3 = query("
    DELETE c1 FROM categories c1
    INNER JOIN categories c2 
    WHERE c1.name = c2.name 
    AND c1.id > c2.id
");
echo "<p>✅ ลบหมวดหมู่ซ้ำ: " . $conn->affected_rows . " รายการ</p>";

// ลบออเดอร์ทดสอบ
$result4 = query("DELETE FROM orders WHERE note = 'Test Note'");
echo "<p>✅ ลบออเดอร์ทดสอบ: " . $conn->affected_rows . " รายการ</p>";

// รีเซ็ตสถานะโต๊ะทั้งหมด
$result5 = query("UPDATE tables SET status = 'available'");
echo "<p>✅ รีเซ็ตสถานะโต๊ะ: " . $conn->affected_rows . " โต๊ะ</p>";

// นับจำนวนเมนู
$countResult = query("SELECT COUNT(*) as cnt FROM menu_items");
$count = fetchOne($countResult);
echo "<p>📊 เมนูที่เหลือ: " . $count['cnt'] . " รายการ</p>";

echo "<hr><p><a href='../admin/dashboard.php'>กลับสู่ Dashboard</a></p>";
?>
