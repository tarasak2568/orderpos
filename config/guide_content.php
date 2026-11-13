<?php
/**
 * Shared Interactive User Guide / Manual Content & Renderer
 * Used by both public /guide (guide.php) and admin portal (admin/guide.php)
 * to avoid duplicating manual steps, styles, and search/tab logic.
 */

/**
 * Resolve navigation target URL depending on public vs admin context.
 */
function guideActionHref(string $adminTarget, string $publicFallback, bool $isAdmin): string
{
    return $isAdmin ? $adminTarget : $publicFallback;
}

/**
 * Resolve storefront URL for customer preview/orders depending on admin vs public path.
 */
function getGuideStorefrontUrl(bool $isAdmin, string $shopSlug = ''): string
{
    if ($shopSlug !== '') {
        return $isAdmin ? '../shop/' . rawurlencode($shopSlug) : '/shop/' . rawurlencode($shopSlug);
    }
    return $isAdmin ? '../menu.php' : '/menu.php';
}

/**
 * Build structured guide tabs, step cards, quick links, and FAQs.
 */
function getOrderPosGuideData(bool $isAdmin = false, bool $isRetail = false, string $shopSlug = ''): array
{
    $storefrontUrl = getGuideStorefrontUrl($isAdmin, $shopSlug);

    $tabs = [
        'customer' => [
            'id' => 'customer',
            'label' => 'คู่มือลูกค้า (สั่งอาหาร / ซื้อสินค้า)',
            'shortLabel' => 'สำหรับลูกค้า',
            'icon' => 'fa-mobile-screen-button',
            'badge' => 'ไม่ต้องโหลดแอป',
            'heroTitle' => 'วิธีสแกน QR สั่งอาหาร & ช้อปหน้าร้านออนไลน์',
            'heroDesc' => 'ลูกค้าสามารถเปิดเมนูอาหารหรือแคตตาล็อกสินค้าผ่านมือถือได้ทันที สั่งเพิ่มได้ตลอด และชำระเงินสะดวกผ่าน PromptPay หรือหน้าเคาน์เตอร์',
            'steps' => [
                [
                    'num' => '01',
                    'icon' => 'fa-qrcode',
                    'title' => 'สแกน QR Code หรือเปิดลิงก์ร้านค้า',
                    'desc' => 'ใช้กล้องมือถือหรือแอป LINE สแกน QR Code ที่วางบนโต๊ะ (ร้านอาหาร) หรือป้ายหน้าร้าน/ลิงก์ร้านค้า (ร้านค้าปลีก) ระบบจะเปิดหน้าเมนูให้ทันทีโดยไม่ต้องติดตั้งแอปหรือสมัครสมาชิก',
                    'tips' => [
                        'หากนั่งทานที่ร้าน ระบบจะระบุหมายเลขโต๊ะให้ด้านบนอัตโนมัติ (เช่น โต๊ะ T01)',
                        'หากสั่งผ่านลิงก์ร้านค้าออนไลน์ ระบบจะขึ้นสถานะ "สั่งซื้อสินค้าหน้าร้าน / ออนไลน์"',
                    ],
                    'actionLabel' => $shopSlug !== '' ? 'เปิดดูหน้าร้านของคุณ' : 'ดูตัวอย่างหน้าเมนู',
                    'actionHref' => $storefrontUrl,
                    'actionExternal' => true,
                ],
                [
                    'num' => '02',
                    'icon' => 'fa-magnifying-glass',
                    'title' => 'เลือกหมวดหมู่ & ค้นหารายการที่ต้องการ',
                    'desc' => 'เลื่อนดูรายการอาหารหรือสินค้า แตะแถบหมวดหมู่ด้านบนเพื่อกรองดูเฉพาะกลุ่มเมนูที่สนใจ หรือดูรายการแนะนำ ราคา และรายละเอียดสินค้าได้ชัดเจนบนหน้าเดียว',
                    'tips' => [
                        'เมนูที่มีป้าย "หมด" หรือ "สินค้าหมดชั่วคราว" จะไม่สามารถกดสั่งเพิ่มได้',
                        'แตะปุ่ม "? วิธีสั่งซื้อ" มุมขวาบนของหน้าเมนูเพื่อดูคำแนะนำด่วนได้ตลอดเวลา',
                    ],
                ],
                [
                    'num' => '03',
                    'icon' => 'fa-cart-plus',
                    'title' => 'หยิบใส่ตะกร้า ปรับจำนวน & ใส่หมายเหตุ',
                    'desc' => 'กดปุ่ม "+ เพิ่ม" ในรายการที่ต้องการ แถบตะกร้าด้านล่างจะสรุปจำนวนชิ้นและยอดรวมแบบเรียลไทม์ กด "ดูตะกร้า" เพื่อปรับเพิ่ม/ลดจำนวน (+ / -) หรือลบรายการได้',
                    'tips' => [
                        'ร้านอาหาร: พิมพ์ช่องหมายเหตุ เช่น "ไม่ใส่ผัก", "เผ็ดน้อย", "แยกน้ำจิ้ม"',
                        'ร้านค้าปลีก: พิมพ์หมายเหตุ เช่น "แยกถุง", "รับหน้าร้านเวลา 17:00 น."',
                    ],
                ],
                [
                    'num' => '04',
                    'icon' => 'fa-paper-plane',
                    'title' => 'ยืนยันส่งรายการเข้าครัว / หน้าเคาน์เตอร์',
                    'desc' => 'ตรวจสอบรายการและยอดรวมให้ครบถ้วน แล้วกด "ส่งรายการสั่งซื้อ" (หรือ "ยืนยันสั่งซื้อสินค้า") ออเดอร์จะถูกส่งตรงไปยังหน้าจอครัวและแคชเชียร์ทันที',
                    'tips' => [
                        'สามารถสแกนสั่งเมนูหรือสินค้าเพิ่มเติมได้เรื่อยๆ ยอดบิลของโต๊ะจะรวมให้อัตโนมัติ',
                        'เมื่อทานหรือช้อปเสร็จ แจ้งพนักงานเช็กบิลเพื่อชำระเงินสดหรือสแกน PromptPay QR',
                    ],
                ],
            ],
        ],
        'restaurant' => [
            'id' => 'restaurant',
            'label' => 'คู่มือร้านอาหาร & คาเฟ่',
            'shortLabel' => 'ร้านอาหาร & คาเฟ่',
            'icon' => 'fa-utensils',
            'badge' => 'ครบวงจร QR + KDS',
            'heroTitle' => 'ตั้งค่าร้านอาหาร คาเฟ่ และใช้งาน POS + หน้าจอครัว',
            'heroDesc' => 'ขั้นตอนเริ่มต้นสำหรับเจ้าของร้านอาหาร ตั้งแต่ตั้งค่าร้านและ PromptPay เพิ่มเมนูอาหาร พิมพ์ป้าย QR ประจำโต๊ะ รับออเดอร์เข้าครัว จนถึงเก็บเงินปิดโต๊ะ',
            'steps' => [
                [
                    'num' => '01',
                    'icon' => 'fa-sliders',
                    'title' => 'ตั้งค่าข้อมูลร้าน & บัญชี PromptPay',
                    'desc' => 'เข้าหน้า "ตั้งค่าร้าน & SEO" เลือกประเภทร้านเป็น "ร้านอาหาร / คาเฟ่ (Restaurant)" กรอกชื่อร้าน เบอร์โทร ที่อยู่ และหมายเลขพร้อมเพย์สำหรับรับเงินโอน QR Code',
                    'tips' => [
                        'รองรับหมายเลข PromptPay ทั้งเบอร์มือถือ 10 หลัก, เลขบัตรประชาชน 13 หลัก หรือ e-Wallet 15 หลัก',
                        'ข้อมูลเบอร์โทรและที่อยู่จะแสดงให้ลูกค้าเห็นบนหน้าเมนูออนไลน์ทันที',
                    ],
                    'actionLabel' => $isAdmin ? 'ไปตั้งค่าร้าน' : 'เข้าสู่ระบบ / เปิดร้านฟรี',
                    'actionHref' => guideActionHref('shop_settings.php', '/register.php', $isAdmin),
                ],
                [
                    'num' => '02',
                    'icon' => 'fa-book',
                    'title' => 'สร้างหมวดหมู่ & เพิ่มรายการอาหาร',
                    'desc' => 'ไปที่ "จัดการเมนู" เพื่อสร้างหมวดหมู่ (เช่น อาหารจานเดียว, กับข้าว, เครื่องดื่ม, ของหวาน) จากนั้นกดเพิ่มเมนูอาหาร ตั้งราคา ใส่คำอธิบาย และอัปโหลดรูปภาพอาหารให้น่าทาน',
                    'tips' => [
                        'สามารถกดสลับสถานะ "พร้อมเสิร์ฟ / หมดชั่วคราว" ได้ในคลิกเดียวโดยไม่ต้องลบเมนู',
                        'ร้านที่สมัครใหม่จะมีหมวดหมู่และเมนูตัวอย่างให้แก้ไขต่อได้ทันที',
                    ],
                    'actionLabel' => $isAdmin ? 'ไปจัดการเมนู' : 'เข้าสู่ระบบร้านค้า',
                    'actionHref' => guideActionHref('menu_manage.php', '/login.php', $isAdmin),
                ],
                [
                    'num' => '03',
                    'icon' => 'fa-qrcode',
                    'title' => 'จัดการโต๊ะ & พิมพ์ QR Code วางตามโต๊ะ',
                    'desc' => 'สร้างหมายเลขโต๊ะ (เช่น T01, T02, VIP1) ในหน้า "จัดการโต๊ะ" แล้วไปที่ "QR Code หน้าร้าน" เพื่อดูและสั่งพิมพ์ป้าย QR ประจำแต่ละโต๊ะ (แต่ละโต๊ะจะได้ลิงก์เฉพาะโต๊ะตัวเอง)',
                    'tips' => [
                        'ลิงก์ประจำโต๊ะจะอยู่ในรูปแบบ /shop/ชื่อร้าน/table/T01',
                        'แนะนำเคลือบพลาสติกหรือตั้งสแตนด์ QR บนโต๊ะเพื่อให้ลูกค้าสแกนสั่งเองได้ทันที',
                    ],
                    'actionLabel' => $isAdmin ? 'พิมพ์ QR Code โต๊ะ' : 'สมัครใช้งานฟรี',
                    'actionHref' => guideActionHref('qr_tables.php', '/register.php', $isAdmin),
                ],
                [
                    'num' => '04',
                    'icon' => 'fa-kitchen-set',
                    'title' => 'รับออเดอร์หน้าร้าน (POS) & หน้าจอครัว (KDS)',
                    'desc' => 'พนักงานรับออเดอร์ให้ลูกค้าหน้าร้านผ่านหน้า "ขายหน้าร้าน (POS)" ส่วนออเดอร์จากทั้ง POS และลูกค้าสแกน QR จะเด้งเข้าหน้า "หน้าจอครัว" แบบเรียลไทม์ เชฟกดเปลี่ยนสถานะจาก รอทำ -> กำลังทำ -> พร้อมเสิร์ฟ ได้ทันที',
                    'tips' => [
                        'เปิดหน้าจอครัว (kitchen.php) บนแท็บเล็ตหรือจอในครัว ระบบรีเฟรชอัตโนมัติพร้อมแสดงหมายเหตุของลูกค้าครบถ้วน',
                        'สามารถแยกหน้าที่พนักงานรับออเดอร์และเชฟในครัวได้พร้อมกันบนอุปกรณ์คนละเครื่อง',
                    ],
                    'actionLabel' => $isAdmin ? 'เปิดหน้าจอครัว / POS' : 'เข้าสู่ระบบ',
                    'actionHref' => guideActionHref('../pos.php', '/login.php', $isAdmin),
                ],
                [
                    'num' => '05',
                    'icon' => 'fa-receipt',
                    'title' => 'เช็กบิล & รับเงินผ่าน PromptPay QR',
                    'desc' => 'ไปที่หน้า "เก็บเงิน (Billing)" เลือกโต๊ะที่ต้องการเช็กบิล ตรวจสอบรายการและยอดรวม เลือกวิธีชำระเงินเป็นเงินสด (คำนวณเงินทอน) หรือ PromptPay QR (สร้าง QR ตามยอดบิลอัตโนมัติ) แล้วกดยืนยันชำระเงิน',
                    'tips' => [
                        'เมื่อยืนยันรับเงินแล้ว สถานะบิลจะเปลี่ยนเป็นชำระแล้ว และโต๊ะจะกลับมา "ว่าง" อัตโนมัติ',
                        'ตรวจสอบยอดขายและประวัติบิลทั้งหมดย้อนหลังได้ที่ "ดูออเดอร์"',
                    ],
                    'actionLabel' => $isAdmin ? 'ไปหน้าเก็บเงิน' : 'เข้าสู่ระบบร้านค้า',
                    'actionHref' => guideActionHref('../billing.php', '/login.php', $isAdmin),
                ],
                [
                    'num' => '06',
                    'icon' => 'fa-users-gear',
                    'title' => 'จัดการทีมงานในร้าน & สมาชิกลูกค้าประจำ (CRM)',
                    'desc' => 'เจ้าของร้านสามารถเพิ่มบัญชีพนักงานแยกสิทธิ์ (admin / staff / kitchen) ในเมนู "จัดการทีมงาน & สิทธิ์" และสร้างทะเบียนสมาชิกลูกค้าประจำเพื่อสะสมแต้มและให้ส่วนลดอัตโนมัติในหน้าคิดเงิน',
                    'tips' => [
                        'พนักงานตำแหน่ง kitchen จะถูกพาไปหน้าจอครัวโดยอัตโนมัติ ไม่เข้าหน้าตั้งค่าร้านหรือการเงิน',
                        'ค้นหาเบอร์โทรสมาชิกหรือสมัครด่วนได้ทันทีตอนเก็บเงินในหน้า POS และ Billing พร้อมหัก/สะสมแต้มอัตโนมัติ',
                    ],
                    'actionLabel' => $isAdmin ? 'จัดการสมาชิก / ทีมงาน' : 'เข้าสู่ระบบร้านค้า',
                    'actionHref' => guideActionHref('members_manage.php', '/login.php', $isAdmin),
                ],
            ],
        ],
        'retail' => [
            'id' => 'retail',
            'label' => 'คู่มือร้านของชำ / ค้าปลีก',
            'shortLabel' => 'ค้าปลีก & มินิมาร์ท',
            'icon' => 'fa-boxes-stacked',
            'badge' => 'บาร์โค้ด + สต๊อก',
            'heroTitle' => 'ใช้งานระบบขายหน้าร้านสำหรับร้านของชำ มินิมาร์ท และค้าปลีก',
            'heroDesc' => 'ออกแบบให้ขายไวเหมือนร้านสะดวกซื้อ รองรับเครื่องยิงบาร์โค้ด ค้นหารหัส SKU คิดเงินทอนด่วน ตัดสต๊อกอัตโนมัติ และเปิดรับออเดอร์ออนไลน์ได้พร้อมกัน',
            'steps' => [
                [
                    'num' => '01',
                    'icon' => 'fa-store',
                    'title' => 'สลับประเภทร้านเป็น "ค้าปลีก / ร้านของชำ (Retail)"',
                    'desc' => 'ในหน้า "ตั้งค่าร้าน & SEO" เลือกประเภทร้านเป็นค้าปลีก เมนูหลังบ้านจะปรับคำศัพท์เป็น "สินค้า & สต๊อก", "จุดขาย/ช่องทาง" และ "บิลขาย" พร้อมซ่อนหน้าจอครัวให้อัตโนมัติ',
                    'tips' => [
                        'เปิดใช้โหมด Retail แล้ว หน้าแคชเชียร์จะโฟกัสช่องค้นหาสินค้า/ยิงบาร์โค้ดเป็นหลัก',
                        'อย่าลืมกรอกเลข PromptPay เพื่อรับโอนเงินหน้าเคาน์เตอร์แบบไม่ต้องเช็กยอดมือ',
                    ],
                    'actionLabel' => $isAdmin ? 'ตั้งค่าโหมดร้านค้า' : 'เปิดร้านค้าปลีกฟรี',
                    'actionHref' => guideActionHref('shop_settings.php', '/register.php', $isAdmin),
                ],
                [
                    'num' => '02',
                    'icon' => 'fa-barcode',
                    'title' => 'เพิ่มสินค้า รหัสบาร์โค้ด (SKU) & จำนวนสต๊อก',
                    'desc' => 'เข้าหน้า "จัดการสินค้า & สต๊อก" เพิ่มรายการสินค้า พร้อมกรอกรหัสบาร์โค้ด (EAN-13 / SKU) และระบุจำนวนสินค้าคงเหลือในสต๊อก เพื่อให้ระบบติดตามและตัดสต๊อกเมื่อขายจริง',
                    'tips' => [
                        'สินค้าที่สต๊อกหมด (0 ชิ้น) หรือปิดสถานะขาย ระบบจะป้องกันไม่ให้ลูกค้าออนไลน์กดสั่งซื้อ',
                        'อัปโหลดรูปภาพสินค้าชัดเจนเพื่อให้ลูกค้าออนไลน์และพนักงานจำสินค้าง่ายขึ้น',
                    ],
                    'actionLabel' => $isAdmin ? 'จัดการสินค้า & สต๊อก' : 'เข้าสู่ระบบร้านค้า',
                    'actionHref' => guideActionHref('menu_manage.php', '/login.php', $isAdmin),
                ],
                [
                    'num' => '03',
                    'icon' => 'fa-cash-register',
                    'title' => 'ยิงบาร์โค้ดขายด่วนที่เคาน์เตอร์ (POS)',
                    'desc' => 'เปิดหน้า "ขายหน้าร้าน (POS)" แล้วเลือกช่องทาง "Walk-in / เคาน์เตอร์" ใช้เครื่องสแกนบาร์โค้ด USB/ไร้สายยิงที่สินค้า หรือพิมพ์ชื่อ/รหัสสินค้าในช่องค้นหา แล้วกด Enter เพื่อลงบิลทันที',
                    'tips' => [
                        'เหมาะมากสำหรับช่วงลูกค้าหนาแน่น คิดเงินรวดเร็ว ไม่ต้องเลื่อนหาเมนูทีละหมวด',
                        'รองรับทั้งการเพิ่มจุดขายหลายเคาน์เตอร์ใน "จุดขาย/ช่องทาง" เช่น เคาน์เตอร์ 1, เคาน์เตอร์ 2',
                    ],
                    'actionLabel' => $isAdmin ? 'เปิดหน้าขายหน้าร้าน POS' : 'เข้าสู่ระบบใช้งาน POS',
                    'actionHref' => guideActionHref('../pos.php', '/login.php', $isAdmin),
                ],
                [
                    'num' => '04',
                    'icon' => 'fa-calculator',
                    'title' => 'คิดเงินทอน PromptPay QR & ตัดสต๊อกอัตโนมัติ',
                    'desc' => 'กดส่งบิลและชำระเงินที่หน้า "เก็บเงิน (Billing)" กรอกจำนวนเงินสดที่รับมาเพื่อคำนวณเงินทอนอัตโนมัติ หรือแสดง QR PromptPay ให้ลูกค้าสแกน ทุกการขายจะตัดจำนวนสต๊อกสินค้าให้ทันที',
                    'tips' => [
                        'สามารถดูยอดขายรายวันและรายการสินค้าที่ขายออกไปได้ในหน้า "ประวัติการขาย / บิลขาย"',
                        'แชร์ลิงก์ /shop/ชื่อร้าน ให้ลูกค้าประจำกดสั่งของผ่านมือถือล่วงหน้าแล้วมารับที่ร้านได้ด้วย',
                    ],
                    'actionLabel' => $isAdmin ? 'ดูประวัติบิลขาย' : 'สมัครใช้งานฟรี',
                    'actionHref' => guideActionHref('orders_manage.php', '/register.php', $isAdmin),
                ],
                [
                    'num' => '05',
                    'icon' => 'fa-address-card',
                    'title' => 'ระบบสมาชิกร้านค้า (CRM) & ทีมงานแคชเชียร์',
                    'desc' => 'สร้างสมาชิกลูกค้าประจำด้วยเบอร์โทร ตั้งส่วนลดระดับ Bronze/Silver/Gold/VIP และตั้งค่ากติกาสะสมแต้ม (เช่น ซื้อครบ ฿25 รับ 1 แต้ม) พร้อมเพิ่มบัญชีพนักงานแคชเชียร์แยกแต่ละคนในเมนูทีมงาน',
                    'tips' => [
                        'ตอนคิดเงินหน้าร้าน POS แค่พิมพ์เบอร์โทรลูกค้า ระบบจะคิดส่วนลด Tier และให้แลกแต้มเป็นส่วนลดเงินสดทันที',
                        'เจ้าของร้านสามารถดูประวัติยอดซื้อรวมและแต้มเข้า-ออกของสมาชิกรายคนย้อนหลังได้ตลอดเวลา',
                    ],
                    'actionLabel' => $isAdmin ? 'ไปหน้าสมาชิกลูกค้า' : 'เข้าสู่ระบบจัดการสมาชิก',
                    'actionHref' => guideActionHref('members_manage.php', '/login.php', $isAdmin),
                ],
            ],
        ],
        'seo' => [
            'id' => 'seo',
            'label' => 'ตั้งค่า SEO & คำถามที่พบบ่อย',
            'shortLabel' => 'SEO & FAQ',
            'icon' => 'fa-magnifying-glass-chart',
            'badge' => 'ติดค้นหา Google',
            'heroTitle' => 'โปรโมทร้านค้าบน Google (SEO) และคำถามที่พบบ่อย',
            'heroDesc' => 'OrderPOS ช่วยสร้างหน้าหน้าร้านออนไลน์ที่รองรับการค้นหา Google โดยอัตโนมัติ พร้อมเครื่องมือปรับแต่ง Meta Tags, คำค้นหาประจำพื้นที่ และสรุปลิงก์ใช้งานทั้งหมด',
            'steps' => [
                [
                    'num' => '01',
                    'icon' => 'fa-wand-magic-sparkles',
                    'title' => 'สร้างคีย์เวิร์ด SEO ใน 1 คลิก',
                    'desc' => 'เข้าไปที่ "ตั้งค่าร้าน & SEO" กรอกชื่อร้าน ที่อยู่/อำเภอ/จังหวัด ให้ครบ แล้วกดปุ่ม "เติมคำแนะนำ SEO อัตโนมัติ" ระบบจะช่วยร่าง SEO Title, Description และ Focus Keywords ให้เหมาะกับประเภทร้านของคุณทันที',
                    'tips' => [
                        'ใส่ชื่อเมนูเด่นและย่านที่ตั้งใน SEO Title เช่น "กะเพราถาดเด็ด - อาหารตามสั่ง สะพานควาย"',
                        'หน้าเมนูร้านของคุณ (/shop/{slug}) จะถูกเพิ่มลงใน /sitemap.xml ให้อัตโนมัติเพื่อให้ Google Bot เข้ามาเก็บข้อมูล',
                    ],
                    'actionLabel' => $isAdmin ? 'ปรับแต่ง SEO ร้านตอนนี้' : 'เข้าสู่ระบบตั้งค่า SEO',
                    'actionHref' => guideActionHref('shop_settings.php', '/login.php', $isAdmin),
                ],
                [
                    'num' => '02',
                    'icon' => 'fa-share-nodes',
                    'title' => 'แชร์ลิงก์ร้านค้าให้ลูกค้าและ Google Maps',
                    'desc' => 'นำลิงก์ร้านค้าของคุณ (/shop/ชื่อร้าน) ไปวางในโปรไฟล์ LINE OA, เพจ Facebook, Instagram และปักหมุดเว็บไซต์บน Google Business Profile เพื่อให้ลูกค้ากดสั่งซื้อหรือดูเมนูได้ตลอด 24 ชม.',
                    'tips' => [
                        'ลิงก์หน้าร้านรองรับ Open Graph Image (แชร์แล้วขึ้นรูปปกและรายละเอียดร้านสวยบน LINE/Facebook)',
                        'ระบบส่งออก Structured Data (JSON-LD) ประเภท Restaurant หรือ Store ให้ Search Engine เข้าใจทันที',
                    ],
                ],
            ],
            'urlReference' => [
                ['label' => 'หน้าร้านออนไลน์ (สำหรับแชร์ลูกค้า)', 'url' => '/shop/{shop-slug}', 'role' => 'ลูกค้าทุกคน'],
                ['label' => 'QR สั่งอาหารประจำโต๊ะ', 'url' => '/shop/{shop-slug}/table/{table-code}', 'role' => 'ลูกค้าที่โต๊ะ'],
                ['label' => 'หน้าขายหน้าร้าน (POS)', 'url' => '/pos.php', 'role' => 'พนักงาน / แคชเชียร์'],
                ['label' => 'หน้าจอครัว (KDS)', 'url' => '/kitchen.php', 'role' => 'เชฟ / ในครัว'],
                ['label' => 'หน้าเก็บเงิน (Billing & PromptPay)', 'url' => '/billing.php', 'role' => 'แคชเชียร์'],
                ['label' => 'หลังบ้านจัดการร้าน', 'url' => '/admin/dashboard.php', 'role' => 'เจ้าของร้าน'],
                ['label' => 'สมาชิกลูกค้า & สะสมแต้ม (CRM)', 'url' => '/admin/members_manage.php', 'role' => 'เจ้าของร้าน / พนักงาน'],
                ['label' => 'จัดการทีมงาน & สิทธิ์พนักงาน', 'url' => '/admin/staff_manage.php', 'role' => 'เจ้าของร้าน (Admin)'],
                ['label' => 'จัดการร้านค้าสมาชิกทั้งหมด', 'url' => '/admin/platform_manage.php', 'role' => 'Super Admin'],
            ],
            'faqs' => [
                [
                    'q' => 'ลูกค้าต้องสมัครสมาชิกหรือโหลดแอปก่อนสั่งอาหารไหม?',
                    'a' => 'ไม่ต้องเลยครับ ลูกค้าแค่สแกน QR Code ที่โต๊ะ หรือเปิดลิงก์ร้านค้าผ่านเว็บเบราว์เซอร์บนมือถือ (Chrome/Safari/LINE) ก็เลือกเมนู หยิบใส่ตะกร้า และส่งออเดอร์ได้ทันที',
                ],
                [
                    'q' => 'เพิ่มบัญชีพนักงานแคชเชียร์ หรือระบบสมาชิกสะสมแต้มได้อย่างไร?',
                    'a' => 'เจ้าของร้าน (admin) สามารถเข้าเมนูหลังบ้าน "จัดการทีมงาน & สิทธิ์" เพื่อสร้างรหัสให้พนักงานขาย (staff) และเชฟ (kitchen) แยกกันได้ และเข้าเมนู "สมาชิกลูกค้า & แต้ม" เพื่อตั้งกติกาสะสมแต้ม ส่วนลด VIP และค้นหาเบอร์โทรสมาชิกเวลาคิดเงินได้ทันที',
                ],
                [
                    'q' => 'QR PromptPay ในหน้าเก็บเงินไม่ขึ้น ต้องตั้งค่าตรงไหน?',
                    'a' => 'ไปที่เมนูหลังบ้าน "ตั้งค่าร้าน & SEO" แล้วกรอกช่อง PromptPay ID ด้วยเบอร์มือถือ 10 หลัก (เช่น 0812345678) หรือเลขประจำตัวผู้เสียภาษี/บัตรประชาชน 13 หลัก หรือ e-Wallet 15 หลัก จากนั้นกดบันทึกข้อมูลครับ',
                ],
                [
                    'q' => 'ร้านขายของชำ/มินิมาร์ท จำเป็นต้องเพิ่มโต๊ะอาหารไหม?',
                    'a' => 'ไม่จำเป็นครับ หากตั้งประเภทร้านเป็น "ร้านค้าปลีก / มินิมาร์ท" ในตั้งค่าร้าน ระบบจะใช้ช่องทาง "Walk-in / เคาน์เตอร์" ให้โดยอัตโนมัติ หรือเพิ่มจุดขายหลายจุด (เช่น เคาน์เตอร์ 1, เคาน์เตอร์ 2) ในเมนู "จุดขาย/ช่องทาง"',
                ],
                [
                    'q' => 'สามารถเปลี่ยนจากโหมดร้านอาหารเป็นร้านค้าปลีกทีหลังได้ไหม?',
                    'a' => 'ได้ตลอดเวลาครับ เพียงเข้าหน้า "ตั้งค่าร้าน & SEO" เปลี่ยนประเภทธุรกิจแล้วกดบันทึก เมนูหลังบ้าน POS และหน้าร้านลูกค้าจะปรับการทำงานให้สอดคล้องทันทีโดยข้อมูลสินค้าและบิลเดิมยังอยู่ครบ',
                ],
                [
                    'q' => 'ใช้ OrderPOS บนมือถือหรือแท็บเล็ตได้หรือไม่?',
                    'a' => 'รองรับทุกอุปกรณ์ครับ ทั้งคอมพิวเตอร์ โน้ตบุ๊ก แท็บเล็ต และมือถือ สามารถเปิดหน้าจอครัวบนแท็บเล็ตไว้ในครัว เปิด POS หน้าเคาน์เตอร์บนคอมพิวเตอร์ และเช็กยอดขายผ่านมือถือได้พร้อมกัน',
                ],
            ],
        ],
    ];

    return $tabs;
}

/**
 * Render the interactive OrderPOS User Guide widget (scoped CSS + HTML + JS).
 */
function renderOrderPosGuideWidget(array $options = []): void
{
    $isAdmin = !empty($options['isAdmin']);
    $isRetail = !empty($options['isRetail']);
    $shopSlug = (string)($options['shopSlug'] ?? '');
    $storefrontUrl = getGuideStorefrontUrl($isAdmin, $shopSlug);

    $requestedTab = (string)($options['defaultTab'] ?? ($_GET['tab'] ?? ''));
    $tabs = getOrderPosGuideData($isAdmin, $isRetail, $shopSlug);
    $activeTab = isset($tabs[$requestedTab]) ? $requestedTab : ($isAdmin ? ($isRetail ? 'retail' : 'restaurant') : 'customer');
    ?>
    <style>
        .op-guide-wrap {
            display: flex;
            flex-direction: column;
            gap: 22px;
        }

        .op-guide-banner {
            background: linear-gradient(135deg, #2D3436 0%, #FF6B35 100%);
            color: #FFFFFF;
            border-radius: 16px;
            padding: 28px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 18px;
            box-shadow: 0 10px 28px rgba(45, 52, 54, 0.16);
        }

        .op-guide-banner h2 {
            margin: 0 0 8px 0;
            font-size: 24px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .op-guide-banner p {
            margin: 0;
            font-size: 14.5px;
            color: rgba(255, 255, 255, 0.9);
            max-width: 680px;
            line-height: 1.65;
        }

        .op-guide-search-box {
            display: flex;
            align-items: center;
            background: #FFFFFF;
            border-radius: 12px;
            padding: 6px 14px;
            min-width: 280px;
            flex: 0 1 360px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.14);
        }

        .op-guide-search-box i {
            color: #FF6B35;
            font-size: 16px;
            margin-right: 10px;
        }

        .op-guide-search-box input {
            border: none;
            outline: none;
            width: 100%;
            font-family: inherit;
            font-size: 14px;
            padding: 8px 0;
            color: #2D3436;
            background: transparent;
        }

        .op-guide-search-clear {
            background: #F1F5F9;
            border: none;
            color: #64748B;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            font-size: 13px;
        }

        .op-guide-search-clear.visible {
            display: inline-flex;
        }

        .op-guide-tabs {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .op-guide-tab-btn {
            border: 1.5px solid #E2E8F0;
            background: #FFFFFF;
            color: #334155;
            border-radius: 12px;
            padding: 12px 18px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 9px;
            transition: all 0.2s ease;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04);
        }

        .op-guide-tab-btn:hover {
            border-color: #FF6B35;
            color: #FF6B35;
            transform: translateY(-1px);
        }

        .op-guide-tab-btn.active {
            background: linear-gradient(135deg, #FF6B35, #E55A2B);
            border-color: #FF6B35;
            color: #FFFFFF;
            box-shadow: 0 6px 16px rgba(255, 107, 53, 0.28);
        }

        .op-guide-tab-badge {
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 999px;
            background: rgba(255, 107, 53, 0.12);
            color: #E55A2B;
            font-weight: 600;
        }

        .op-guide-tab-btn.active .op-guide-tab-badge {
            background: rgba(255, 255, 255, 0.22);
            color: #FFFFFF;
        }

        .op-guide-panel {
            display: none;
            flex-direction: column;
            gap: 18px;
        }

        .op-guide-panel.active {
            display: flex;
        }

        .op-guide-section-head {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-left: 5px solid #FF6B35;
            border-radius: 14px;
            padding: 20px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 14px;
        }

        .op-guide-section-head h3 {
            margin: 0 0 6px 0;
            font-size: 19px;
            color: #1E293B;
        }

        .op-guide-section-head p {
            margin: 0;
            color: #64748B;
            font-size: 14px;
            line-height: 1.6;
        }

        .op-guide-steps-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(310px, 1fr));
            gap: 18px;
        }

        .op-guide-step-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 16px;
            padding: 22px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .op-guide-step-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        }

        .op-guide-step-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .op-guide-step-num {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
            font-size: 13px;
            color: #FF6B35;
            background: #FFF3ED;
            padding: 5px 12px;
            border-radius: 999px;
        }

        .op-guide-step-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: #F8FAFC;
            color: #2D3436;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
        }

        .op-guide-step-card h4 {
            margin: 0;
            font-size: 17px;
            color: #1E293B;
            line-height: 1.4;
        }

        .op-guide-step-card p {
            margin: 0;
            font-size: 14px;
            color: #475569;
            line-height: 1.65;
        }

        .op-guide-tips {
            list-style: none;
            margin: 0;
            padding: 12px 14px;
            background: #F8FAFC;
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .op-guide-tips li {
            font-size: 13px;
            color: #334155;
            line-height: 1.5;
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }

        .op-guide-tips li i {
            color: #10B981;
            margin-top: 3px;
            flex-shrink: 0;
        }

        .op-guide-step-actions {
            margin-top: auto;
            padding-top: 6px;
        }

        .op-guide-extra-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 18px;
        }

        .op-guide-box {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 16px;
            padding: 22px;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
        }

        .op-guide-box h4 {
            margin: 0 0 14px 0;
            font-size: 17px;
            color: #1E293B;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .op-guide-url-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .op-guide-url-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 10px;
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
        }

        .op-guide-url-item strong {
            font-size: 13.5px;
            color: #1E293B;
            display: block;
        }

        .op-guide-url-item span {
            font-size: 12px;
            color: #64748B;
        }

        .op-guide-url-item code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 12.5px;
            background: #FFF3ED;
            color: #C2410C;
            padding: 4px 9px;
            border-radius: 6px;
        }

        .op-guide-faq-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .op-guide-faq-item {
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            overflow: hidden;
            background: #FFFFFF;
        }

        .op-guide-faq-q {
            width: 100%;
            text-align: left;
            background: #F8FAFC;
            border: none;
            padding: 14px 16px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            color: #1E293B;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }

        .op-guide-faq-a {
            display: none;
            padding: 14px 16px;
            font-size: 13.5px;
            color: #475569;
            line-height: 1.65;
            border-top: 1px solid #E2E8F0;
            background: #FFFFFF;
        }

        .op-guide-faq-item.open .op-guide-faq-a {
            display: block;
        }

        .op-guide-faq-item.open .op-guide-faq-q i {
            transform: rotate(180deg);
        }

        .op-guide-faq-q i {
            transition: transform 0.2s ease;
            color: #FF6B35;
        }

        .op-guide-search-results {
            display: none;
            flex-direction: column;
            gap: 14px;
        }

        .op-guide-search-results.active {
            display: flex;
        }

        .op-guide-search-meta {
            font-size: 14px;
            color: #475569;
            background: #FFF7ED;
            border: 1px solid #FDBA74;
            padding: 12px 16px;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }

        .op-guide-tab-tag {
            display: inline-block;
            font-size: 11.5px;
            font-weight: 600;
            color: #0369A1;
            background: #E0F2FE;
            padding: 3px 9px;
            border-radius: 999px;
            margin-bottom: 6px;
        }

        .op-guide-empty {
            background: #FFFFFF;
            border: 1px dashed #CBD5E1;
            border-radius: 14px;
            padding: 32px 20px;
            text-align: center;
            color: #64748B;
        }

        @media (max-width: 640px) {
            .op-guide-banner {
                padding: 20px;
            }
            .op-guide-banner h2 {
                font-size: 20px;
            }
            .op-guide-search-box {
                width: 100%;
                min-width: 0;
            }
            .op-guide-tab-btn {
                padding: 10px 14px;
                font-size: 13px;
            }
        }
    </style>

    <div class="op-guide-wrap" id="orderPosGuideRoot">
        <!-- Hero Banner & Live Search -->
        <section class="op-guide-banner" aria-label="หัวข้อคู่มือการใช้งาน">
            <div>
                <h2><i class="fas fa-book-open"></i> คู่มือการใช้งาน OrderPOS</h2>
                <p>รวมขั้นตอนใช้งานตั้งแต่สแกน QR สั่งอาหารสำหรับลูกค้า ไปจนถึงตั้งค่าร้านอาหาร คาเฟ่ ร้านค้าปลีก ยิงบาร์โค้ด หน้าจอครัว PromptPay และตั้งค่า SEO ให้ร้านติด Google</p>
            </div>
            <div class="op-guide-search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="guideSearchInput" placeholder="ค้นหาคู่มือ เช่น PromptPay, บาร์โค้ด, QR โต๊ะ..." aria-label="ค้นหาคู่มือการใช้งาน">
                <button type="button" id="guideSearchClear" class="op-guide-search-clear" title="ล้างคำค้นหา">&times;</button>
            </div>
        </section>

        <!-- Role Tabs -->
        <div class="op-guide-tabs" role="tablist" aria-label="เลือกหมวดหมู่คู่มือ">
            <?php foreach ($tabs as $tabKey => $tab): ?>
                <button type="button"
                        class="op-guide-tab-btn <?= $tabKey === $activeTab ? 'active' : '' ?>"
                        data-guide-tab="<?= htmlspecialchars($tabKey, ENT_QUOTES, 'UTF-8') ?>"
                        role="tab"
                        aria-selected="<?= $tabKey === $activeTab ? 'true' : 'false' ?>">
                    <i class="fas <?= htmlspecialchars($tab['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                    <span><?= htmlspecialchars($tab['shortLabel'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="op-guide-tab-badge"><?= htmlspecialchars($tab['badge'], ENT_QUOTES, 'UTF-8') ?></span>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Unified Search Results Container -->
        <div id="guideSearchResults" class="op-guide-search-results" aria-live="polite"></div>

        <!-- Tab Panels -->
        <?php foreach ($tabs as $tabKey => $tab): ?>
            <section class="op-guide-panel <?= $tabKey === $activeTab ? 'active' : '' ?>"
                     data-guide-panel="<?= htmlspecialchars($tabKey, ENT_QUOTES, 'UTF-8') ?>"
                     data-tab-label="<?= htmlspecialchars($tab['label'], ENT_QUOTES, 'UTF-8') ?>"
                     role="tabpanel">
                <div class="op-guide-section-head">
                    <div>
                        <h3><i class="fas <?= htmlspecialchars($tab['icon'], ENT_QUOTES, 'UTF-8') ?>" style="color:#FF6B35;"></i> <?= htmlspecialchars($tab['heroTitle'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p><?= htmlspecialchars($tab['heroDesc'], ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <?php if ($isAdmin && $tabKey === 'customer'): ?>
                        <a href="<?= htmlspecialchars($storefrontUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="btn btn-primary btn-sm">
                            <i class="fas fa-external-link-alt"></i> เปิดหน้าร้านจริงของคุณ
                        </a>
                    <?php endif; ?>
                </div>

                <div class="op-guide-steps-grid">
                    <?php foreach ($tab['steps'] as $step): ?>
                        <article class="op-guide-step-card" data-searchable-card="1" data-tab-label="<?= htmlspecialchars($tab['shortLabel'], ENT_QUOTES, 'UTF-8') ?>">
                            <div class="op-guide-step-top">
                                <span class="op-guide-step-num">ขั้นตอนที่ <?= htmlspecialchars($step['num'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="op-guide-step-icon"><i class="fas <?= htmlspecialchars($step['icon'], ENT_QUOTES, 'UTF-8') ?>"></i></span>
                            </div>
                            <h4><?= htmlspecialchars($step['title'], ENT_QUOTES, 'UTF-8') ?></h4>
                            <p><?= htmlspecialchars($step['desc'], ENT_QUOTES, 'UTF-8') ?></p>
                            <?php if (!empty($step['tips'])): ?>
                                <ul class="op-guide-tips">
                                    <?php foreach ($step['tips'] as $tip): ?>
                                        <li><i class="fas fa-check-circle"></i><span><?= htmlspecialchars($tip, ENT_QUOTES, 'UTF-8') ?></span></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                            <?php if (!empty($step['actionHref']) && !empty($step['actionLabel'])): ?>
                                <div class="op-guide-step-actions">
                                    <a href="<?= htmlspecialchars($step['actionHref'], ENT_QUOTES, 'UTF-8') ?>"
                                       <?= !empty($step['actionExternal']) ? 'target="_blank" rel="noopener"' : '' ?>
                                       class="btn btn-outline btn-sm">
                                        <i class="fas fa-arrow-right"></i> <?= htmlspecialchars($step['actionLabel'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>

                <?php if ($tabKey === 'seo'): ?>
                    <div class="op-guide-extra-grid">
                        <div class="op-guide-box">
                            <h4><i class="fas fa-link" style="color:#FF6B35;"></i> สรุปลิงก์เข้าใช้งานในระบบ</h4>
                            <div class="op-guide-url-list">
                                <?php foreach ($tab['urlReference'] as $u): ?>
                                    <div class="op-guide-url-item" data-searchable-card="1" data-tab-label="URL ระบบ">
                                        <div>
                                            <strong><?= htmlspecialchars($u['label'], ENT_QUOTES, 'UTF-8') ?></strong>
                                            <span>ผู้ใช้งาน: <?= htmlspecialchars($u['role'], ENT_QUOTES, 'UTF-8') ?></span>
                                        </div>
                                        <code><?= htmlspecialchars($u['url'], ENT_QUOTES, 'UTF-8') ?></code>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="op-guide-box">
                            <h4><i class="fas fa-circle-question" style="color:#FF6B35;"></i> คำถามที่พบบ่อย (FAQ)</h4>
                            <div class="op-guide-faq-list">
                                <?php foreach ($tab['faqs'] as $idx => $faq): ?>
                                    <div class="op-guide-faq-item <?= $idx === 0 ? 'open' : '' ?>" data-searchable-card="1" data-tab-label="FAQ">
                                        <button type="button" class="op-guide-faq-q">
                                            <span><?= htmlspecialchars($faq['q'], ENT_QUOTES, 'UTF-8') ?></span>
                                            <i class="fas fa-chevron-down"></i>
                                        </button>
                                        <div class="op-guide-faq-a"><?= htmlspecialchars($faq['a'], ENT_QUOTES, 'UTF-8') ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
    </div>

    <script>
        (function () {
            const root = document.getElementById('orderPosGuideRoot');
            if (!root) return;

            const tabButtons = root.querySelectorAll('[data-guide-tab]');
            const tabPanels = root.querySelectorAll('[data-guide-panel]');
            const searchInput = document.getElementById('guideSearchInput');
            const searchClear = document.getElementById('guideSearchClear');
            const searchResults = document.getElementById('guideSearchResults');

            function switchGuideTab(tabId, updateUrl) {
                tabButtons.forEach((btn) => {
                    const isMatch = btn.getAttribute('data-guide-tab') === tabId;
                    btn.classList.toggle('active', isMatch);
                    btn.setAttribute('aria-selected', isMatch ? 'true' : 'false');
                });
                tabPanels.forEach((panel) => {
                    const isMatch = panel.getAttribute('data-guide-panel') === tabId;
                    panel.classList.toggle('active', isMatch);
                });
                if (updateUrl && window.history && window.history.replaceState) {
                    const url = new URL(window.location.href);
                    url.searchParams.set('tab', tabId);
                    window.history.replaceState({}, '', url.toString());
                }
            }

            tabButtons.forEach((btn) => {
                btn.addEventListener('click', () => {
                    if (searchInput && searchInput.value.trim() !== '') {
                        searchInput.value = '';
                        renderSearch('');
                    }
                    switchGuideTab(btn.getAttribute('data-guide-tab'), true);
                });
            });

            root.querySelectorAll('.op-guide-faq-q').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const item = btn.closest('.op-guide-faq-item');
                    if (item) item.classList.toggle('open');
                });
            });

            function renderSearch(query) {
                const keyword = query.trim().toLowerCase();
                if (searchClear) {
                    searchClear.classList.toggle('visible', keyword.length > 0);
                }

                if (!keyword) {
                    searchResults.classList.remove('active');
                    searchResults.innerHTML = '';
                    const activeBtn = root.querySelector('.op-guide-tab-btn.active');
                    if (activeBtn) {
                        switchGuideTab(activeBtn.getAttribute('data-guide-tab'), false);
                    }
                    return;
                }

                tabPanels.forEach((panel) => panel.classList.remove('active'));
                searchResults.classList.add('active');

                const allCards = Array.from(root.querySelectorAll('[data-searchable-card="1"]'));
                const matches = allCards.filter((card) => card.textContent.toLowerCase().includes(keyword));

                searchResults.innerHTML = '';
                const meta = document.createElement('div');
                meta.className = 'op-guide-search-meta';
                meta.innerHTML = `<span>พบ <strong>${matches.length}</strong> หัวข้อที่ตรงกับคำค้นหา "<strong>${escapeHtml(query.trim())}</strong>"</span>`;
                const resetBtn = document.createElement('button');
                resetBtn.type = 'button';
                resetBtn.className = 'btn btn-outline btn-sm';
                resetBtn.textContent = 'กลับไปดูตามหมวดหมู่';
                resetBtn.addEventListener('click', () => {
                    searchInput.value = '';
                    renderSearch('');
                });
                meta.appendChild(resetBtn);
                searchResults.appendChild(meta);

                if (matches.length === 0) {
                    const empty = document.createElement('div');
                    empty.className = 'op-guide-empty';
                    empty.innerHTML = '<i class="fas fa-search" style="font-size:24px;margin-bottom:8px;display:block;"></i>ไม่พบหัวข้อที่ค้นหา ลองใช้คำสั้นลง เช่น "QR", "PromptPay", "สต๊อก", "บาร์โค้ด", "SEO"';
                    searchResults.appendChild(empty);
                    return;
                }

                const grid = document.createElement('div');
                grid.className = 'op-guide-steps-grid';
                matches.forEach((match) => {
                    const clone = match.cloneNode(true);
                    if (clone.classList.contains('op-guide-faq-item')) {
                        clone.classList.add('open');
                        const qBtn = clone.querySelector('.op-guide-faq-q');
                        if (qBtn) {
                            qBtn.addEventListener('click', () => clone.classList.toggle('open'));
                        }
                    }
                    const badge = document.createElement('span');
                    badge.className = 'op-guide-tab-tag';
                    badge.textContent = match.getAttribute('data-tab-label') || 'คู่มือ';
                    clone.insertBefore(badge, clone.firstChild);
                    grid.appendChild(clone);
                });
                searchResults.appendChild(grid);
            }

            function escapeHtml(text) {
                return text
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');
            }

            if (searchInput) {
                searchInput.addEventListener('input', () => renderSearch(searchInput.value));
            }
            if (searchClear) {
                searchClear.addEventListener('click', () => {
                    searchInput.value = '';
                    renderSearch('');
                    searchInput.focus();
                });
            }
        })();
    </script>
    <?php
}
