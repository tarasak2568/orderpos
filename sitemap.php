<?php
require_once 'config/db.php';
require_once 'config/seo.php';

header('Content-Type: application/xml; charset=utf-8');

$baseUrl = getBaseSiteUrl();
$today = date('c');

$urls = [
    [
        'loc' => $baseUrl . '/',
        'lastmod' => $today,
        'changefreq' => 'daily',
        'priority' => '1.0'
    ],
    [
        'loc' => $baseUrl . '/login.php',
        'lastmod' => $today,
        'changefreq' => 'weekly',
        'priority' => '0.8'
    ],
    [
        'loc' => $baseUrl . '/register.php',
        'lastmod' => $today,
        'changefreq' => 'weekly',
        'priority' => '0.9'
    ]
];

// 1. ดึงรายชื่อร้านค้าสมาชิกที่เปิดใช้งานอยู่ทั้งหมด (Dynamic Shop URLs)
$shopsResult = query("SELECT shop_slug, updated_at, created_at FROM shops WHERE is_active = 1 ORDER BY id ASC");
$shops = fetchAll($shopsResult);

foreach ($shops as $s) {
    $slug = trim($s['shop_slug'] ?? '');
    if ($slug === '') continue;
    $lastModRaw = !empty($s['updated_at']) ? $s['updated_at'] : ($s['created_at'] ?? 'now');
    $lastMod = date('c', strtotime($lastModRaw));

    $urls[] = [
        'loc' => $baseUrl . '/shop/' . rawurlencode($slug),
        'lastmod' => $lastMod,
        'changefreq' => 'daily',
        'priority' => '0.9'
    ];
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($urls as $u): ?>
    <url>
        <loc><?= htmlspecialchars($u['loc'], ENT_XML1, 'UTF-8') ?></loc>
        <lastmod><?= htmlspecialchars($u['lastmod'], ENT_XML1, 'UTF-8') ?></lastmod>
        <changefreq><?= htmlspecialchars($u['changefreq'], ENT_XML1, 'UTF-8') ?></changefreq>
        <priority><?= htmlspecialchars($u['priority'], ENT_XML1, 'UTF-8') ?></priority>
    </url>
<?php endforeach; ?>
</urlset>
