<?php
header('Content-Type: application/json; charset=utf-8');

require_once '../config/db.php';

try {
    $result = query("SELECT 1 as test");
    $row = fetchOne($result);
    
    echo json_encode([
        'success' => true,
        'message' => 'Database connection OK',
        'data' => $row
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>