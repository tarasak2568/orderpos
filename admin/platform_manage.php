<?php
require_once 'header.php';
requireShopRoles(['superadmin'], 'dashboard.php');
?>

<div class="stats-grid" style="margin-bottom: 20px;">
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-store"></i></div>
        <div class="stat-info">
            <h4 id="pfTotalShops">0</h4>
            <p>ร้านค้าสมาชิกทั้งหมด (<span id="pfActiveShops">0</span> เปิดอยู่)</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <h4 id="pfTotalUsers">0</h4>
            <p>บัญชีผู้ใช้งาน / พนักงานรวม</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fas fa-address-card"></i></div>
        <div class="stat-info">
            <h4 id="pfTotalMembers">0</h4>
            <p>สมาชิกลูกค้า CRM รวม</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-sack-dollar"></i></div>
        <div class="stat-info">
            <h4 id="pfTotalRevenue">฿0</h4>
            <p>ยอดขายชำระแล้วทั้งระบบ</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header" style="flex-wrap: wrap; gap: 10px;">
        <div>
            <h3 style="margin: 0 0 4px 0;"><i class="fas fa-crown" style="color: #7C3AED;"></i> จัดการร้านค้าสมาชิกทั้งหมด (Super Admin)</h3>
            <span style="font-size: 13px; color: var(--gray);">ดูรายชื่อร้านค้า ปรับแพ็กเกจ ระงับร้าน ช่วยรีเซ็ตรหัสผ่านเจ้าของร้าน หรือสลับเข้าไปจัดการร้านใดๆ ได้ทันที</span>
        </div>
        <button type="button" class="btn btn-primary" onclick="openModal('createShopModal')">
            <i class="fas fa-plus"></i> สร้างร้านค้าสมาชิกใหม่
        </button>
    </div>
    <div class="card-body">
        <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 16px;">
            <input type="text" id="pfSearch" placeholder="ค้นหาชื่อร้าน, slug, เบอร์โทร..." oninput="loadPlatformShops()"
                   style="flex: 1; min-width: 220px; padding: 10px 14px; border: 1.5px solid #E2E8F0; border-radius: 8px; font-family: inherit;">
            <select id="pfTypeFilter" onchange="loadPlatformShops()" style="padding: 10px 12px; border: 1.5px solid #E2E8F0; border-radius: 8px; font-family: inherit;">
                <option value="">ทุกประเภทธุรกิจ</option>
                <option value="restaurant">ร้านอาหาร & คาเฟ่</option>
                <option value="retail">ร้านค้าปลีก / ของชำ</option>
            </select>
            <select id="pfPlanFilter" onchange="loadPlatformShops()" style="padding: 10px 12px; border: 1.5px solid #E2E8F0; border-radius: 8px; font-family: inherit;">
                <option value="">ทุกแพ็กเกจ</option>
                <option value="free">Free</option>
                <option value="pro">Pro</option>
                <option value="enterprise">Enterprise</option>
            </select>
            <select id="pfStatusFilter" onchange="loadPlatformShops()" style="padding: 10px 12px; border: 1.5px solid #E2E8F0; border-radius: 8px; font-family: inherit;">
                <option value="">ทุกสถานะ</option>
                <option value="1">เปิดใช้งาน</option>
                <option value="0">ถูกระงับ</option>
            </select>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID / ร้านค้า</th>
                        <th>ประเภท & แพ็กเกจ</th>
                        <th>บัญชีหลัก & สถิติ</th>
                        <th>ยอดขายสะสม</th>
                        <th>สถานะ</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody id="platformShopsBody">
                    <tr><td colspan="6" class="text-center text-gray">กำลังโหลดข้อมูลร้านค้า...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create New Tenant Shop Modal -->
<div class="modal-overlay" id="createShopModal">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-store"></i> สร้างร้านค้าสมาชิกใหม่</h3>
            <button type="button" class="modal-close" onclick="closeModal('createShopModal')">&times;</button>
        </div>
        <form id="createShopForm" onsubmit="createPlatformShop(event)">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>ชื่อร้านค้า <span class="text-danger">*</span></label>
                    <input type="text" id="newShopName" required placeholder="เช่น คาเฟ่สุขใจ">
                </div>
                <div class="form-group">
                    <label>URL Slug (เว้นว่างให้สร้างอัตโนมัติ)</label>
                    <input type="text" id="newShopSlug" placeholder="cafe-sukjai">
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>ประเภทธุรกิจ</label>
                    <select id="newShopType">
                        <option value="restaurant">ร้านอาหาร / คาเฟ่</option>
                        <option value="retail">ร้านค้าปลีก / มินิมาร์ท</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>แพ็กเกจ</label>
                    <select id="newShopPlan">
                        <option value="free">Free</option>
                        <option value="pro">Pro</option>
                        <option value="enterprise">Enterprise</option>
                    </select>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>เบอร์โทรร้าน</label>
                    <input type="text" id="newShopPhone" placeholder="0812345678">
                </div>
                <div class="form-group">
                    <label>ที่อยู่ร้าน</label>
                    <input type="text" id="newShopAddress" placeholder="จังหวัด/ย่าน">
                </div>
            </div>
            <hr style="margin: 12px 0; border-color: #E2E8F0;">
            <div class="form-group">
                <label>ชื่อเจ้าของร้าน (แสดงผล)</label>
                <input type="text" id="newOwnerName" placeholder="ชื่อเจ้าของร้าน">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>Username เจ้าของร้าน <span class="text-danger">*</span></label>
                    <input type="text" id="newOwnerUsername" required pattern="[a-zA-Z0-9_.\-]{3,50}" placeholder="owner01">
                </div>
                <div class="form-group">
                    <label>รหัสผ่านเริ่มต้น <span class="text-danger">*</span></label>
                    <input type="password" id="newOwnerPassword" required minlength="6" placeholder="อย่างน้อย 6 ตัวอักษร">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('createShopModal')">ยกเลิก</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-plus-circle"></i> สร้างร้านค้า</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Shop Modal -->
<div class="modal-overlay" id="editShopModal">
    <div class="modal" style="max-width: 460px;">
        <div class="modal-header">
            <h3><i class="fas fa-pen"></i> แก้ไขแพ็กเกจ & สถานะร้านค้า</h3>
            <button type="button" class="modal-close" onclick="closeModal('editShopModal')">&times;</button>
        </div>
        <form onsubmit="saveEditShop(event)">
            <input type="hidden" id="editShopId" value="">
            <div class="form-group">
                <label>ชื่อร้านค้า</label>
                <input type="text" id="editShopName" required>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>ประเภทธุรกิจ</label>
                    <select id="editShopType">
                        <option value="restaurant">ร้านอาหาร / คาเฟ่</option>
                        <option value="retail">ร้านค้าปลีก / ของชำ</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>แพ็กเกจ</label>
                    <select id="editShopPlan">
                        <option value="free">Free</option>
                        <option value="pro">Pro</option>
                        <option value="enterprise">Enterprise</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>สถานะร้านค้า</label>
                <select id="editShopActive">
                    <option value="1">เปิดใช้งานปกติ</option>
                    <option value="0">ระงับร้าน (ล็อกอินเข้าใช้งานไม่ได้)</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('editShopModal')">ยกเลิก</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> บันทึก</button>
            </div>
        </form>
    </div>
</div>

<!-- View Shop Users & Reset Password Modal -->
<div class="modal-overlay" id="shopUsersModal">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h3><i class="fas fa-users"></i> บัญชีผู้ใช้งานในร้าน: <span id="suShopTitle"></span></h3>
            <button type="button" class="modal-close" onclick="closeModal('shopUsersModal')">&times;</button>
        </div>
        <div id="shopUsersBody" class="table-container">
            <div class="text-center text-gray" style="padding: 20px;">กำลังโหลด...</div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeModal('shopUsersModal')">ปิด</button>
        </div>
    </div>
</div>

<script>
let shopsCache = [];

function esc(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

async function loadPlatformShops() {
    try {
        const params = new URLSearchParams();
        const q = document.getElementById('pfSearch').value.trim();
        const type = document.getElementById('pfTypeFilter').value;
        const plan = document.getElementById('pfPlanFilter').value;
        const status = document.getElementById('pfStatusFilter').value;
        if (q) params.set('q', q);
        if (type) params.set('shop_type', type);
        if (plan) params.set('plan_type', plan);
        if (status !== '') params.set('status', status);

        const res = await API.get('platform_shops.php' + (params.toString() ? '?' + params.toString() : ''));
        const rows = res.data || [];
        shopsCache = rows;

        if (res.stats) {
            document.getElementById('pfTotalShops').textContent = res.stats.total_shops;
            document.getElementById('pfActiveShops').textContent = res.stats.active_shops;
            document.getElementById('pfTotalUsers').textContent = res.stats.total_users;
            document.getElementById('pfTotalMembers').textContent = res.stats.total_members;
            document.getElementById('pfTotalRevenue').textContent = formatCurrency(res.stats.total_revenue || 0);
        }

        const tbody = document.getElementById('platformShopsBody');
        if (rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-gray">ไม่พบร้านค้าตามเงื่อนไข</td></tr>';
            return;
        }

        const planStyles = {
            free: 'background:#F1F5F9;color:#475569;',
            pro: 'background:#DBEAFE;color:#1D4ED8;',
            enterprise: 'background:#EDE9FE;color:#6D28D9;'
        };

        tbody.innerHTML = rows.map(s => {
            const currentBadge = s.is_current_session
                ? '<span style="display:inline-block;margin-left:6px;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:700;background:#FFF3ED;color:#C2410C;">กำลังจัดการอยู่</span>'
                : '';
            const typeBadge = s.shop_type === 'retail'
                ? '<span style="font-size:12px;color:#0369A1;"><i class="fas fa-boxes-stacked"></i> ค้าปลีก</span>'
                : '<span style="font-size:12px;color:#C2410C;"><i class="fas fa-utensils"></i> ร้านอาหาร</span>';
            const statusBadge = s.is_active === 1
                ? '<span class="badge badge-available">เปิดใช้งาน</span>'
                : '<span class="badge badge-cancelled">ถูกระงับ</span>';

            return `<tr>
                <td>
                    <strong>#${s.id} ${esc(s.shop_name)}</strong>${currentBadge}
                    <div style="font-size:12px;margin-top:2px;">
                        <a href="../shop/${encodeURIComponent(s.shop_slug)}" target="_blank" style="color:var(--primary);text-decoration:none;">
                            /shop/${esc(s.shop_slug)} <i class="fas fa-external-link-alt" style="font-size:10px;"></i>
                        </a>
                        ${s.phone ? ' · ' + esc(s.phone) : ''}
                    </div>
                </td>
                <td>
                    <div>${typeBadge}</div>
                    <span style="display:inline-block;margin-top:4px;padding:2px 10px;border-radius:999px;font-size:11px;font-weight:700;text-transform:uppercase;${planStyles[s.plan_type] || planStyles.free}">
                        ${esc(s.plan_type || 'free')}
                    </span>
                </td>
                <td style="font-size:13px;">
                    <div>Owner: <code>${esc(s.owner_username || '-')}</code></div>
                    <div class="text-gray" style="font-size:12px;">ทีมงาน ${s.users_count} · ลูกค้า CRM ${s.members_count} · ออเดอร์ ${s.orders_count}</div>
                </td>
                <td><strong class="text-primary">${formatCurrency(s.paid_revenue)}</strong></td>
                <td>${statusBadge}</td>
                <td>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                        ${!s.is_current_session ? `
                            <button type="button" class="btn btn-sm btn-primary" onclick="switchToShop(${s.id})" title="สลับเข้าไปจัดการร้านนี้">
                                <i class="fas fa-right-to-bracket"></i> เข้าร้าน
                            </button>
                        ` : ''}
                        <button type="button" class="btn btn-sm btn-outline" onclick="openShopUsers(${s.id}, '${esc(s.shop_name)}')" title="ดูบัญชีและรีเซ็ตรหัส">
                            <i class="fas fa-users"></i> บัญชี
                        </button>
                        <button type="button" class="btn btn-sm btn-outline" onclick="openEditShopModal(${s.id})" title="แก้ไขแพ็กเกจ/สถานะ">
                            <i class="fas fa-pen"></i>
                        </button>
                        ${s.id > 1 && !s.is_current_session ? `
                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteShop(${s.id}, '${esc(s.shop_name)}')" title="ลบร้านค้า">
                                <i class="fas fa-trash"></i>
                            </button>
                        ` : ''}
                    </div>
                </td>
            </tr>`;
        }).join('');
    } catch (e) {
        console.error(e);
    }
}

async function switchToShop(shopId) {
    try {
        const res = await API.post('platform_shops.php?action=switch_shop', { shop_id: shopId });
        showToast(res.message || 'สลับร้านค้าเรียบร้อย', 'success');
        setTimeout(() => { window.location.href = 'dashboard.php'; }, 600);
    } catch (e) {
        // handled
    }
}

function openEditShopModal(id) {
    const s = shopsCache.find(item => item.id === id);
    if (!s) return;
    document.getElementById('editShopId').value = s.id;
    document.getElementById('editShopName').value = s.shop_name;
    document.getElementById('editShopType').value = s.shop_type || 'restaurant';
    document.getElementById('editShopPlan').value = s.plan_type || 'free';
    const actEl = document.getElementById('editShopActive');
    actEl.value = String(s.is_active);
    actEl.disabled = (s.id === 1);
    openModal('editShopModal');
}

async function saveEditShop(event) {
    event.preventDefault();
    const id = parseInt(document.getElementById('editShopId').value, 10);
    const payload = {
        id: id,
        shop_name: document.getElementById('editShopName').value.trim(),
        shop_type: document.getElementById('editShopType').value,
        plan_type: document.getElementById('editShopPlan').value
    };
    if (!document.getElementById('editShopActive').disabled) {
        payload.is_active = parseInt(document.getElementById('editShopActive').value, 10);
    }
    try {
        await API.put('platform_shops.php', payload);
        showToast('บันทึกข้อมูลร้านค้าเรียบร้อย', 'success');
        closeModal('editShopModal');
        await loadPlatformShops();
    } catch (e) {
        // handled
    }
}

async function createPlatformShop(event) {
    event.preventDefault();
    try {
        await API.post('platform_shops.php?action=create_shop', {
            shop_name: document.getElementById('newShopName').value.trim(),
            shop_slug: document.getElementById('newShopSlug').value.trim(),
            shop_type: document.getElementById('newShopType').value,
            plan_type: document.getElementById('newShopPlan').value,
            phone: document.getElementById('newShopPhone').value.trim(),
            address: document.getElementById('newShopAddress').value.trim(),
            owner_name: document.getElementById('newOwnerName').value.trim(),
            owner_username: document.getElementById('newOwnerUsername').value.trim(),
            owner_password: document.getElementById('newOwnerPassword').value
        });
        showToast('สร้างร้านค้าสมาชิกใหม่เรียบร้อยแล้ว', 'success');
        closeModal('createShopModal');
        document.getElementById('createShopForm').reset();
        await loadPlatformShops();
    } catch (e) {
        // handled
    }
}

async function openShopUsers(shopId, shopName) {
    document.getElementById('suShopTitle').textContent = shopName;
    const body = document.getElementById('shopUsersBody');
    body.innerHTML = '<div class="text-center text-gray" style="padding:20px;">กำลังโหลด...</div>';
    openModal('shopUsersModal');

    try {
        const res = await API.get('platform_shops.php?action=shop_users&shop_id=' + shopId);
        const users = res.data || [];
        if (users.length === 0) {
            body.innerHTML = '<div class="text-center text-gray" style="padding:20px;">ไม่พบบัญชีผู้ใช้ในร้านนี้</div>';
            return;
        }
        body.innerHTML = `<table>
            <thead><tr><th>ชื่อ</th><th>Username</th><th>สิทธิ์</th><th>สถานะ</th><th>รีเซ็ตรหัสผ่าน</th></tr></thead>
            <tbody>
                ${users.map(u => {
                    const rInfo = u.role_info || { label: u.role, bg: '#F1F5F9', color: '#475569' };
                    return `<tr>
                        <td><strong>${esc(u.full_name)}</strong></td>
                        <td><code>${esc(u.username)}</code></td>
                        <td><span style="padding:2px 8px;border-radius:999px;font-size:12px;background:${esc(rInfo.bg)};color:${esc(rInfo.color)};">${esc(rInfo.label)}</span></td>
                        <td>${u.is_active === 1 ? '<span class="badge badge-available">ปกติ</span>' : '<span class="badge badge-cancelled">ระงับ</span>'}</td>
                        <td>
                            <button type="button" class="btn btn-sm btn-warning" onclick="resetShopUserPassword(${u.id}, '${esc(u.username)}')">
                                <i class="fas fa-key"></i> ตั้งรหัสใหม่
                            </button>
                        </td>
                    </tr>`;
                }).join('')}
            </tbody>
        </table>`;
    } catch (e) {
        body.innerHTML = '<div class="text-center text-danger" style="padding:20px;">โหลดข้อมูลบัญชีไม่สำเร็จ</div>';
    }
}

async function resetShopUserPassword(userId, username) {
    const newPass = prompt(`ตั้งรหัสผ่านใหม่สำหรับผู้ใช้ "${username}" (อย่างน้อย 6 ตัวอักษร):`);
    if (!newPass) return;
    if (newPass.length < 6) {
        showToast('รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร', 'warning');
        return;
    }
    try {
        await API.post('platform_shops.php?action=reset_user_password', {
            user_id: userId,
            password: newPass
        });
        showToast(`รีเซ็ตรหัสผ่านของ ${username} เรียบร้อยแล้ว`, 'success');
    } catch (e) {
        // handled
    }
}

async function deleteShop(shopId, shopName) {
    if (!confirm(`ยืนยันลบร้าน "${shopName}" (#${shopId}) และข้อมูลเมนู/ออเดอร์ทั้งหมดของร้านนี้ถาวร?`)) return;
    try {
        await API.delete('platform_shops.php?id=' + shopId);
        showToast('ลบร้านค้าเรียบร้อยแล้ว', 'success');
        await loadPlatformShops();
    } catch (e) {
        // handled
    }
}

document.addEventListener('DOMContentLoaded', loadPlatformShops);
</script>

<?php require_once 'footer.php'; ?>
