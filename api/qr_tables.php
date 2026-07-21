<?php
require_once '../config/db.php';
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Generate QR codes for all tables
        $result = query("SELECT * FROM tables ORDER BY table_code ASC");
        $tables = fetchAll($result);
        
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
        
        foreach ($tables as &$table) {
            $menuUrl = $baseUrl . '/menu.php?table_id=' . $table['table_code'];
            $qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($menuUrl);
            $table['qr_url'] = $menuUrl;
            $table['qr_image'] = $qrApiUrl;
            
            // Update QR code in database if not set
            if (empty($table['qr_code'])) {
                query("UPDATE tables SET qr_code='" . escape($menuUrl) . "' WHERE id=" . $table['id']);
            }
        }
        
        echo json_encode(['success' => true, 'data' => $tables], JSON_UNESCAPED_UNICODE);
        break;
        
    case 'POST':
        // Regenerate QR code for a specific table
        $input = json_decode(file_get_contents('php://input'), true);
        $id = intval($input['id'] ?? 0);
        
        if ($id > 0) {
            $result = query("SELECT * FROM tables WHERE id=$id");
            $table = fetchOne($result);
            
            if ($table) {
                $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
                $menuUrl = $baseUrl . '/menu.php?table_id=' . $table['table_code'];
                
                query("UPDATE tables SET qr_code='" . escape($menuUrl) . "' WHERE id=$id");
                
                echo json_encode([
                    'success' => true, 
                    'data' => [
                        'qr_url' => $menuUrl,
                        'qr_image' => 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($menuUrl)
                    ]
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['success' => false, 'error' => 'ไม่พบโต๊ะ'], JSON_UNESCAPED_UNICODE);
            }
        }
        break;
}
?>