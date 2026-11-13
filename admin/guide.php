<?php
require_once 'header.php';
require_once '../config/guide_content.php';

$shopSlug = (string)($currentShop['shop_slug'] ?? '');
$shopName = (string)($currentShop['shop_name'] ?? 'ร้านของคุณ');
$allowedTabs = ['customer', 'restaurant', 'retail', 'seo'];
$requestedTab = (string)($_GET['tab'] ?? ($isRetailShop ? 'retail' : 'restaurant'));
if (!in_array($requestedTab, $allowedTabs, true)) {
    $requestedTab = $isRetailShop ? 'retail' : 'restaurant';
}
?>

<div class="card" style="margin-bottom: 20px;">
    <div class="card-header">
        <div>
            <h3 style="margin: 0 0 4px 0;"><i class="fas fa-book-open" style="color: var(--primary);"></i> คู่มือการใช้งานระบบ — "<?= htmlspecialchars($shopName, ENT_QUOTES, 'UTF-8') ?>"</h3>
            <span style="font-size: 13.5px; color: var(--gray);">
                โหมดร้านค้าปัจจุบัน: <strong><?= $isRetailShop ? 'ร้านค้าปลีก / มินิมาร์ท (Retail)' : 'ร้านอาหาร & คาเฟ่ (Restaurant)' ?></strong>
                — คลิกปุ่มในแต่ละขั้นตอนเพื่อไปยังหน้าตั้งค่าได้ทันที หรือเปิดดูคู่มือสำหรับลูกค้าที่สแกน QR
            </span>
        </div>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <a href="../guide?tab=customer" target="_blank" class="btn btn-outline btn-sm">
                <i class="fas fa-share-nodes"></i> ลิงก์คู่มือสาธารณะ (/guide)
            </a>
            <?php if ($shopSlug !== ''): ?>
            <a href="../shop/<?= urlencode($shopSlug) ?>" target="_blank" class="btn btn-primary btn-sm">
                <i class="fas fa-external-link-alt"></i> เปิดหน้าร้านลูกค้า
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
renderOrderPosGuideWidget([
    'isAdmin' => true,
    'isRetail' => $isRetailShop,
    'shopSlug' => $shopSlug,
    'defaultTab' => $requestedTab,
]);
?>

<?php require_once 'footer.php'; ?>
