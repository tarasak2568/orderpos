<?php
require_once __DIR__ . '/saas.php';
/**
 * โมดูลจัดการ Dynamic SEO, Open Graph, Twitter Card และ JSON-LD Schema.org
 * สำหรับแพลตฟอร์ม OrderPOS SaaS และหน้าร้านค้าสมาชิก (ร้านอาหาร / คาเฟ่ / ร้านของชำ / ค้าปลีก)
 */

function getBaseSiteUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $protocol . '://' . $host;
}

/**
 * ตัดข้อความ UTF-8 อย่างปลอดภัยสำหรับ Meta Description (รองรับแม้ไม่มี mbstring)
 */
function seoTruncateText(string $text, int $maxChars = 160, string $suffix = '...'): string {
    $clean = trim(preg_replace('/\s+/u', ' ', strip_tags($text)));
    if ($clean === '') {
        return '';
    }
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($clean, 'UTF-8') <= $maxChars) {
            return $clean;
        }
        $cut = max(1, $maxChars - mb_strlen($suffix, 'UTF-8'));
        return rtrim(mb_substr($clean, 0, $cut, 'UTF-8')) . $suffix;
    }
    $chars = preg_split('//u', $clean, -1, PREG_SPLIT_NO_EMPTY);
    if (!$chars || count($chars) <= $maxChars) {
        return $clean;
    }
    $cut = max(1, $maxChars - strlen($suffix));
    return rtrim(implode('', array_slice($chars, 0, $cut))) . $suffix;
}

/**
 * จัดชุดคีย์เวิร์ดไม่ให้เกินขนาดคอลัมน์ seo_keywords (VARCHAR(255)) โดยตัดที่ขอบคีย์เวิร์ดพอดี
 */
function seoJoinKeywordsWithinLimit(array $keywords, int $maxChars = 250): string {
    $unique = [];
    $seen = [];
    foreach ($keywords as $kw) {
        $kw = trim($kw);
        if ($kw === '' || isset($seen[$kw])) {
            continue;
        }
        $seen[$kw] = true;
        $unique[] = $kw;
    }

    $result = '';
    foreach ($unique as $kw) {
        $candidate = $result === '' ? $kw : ($result . ', ' . $kw);
        $len = function_exists('mb_strlen')
            ? mb_strlen($candidate, 'UTF-8')
            : count(preg_split('//u', $candidate, -1, PREG_SPLIT_NO_EMPTY) ?: []);
        if ($len > $maxChars) {
            break;
        }
        $result = $candidate;
    }
    return $result;
}

/**
 * ชุดคีย์เวิร์ด SEO คุณภาพสูงแยกตามกลุ่มธุรกิจ (High-Intent Buyer & Local Search Keywords)
 */
function getOrderPosKeywordSets(): array {
    return [
        'platform' => 'ระบบ POS ออนไลน์, ระบบ POS ร้านอาหาร, โปรแกรมขายหน้าร้านฟรี, ระบบร้านของชำ, โปรแกรมมินิมาร์ท, สั่งอาหารผ่าน QR Code, ระบบสแกนบาร์โค้ดสินค้า, ตัดสต๊อกสินค้าออนไลน์, จอห้องครัว KDS, รับเงินพร้อมเพย์ POS, OrderPOS',
        'restaurant' => 'สั่งอาหารออนไลน์, เมนูอาหารแนะนำ, สแกน QR สั่งอาหาร, ร้านอาหารใกล้ฉัน, สั่งอาหารไม่ต่อคิว, จองโต๊ะล่วงหน้า, คาเฟ่และเครื่องดื่ม, รับเงินพร้อมเพย์',
        'retail' => 'ร้านของชำออนไลน์, มินิมาร์ทใกล้ฉัน, ของใช้ประจำวันราคาประหยัด, สินค้าอุปโภคบริโภคครบครัน, ซื้อของชำใกล้บ้าน, สั่งซื้อสินค้าออนไลน์, ร้านค้าปลีกคุณภาพ'
    ];
}

/**
 * สร้างชุด SEO Title, Meta Description และ Keywords ที่พร้อมใช้งานจริงให้ร้านค้าโดยอัตโนมัติ
 */
function generateShopSeoPreset(
    string $shopName,
    string $shopType = 'restaurant',
    array $categoryNames = [],
    array $topItemNames = []
): array {
    $cleanName = trim($shopName) !== '' ? trim($shopName) : 'ร้านค้าของเรา';
    $isRetail = ($shopType === 'retail');
    $kwSets = getOrderPosKeywordSets();

    $cats = array_values(array_filter(array_map('trim', $categoryNames)));
    $items = array_values(array_filter(array_map('trim', $topItemNames)));

    if ($isRetail) {
        $seoTitle = seoTruncateText("{$cleanName} | ร้านของชำ & สั่งซื้อสินค้าออนไลน์", 180, '');
        $storefrontDesc = "ครบทุกของใช้ประจำวัน เครื่องดื่ม และสินค้าอุปโภคบริโภคที่ {$cleanName} คัดสรรคุณภาพ ราคาเป็นกันเอง คิดเงินไว รองรับพร้อมเพย์";
        $itemSnippet = !empty($items) ? ' สินค้ายอดนิยม: ' . implode(', ', array_slice($items, 0, 4)) : '';
        $seoDesc = seoTruncateText(
            "ช้อปสินค้าอุปโภคบริโภค ของใช้ประจำวัน และเครื่องดื่มที่ {$cleanName} สั่งซื้อง่าย เช็กสต๊อกไว รองรับจ่ายพร้อมเพย์{$itemSnippet}",
            160
        );
        $baseKeywords = array_merge(
            [$cleanName, "สินค้า{$cleanName}", "ร้านของชำ{$cleanName}"],
            array_slice($cats, 0, 4),
            array_slice($items, 0, 3),
            array_map('trim', explode(',', $kwSets['retail']))
        );
        $seoKeywords = seoJoinKeywordsWithinLimit($baseKeywords, 245);
    } else {
        $seoTitle = seoTruncateText("{$cleanName} | เมนูแนะนำ & สแกนสั่งอาหารออนไลน์", 180, '');
        $storefrontDesc = "อิ่มอร่อยกับเมนูคัดสรรจากร้าน {$cleanName} ปรุงสดใหม่ทุกจาน สั่งง่ายผ่าน QR Code ส่งตรงเข้าครัวทันที รวดเร็วทันใจ";
        $itemSnippet = !empty($items) ? ' เมนูแนะนำ: ' . implode(', ', array_slice($items, 0, 4)) : '';
        $seoDesc = seoTruncateText(
            "ดูเมนูแนะนำและสั่งอาหารออนไลน์ร้าน {$cleanName} สแกน QR สั่งได้ทันที ส่งตรงถึงครัว สะดวก รวดเร็ว รองรับชำระเงินพร้อมเพย์{$itemSnippet}",
            160
        );
        $baseKeywords = array_merge(
            [$cleanName, "เมนู{$cleanName}", "ร้านอาหาร{$cleanName}"],
            array_slice($cats, 0, 4),
            array_slice($items, 0, 3),
            array_map('trim', explode(',', $kwSets['restaurant']))
        );
        $seoKeywords = seoJoinKeywordsWithinLimit($baseKeywords, 245);
    }

    return [
        'seo_title' => $seoTitle,
        'seo_description' => $seoDesc,
        'seo_keywords' => $seoKeywords,
        'storefront_description' => $storefrontDesc
    ];
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
    $kwSets = getOrderPosKeywordSets();

    // ค่าเริ่มต้นสำหรับแพลตฟอร์ม OrderPOS SaaS
    $title = $options['title'] ?? 'OrderPOS - ระบบ POS ร้านอาหาร & ร้านของชำออนไลน์ QR Menu ฟรี';
    $description = $options['description'] ?? 'เปิดร้านออนไลน์ฟรีด้วย OrderPOS Cloud POS ครบทั้งระบบสั่งอาหาร QR Code จอครัวร้านอาหาร และสแกนบาร์โค้ดตัดสต๊อกสำหรับร้านของชำ รองรับพร้อมเพย์ในระบบเดียว';
    $keywords = $options['keywords'] ?? $kwSets['platform'];
    $canonical = $options['canonical'] ?? ($baseUrl . strtok($_SERVER['REQUEST_URI'] ?? '/', '?'));
    $image = $options['image'] ?? ($baseUrl . '/favicon.svg');
    $siteName = 'OrderPOS Cloud POS Platform';
    $ogType = 'website';

    // กรณีเป็นหน้าร้านค้าสมาชิก (Dynamic Restaurant / Retail Store SEO)
    if ($type === 'restaurant' && $shop) {
        $shopName = trim($shop['shop_name'] ?? 'ร้านค้า');
        $shopSlug = trim($shop['shop_slug'] ?? 'main-shop');
        $shopType = $shop['shop_type'] ?? 'restaurant';
        $isRetail = ($shopType === 'retail');

        $catNames = array_map(function($c) { return $c['name'] ?? ''; }, array_slice($categories, 0, 5));
        $itemNames = array_map(function($mi) {
            return !empty($mi['name']) ? ($mi['name'] . ' (' . number_format((float)($mi['price'] ?? 0), 0) . '฿)') : '';
        }, array_slice($menuItems, 0, 5));
        $preset = generateShopSeoPreset($shopName, $shopType, $catNames, $itemNames);

        $siteName = $shopName . ' | OrderPOS';
        $ogType = $isRetail ? 'website' : 'restaurant.restaurant';

        // 1. Dynamic Title
        $title = !empty($shop['seo_title']) ? $shop['seo_title'] : $preset['seo_title'];

        // 2. Dynamic Description
        if (!empty($shop['seo_description'])) {
            $description = $shop['seo_description'];
        } elseif (!empty($shop['description'])) {
            $sampleNames = array_filter($itemNames);
            $suffix = !empty($sampleNames)
                ? (($isRetail ? ' | สินค้าแนะนำ: ' : ' | เมนูแนะนำ: ') . implode(', ', array_slice($sampleNames, 0, 3)))
                : '';
            $description = seoTruncateText($shop['description'] . $suffix, 160);
        } else {
            $description = $preset['seo_description'];
        }

        // 3. Dynamic Keywords
        $keywords = !empty($shop['seo_keywords']) ? $shop['seo_keywords'] : $preset['seo_keywords'];

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
    <meta name="author" content="{$escSiteName}">
    <meta name="robots" content="{$robots}">
    <meta name="theme-color" content="#FF6B35">
    <link rel="canonical" href="{$escCanonical}">

    <!-- Open Graph / Facebook / LINE -->
    <meta property="og:type" content="{$ogType}">
    <meta property="og:url" content="{$escCanonical}">
    <meta property="og:title" content="{$escTitle}">
    <meta property="og:description" content="{$escDesc}">
    <meta property="og:image" content="{$escImage}">
    <meta property="og:image:alt" content="{$escTitle}">
    <meta property="og:site_name" content="{$escSiteName}">
    <meta property="og:locale" content="th_TH">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{$escCanonical}">
    <meta name="twitter:title" content="{$escTitle}">
    <meta name="twitter:description" content="{$escDesc}">
    <meta name="twitter:image" content="{$escImage}">
    <meta name="twitter:image:alt" content="{$escTitle}">
HTML;

    // สร้าง JSON-LD Schema.org สำหรับหน้าสาธารณะ
    if ($type === 'restaurant' && $shop) {
        $schema = buildRestaurantJsonLd($shop, $menuItems, $categories, $canonical, $image);
        $jsonLd = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        $html .= "\n    <script type=\"application/ld+json\">\n" . $jsonLd . "\n    </script>\n";

        $breadcrumb = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'OrderPOS',
                    'item' => $baseUrl . '/'
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => $shop['shop_name'] ?? 'ร้านค้า',
                    'item' => $canonical
                ]
            ]
        ];
        $bcJson = json_encode($breadcrumb, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        $html .= "    <script type=\"application/ld+json\">\n" . $bcJson . "\n    </script>\n";
    } elseif ($type === 'platform') {
        $schema = buildPlatformJsonLd($title, $description, $canonical, $image);
        $jsonLd = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        $html .= "\n    <script type=\"application/ld+json\">\n" . $jsonLd . "\n    </script>\n";
    }

    return $html;
}

/**
 * สร้าง JSON-LD Schema ประเภท Restaurant หรือ Store/GroceryStore สำหรับหน้าร้านค้าสมาชิก
 */
function buildRestaurantJsonLd(array $shop, array $menuItems, array $categories, string $url, string $image) {
    $isRetail = (($shop['shop_type'] ?? 'restaurant') === 'retail');
    $prices = array_filter(array_map(function($item) {
        return floatval($item['price'] ?? 0);
    }, $menuItems), function($p) { return $p > 0; });

    $priceRange = '฿฿';
    if (!empty($prices)) {
        $minPrice = number_format(min($prices), 0);
        $maxPrice = number_format(max($prices), 0);
        $priceRange = "฿{$minPrice} - ฿{$maxPrice}";
    }

    if ($isRetail) {
        $products = [];
        foreach (array_slice($menuItems, 0, 30) as $mi) {
            $inStock = (!isset($mi['stock_qty']) || $mi['stock_qty'] === null || intval($mi['stock_qty']) > 0);
            $prod = [
                '@type' => 'Product',
                'name' => $mi['name'],
                'description' => !empty($mi['description']) ? $mi['description'] : $mi['name'],
                'offers' => [
                    '@type' => 'Offer',
                    'price' => number_format((float)$mi['price'], 2, '.', ''),
                    'priceCurrency' => 'THB',
                    'availability' => $inStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock'
                ]
            ];
            if (!empty($mi['sku'])) {
                $prod['sku'] = $mi['sku'];
            }
            $products[] = $prod;
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Store',
            'additionalType' => 'https://schema.org/GroceryStore',
            'name' => $shop['shop_name'] ?? 'ร้านค้าปลีก',
            'description' => !empty($shop['description']) ? $shop['description'] : ('เลือกซื้อสินค้าร้าน ' . ($shop['shop_name'] ?? '')),
            'url' => $url,
            'image' => $image,
            'priceRange' => $priceRange,
            'paymentAccepted' => 'Cash, PromptPay QR'
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
        if (!empty($products)) {
            $schema['hasOfferCatalog'] = [
                '@type' => 'OfferCatalog',
                'name' => 'รายการสินค้าร้าน ' . ($shop['shop_name'] ?? ''),
                'itemListElement' => $products
            ];
        }
        return $schema;
    }

    // จัดกลุ่มรายการอาหารตามหมวดหมู่เพื่อทำ MenuSection (สำหรับร้านอาหาร)
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
        'acceptsReservations' => 'True',
        'paymentAccepted' => 'Cash, PromptPay QR'
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
        'inLanguage' => 'th',
        'applicationCategory' => 'BusinessApplication',
        'operatingSystem' => 'Web Browser, iOS, Android',
        'featureList' => [
            'ระบบ POS ร้านอาหารและคาเฟ่พร้อม QR Code สั่งอาหารประจำโต๊ะ',
            'จอห้องครัว KDS อัปเดตสถานะออเดอร์แบบเรียลไทม์',
            'โหมดร้านของชำ มินิมาร์ท ค้าปลีก พร้อมค้นหาบาร์โค้ดและตัดสต๊อกอัตโนมัติ',
            'รองรับชำระเงิน PromptPay QR และสรุปยอดขายรายวันทันที'
        ],
        'provider' => [
            '@type' => 'Organization',
            'name' => 'OrderPOS',
            'url' => $url
        ],
        'offers' => [
            '@type' => 'Offer',
            'price' => '0',
            'priceCurrency' => 'THB',
            'description' => 'สมัครสมาชิกเปิดร้านอาหารและร้านค้าปลีกใช้งานระบบ POS ฟรี'
        ]
    ];
}
