<?php
require_once __DIR__ . '/saas.php';
/**
 * โมดูลจัดการ Dynamic SEO, Open Graph, Twitter Card และ JSON-LD Schema.org
 * สำหรับแพลตฟอร์ม OrderPOS SaaS และหน้าร้านค้าสมาชิกแต่ละร้าน
 */

function getBaseSiteUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $protocol . '://' . $host;
}

/**
 * สร้าง HTML Tags สำหรับ SEO ใน <head> แบบไดนามิก
 *
 * @param array $options [
 *   'type' => 'platform' | 'restaurant' | 'noindex',
 *   'title' => string,
 *   'description' => string,
 *   'keywords' => string,
 *   'canonical' => string,
 *   'image' => string,
 *   'shop' => array|null,
 *   'menu_items' => array,
 *   'categories' => array
 * ]
 */
function renderDynamicSeoTags(array $options = []) {
    $baseUrl = getBaseSiteUrl();
    $type = $options['type'] ?? 'platform';
    $shop = $options['shop'] ?? null;
    $menuItems = $options['menu_items'] ?? [];
    $categories = $options['categories'] ?? [];

    // ค่าเริ่มต้นสำหรับแพลตฟอร์ม OrderPOS SaaS
    $title = $options['title'] ?? 'OrderPOS - ระบบ POS ร้านอาหารออนไลน์ & สั่งอาหารผ่าน QR Code ฟรี';
    $description = $options['description'] ?? 'บริการระบบจัดการร้านอาหารออนไลน์ (Cloud POS SaaS) สมัครสมาชิกเปิดร้านฟรี ตั้งชื่อร้าน สร้าง QR Code สั่งอาหารประจำโต๊ะ ระบบจอห้องครัว และคิดเงินครบวงจร';
    $keywords = $options['keywords'] ?? 'ระบบ POS ร้านอาหาร, สั่งอาหารผ่าน QR Code, โปรแกรมร้านอาหารฟรี, ระบบจัดการร้านอาหารออนไลน์, OrderPOS';
    $canonical = $options['canonical'] ?? ($baseUrl . strtok($_SERVER['REQUEST_URI'] ?? '/', '?'));
    $image = $options['image'] ?? ($baseUrl . '/favicon.svg');
    $siteName = 'OrderPOS Cloud Restaurant Platform';
    $ogType = 'website';

    // กรณีเป็นหน้าร้านค้าสมาชิก (Dynamic Restaurant SEO)
    if ($type === 'restaurant' && $shop) {
        $shopName = trim($shop['shop_name'] ?? 'ร้านอาหาร');
        $shopSlug = trim($shop['shop_slug'] ?? 'main-shop');
        $siteName = $shopName . ' | OrderPOS';
        $ogType = 'restaurant.restaurant';

        // 1. Dynamic Title
        if (!empty($shop['seo_title'])) {
            $title = $shop['seo_title'];
        } else {
            $title = $shopName . ' - สั่งอาหารออนไลน์ & เมนูแนะนำ | OrderPOS';
        }

        // 2. Dynamic Description (ดึงจาก seo_description หรือ description หรือสร้างอัตโนมัติจากรายการเมนูจริง)
        if (!empty($shop['seo_description'])) {
            $description = $shop['seo_description'];
        } elseif (!empty($shop['description'])) {
            $description = $shop['description'];
        } else {
            $sampleNames = [];
            foreach (array_slice($menuItems, 0, 5) as $mi) {
                if (!empty($mi['name'])) {
                    $sampleNames[] = $mi['name'] . ' (' . number_format((float)$mi['price'], 0) . '฿)';
                }
            }
            $menuHighlight = !empty($sampleNames) ? ' เมนูแนะนำ: ' . implode(', ', $sampleNames) : '';
            $description = 'สั่งอาหารออนไลน์ร้าน ' . $shopName . ' ผ่านระบบ QR Code สะดวก รวดเร็ว' . $menuHighlight;
        }

        // 3. Dynamic Keywords
        if (!empty($shop['seo_keywords'])) {
            $keywords = $shop['seo_keywords'];
        } else {
            $catNames = array_map(function($c) { return $c['name'] ?? ''; }, array_slice($categories, 0, 5));
            $catStr = !empty($catNames) ? ', ' . implode(', ', array_filter($catNames)) : '';
            $keywords = $shopName . ', เมนูร้าน ' . $shopName . ', สั่งอาหาร ' . $shopName . $catStr . ', OrderPOS';
        }

        // 4. Canonical URL ของร้านค้า
        $canonical = $options['canonical'] ?? ($baseUrl . '/shop/' . rawurlencode($shopSlug));

        // 5. รูปภาพ Open Graph ของร้าน
        if (!empty($shop['logo_image'])) {
            $image = (strpos($shop['logo_image'], 'http') === 0)
                ? $shop['logo_image']
                : $baseUrl . '/' . ltrim($shop['logo_image'], '/');
        }
    }

    $robots = ($type === 'noindex')
        ? 'noindex, nofollow'
        : 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';

    $escTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $escDesc = htmlspecialchars($description, ENT_QUOTES, 'UTF-8');
    $escKeywords = htmlspecialchars($keywords, ENT_QUOTES, 'UTF-8');
    $escCanonical = htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8');
    $escImage = htmlspecialchars($image, ENT_QUOTES, 'UTF-8');
    $escSiteName = htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8');

    $html = <<<HTML
    <!-- Dynamic SEO Directives -->
    <title>{$escTitle}</title>
    <meta name="description" content="{$escDesc}">
    <meta name="keywords" content="{$escKeywords}">
    <meta name="robots" content="{$robots}">
    <link rel="canonical" href="{$escCanonical}">

    <!-- Open Graph / Facebook / LINE -->
    <meta property="og:type" content="{$ogType}">
    <meta property="og:url" content="{$escCanonical}">
    <meta property="og:title" content="{$escTitle}">
    <meta property="og:description" content="{$escDesc}">
    <meta property="og:image" content="{$escImage}">
    <meta property="og:site_name" content="{$escSiteName}">
    <meta property="og:locale" content="th_TH">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{$escCanonical}">
    <meta name="twitter:title" content="{$escTitle}">
    <meta name="twitter:description" content="{$escDesc}">
    <meta name="twitter:image" content="{$escImage}">
HTML;

    // สร้าง JSON-LD Schema.org สำหรับหน้าสาธารณะ
    if ($type === 'restaurant' && $shop) {
        $schema = buildRestaurantJsonLd($shop, $menuItems, $categories, $canonical, $image);
        $jsonLd = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        $html .= "\n    <script type=\"application/ld+json\">\n" . $jsonLd . "\n    </script>\n";
    } elseif ($type === 'platform') {
        $schema = buildPlatformJsonLd($title, $description, $canonical, $image);
        $jsonLd = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        $html .= "\n    <script type=\"application/ld+json\">\n" . $jsonLd . "\n    </script>\n";
    }

    return $html;
}

/**
 * สร้าง JSON-LD Schema ประเภท Restaurant + Menu สำหรับหน้าร้านค้าสมาชิก
 */
function buildRestaurantJsonLd(array $shop, array $menuItems, array $categories, string $url, string $image) {
    $prices = array_map(function($item) {
        return floatval($item['price'] ?? 0);
    }, $menuItems);
    $prices = array_filter($prices, function($p) { return $p > 0; });

    $priceRange = '฿฿';
    if (!empty($prices)) {
        $minPrice = number_format(min($prices), 0);
        $maxPrice = number_format(max($prices), 0);
        $priceRange = "฿{$minPrice} - ฿{$maxPrice}";
    }

    // จัดกลุ่มรายการอาหารตามหมวดหมู่เพื่อทำ MenuSection
    $menuSections = [];
    foreach ($categories as $cat) {
        $catId = intval($cat['id'] ?? 0);
        $catItems = array_filter($menuItems, function($mi) use ($catId) {
            return intval($mi['category_id'] ?? 0) === $catId;
        });

        if (empty($catItems)) continue;

        $schemaItems = [];
        foreach (array_slice($catItems, 0, 15) as $mi) {
            $schemaItems[] = [
                '@type' => 'MenuItem',
                'name' => $mi['name'],
                'description' => !empty($mi['description']) ? $mi['description'] : $mi['name'],
                'offers' => [
                    '@type' => 'Offer',
                    'price' => number_format((float)$mi['price'], 2, '.', ''),
                    'priceCurrency' => 'THB',
                    'availability' => 'https://schema.org/InStock'
                ]
            ];
        }

        $menuSections[] = [
            '@type' => 'MenuSection',
            'name' => $cat['name'],
            'hasMenuItem' => $schemaItems
        ];
    }

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Restaurant',
        'name' => $shop['shop_name'] ?? 'ร้านอาหาร',
        'description' => !empty($shop['description']) ? $shop['description'] : ('สั่งอาหารออนไลน์ร้าน ' . ($shop['shop_name'] ?? '')),
        'url' => $url,
        'image' => $image,
        'priceRange' => $priceRange,
        'servesCuisine' => 'Thai',
        'acceptsReservations' => 'True'
    ];

    if (!empty($shop['phone'])) {
        $schema['telephone'] = $shop['phone'];
    }

    if (!empty($shop['address'])) {
        $schema['address'] = [
            '@type' => 'PostalAddress',
            'streetAddress' => $shop['address'],
            'addressCountry' => 'TH'
        ];
    }

    if (!empty($menuSections)) {
        $schema['hasMenu'] = [
            '@type' => 'Menu',
            'name' => 'เมนูอาหารร้าน ' . ($shop['shop_name'] ?? ''),
            'hasMenuSection' => $menuSections
        ];
    }

    return $schema;
}

/**
 * สร้าง JSON-LD Schema ประเภท SoftwareApplication สำหรับแพลตฟอร์ม OrderPOS SaaS
 */
function buildPlatformJsonLd(string $title, string $description, string $url, string $image) {
    return [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'OrderPOS',
        'headline' => $title,
        'description' => $description,
        'url' => $url,
        'image' => $image,
        'applicationCategory' => 'BusinessApplication',
        'operatingSystem' => 'Web Browser, iOS, Android',
        'offers' => [
            '@type' => 'Offer',
            'price' => '0',
            'priceCurrency' => 'THB',
            'description' => 'สมัครสมาชิกเปิดร้านอาหารและใช้งานระบบ POS ฟรี'
        ]
    ];
}
?>
