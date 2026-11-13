<?php
require_once '../config/db.php';
require_once '../config/saas.php';
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$shopId = currentShopId();
$shop = getCurrentShop($shopId);
$shopSlug = $shop['shop_slug'] ?? 'main-shop';

switch ($method) {
    case 'GET':
        $result = query("SELECT * FROM tables WHERE shop_id = $shopId ORDER BY table_code ASC");
        $tables = fetchAll($result);
        
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
        
        foreach ($tables as &$table) {
            // ลิงก์ QR Code แนบทั้ง shop_slug และ table_id เพื่อให้สแกนเข้าเมนูของร้านนั้นโดยตรง
            $menuUrl = $baseUrl . '/menu.php?shop=' . rawurlencode($shopSlug) . '&table_id=' . rawurlencode($table['table_code']);
            $qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($menuUrl);
            $table['qr_url'] = $menuUrl;
            $table['qr_image'] = $qrApiUrl;
            
            if ($table['qr_code'] !== $menuUrl) {
                query("UPDATE tables SET qr_code='" . escape($menuUrl) . "' WHERE id=" . intval($table['id']) . " AND shop_id=$shopId");
            }
        }
        
        echo json_encode(['success' => true, 'data' => $tables], JSON_UNESCAPED_UNICODE);
        break;
        
    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = intval($input['id'] ?? 0);
        
        if ($id > 0) {
            $result = query("SELECT * FROM tables WHERE id=$id AND shop_id=$shopId");
            $table = fetchOne($result);
            
            if ($table) {
                $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
                $menuUrl = $baseUrl . '/menu.php?shop=' . rawurlencode($shopSlug) . '&table_id=' . rawurlencode($table['table_code']);
                
                query("UPDATE tables SET qr_code='" . escape($menuUrl) . "' WHERE id=$id AND shop_id=$shopId");
                
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
