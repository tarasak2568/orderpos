<?php
require_once 'header.php';
requireShopRoles(['superadmin', 'admin']);
?>

<div class="stats-grid" style="margin-bottom: 20px;">
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <h4 id="staffStatTotal">0</h4>
            <p>ทีมงานทั้งหมดในร้าน</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fas fa-user-shield"></i></div>
        <div class="stat-info">
            <h4 id="staffStatAdmin">0</h4>
            <p>เจ้าของร้าน / แอดมิน</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-cash-register"></i></div>
        <div class="stat-info">
            <h4 id="staffStatStaff">0</h4>
            <p>พนักงานขาย / แคชเชียร์</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-kitchen-set"></i></div>
        <div class="stat-info">
            <h4 id="staffStatKitchen">0</h4>
            <p>พนักงานครัว (KDS)</p>
        </div>
    </div>
</div>

<!-- Role Permission Overview -->
<div class="card" style="margin-bottom: 20px;">
    <div class="card-header">
        <h3><i class="fas fa-shield-halved"></i> คำอธิบายระดับสิทธิ์การใช้งาน (Roles)</h3>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 12px;">
            <?php foreach (['admin', 'staff', 'kitchen'] as $rKey): $rInfo = getRoleDisplayInfo($rKey); ?>
                <div style="padding: 12px 14px; border-radius: 10px; border: 1px solid #E2E8F0; background: #F8FAFC;">
                    <span style="display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; background: <?= htmlspecialchars($rInfo['bg']) ?>; color: <?= htmlspecialchars($rInfo['color']) ?>; margin-bottom: 6px;">
                        <?= htmlspecialchars($rInfo['label']) ?>
                    </span>
                    <div style="font-size: 13px; color: #475569; line-height: 1.5;"><?= htmlspecialchars($rInfo['desc']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header" style="flex-wrap: wrap; gap: 10px;">
        <h3><i class="fas fa-users-gear"></i> จัดการทีมงาน & บัญชีพนักงาน</h3>
        <button class="btn btn-primary" onclick="openAddStaffModal()">
            <i class="fas fa-user-plus"></i> เพิ่มพนักงานใหม่
        </button>
    </div>
    <div class="card-body">
        <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 16px;">
            <input type="text" id="staffSearchInput" placeholder="ค้นหาชื่อ-นามสกุล หรือ Username..." oninput="loadStaffList()"
                   style="flex: 1; min-width: 220px; padding: 10px 14px; border: 1.5px solid #E2E8F0; border-radius: 8px; font-family: inherit;">
            <select id="staffRoleFilter" onchange="loadStaffList()"
                    style="padding: 10px 14px; border: 1.5px solid #E2E8F0; border-radius: 8px; font-family: inherit;">
                <option value="">ทุกตำแหน่ง</option>
                <option value="admin">เจ้าของร้าน / แอดมิน</option>
                <option value="staff">พนักงานหน้าร้าน / แคชเชียร์</option>
                <option value="kitchen">พนักงานครัว</option>
            </select>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ชื่อ-นามสกุล</th>
                        <th>ชื่อผู้ใช้ (Username)</th>
                        <th>สิทธิ์ (Role)</th>
                        <th>สถานะ</th>
                        <th>วันที่เพิ่ม</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody id="staffTableBody">
                    <tr><td colspan="6" class="text-center text-gray">กำลังโหลดข้อมูลทีมงาน...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add / Edit Staff Modal -->
<div class="modal-overlay" id="staffModal">
    <div class="modal">
        <div class="modal-header">
            <h3 id="staffModalTitle"><i class="fas fa-user-plus"></i> เพิ่มพนักงานใหม่</h3>
            <button type="button" class="modal-close" onclick="closeModal('staffModal')">&times;</button>
        </div>
        <form id="staffForm" onsubmit="saveStaffMember(event)">
            <input type="hidden" id="staffId" value="">
            <div class="form-group">
                <label>ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                <input type="text" id="staffFullName" required placeholder="เช่น สมชาย ใจดี">
            </div>
            <div class="form-group">
                <label>ชื่อผู้ใช้สำหรับเข้าสู่ระบบ (Username) <span class="text-danger">*</span></label>
                <input type="text" id="staffUsername" required pattern="[a-zA-Z0-9_.\-]{3,50}" placeholder="ภาษาอังกฤษหรือตัวเลข เช่น cashier01">
                <small id="staffUsernameHint" style="color: var(--gray); font-size: 12px;">ใช้สำหรับล็อกอินในหน้า login.php (3-50 ตัวอักษร)</small>
            </div>
            <div class="form-group">
                <label id="staffPasswordLabel">รหัสผ่าน <span class="text-danger">*</span></label>
                <input type="password" id="staffPassword" minlength="6" placeholder="อย่างน้อย 6 ตัวอักษร">
                <small id="staffPasswordHint" style="color: var(--gray); font-size: 12px; display: none;">เว้นว่างไว้หากไม่ต้องการเปลี่ยนรหัสผ่าน</small>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>สิทธิ์การใช้งาน (Role)</label>
                    <select id="staffRole" required>
                        <option value="staff">พนักงานหน้าร้าน / แคชเชียร์</option>
                        <option value="kitchen">พนักงานครัว (KDS)</option>
                        <option value="admin">เจ้าของร้าน / แอดมิน</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>สถานะบัญชี</label>
                    <select id="staffActive">
                        <option value="1">เปิดใช้งาน</option>
                        <option value="0">ระงับชั่วคราว</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('staffModal')">ยกเลิก</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> บันทึกข้อมูล</button>
            </div>
        </form>
    </div>
</div>

<script>
let staffRowsCache = [];

function esc(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

async function loadStaffList() {
    try {
        const q = document.getElementById('staffSearchInput').value.trim();
        const role = document.getElementById('staffRoleFilter').value;
        const params = new URLSearchParams();
        if (q) params.set('q', q);
        if (role) params.set('role', role);

        const res = await API.get('staff.php' + (params.toString() ? '?' + params.toString() : ''));
        const rows = res.data || [];
        staffRowsCache = rows;

        if (!q && !role) {
            document.getElementById('staffStatTotal').textContent = rows.length;
            document.getElementById('staffStatAdmin').textContent = rows.filter(u => u.role === 'admin' || u.role === 'superadmin').length;
            document.getElementById('staffStatStaff').textContent = rows.filter(u => u.role === 'staff').length;
            document.getElementById('staffStatKitchen').textContent = rows.filter(u => u.role === 'kitchen').length;
        }

        const tbody = document.getElementById('staffTableBody');
        if (rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-gray">ไม่พบข้อมูลพนักงาน</td></tr>';
            return;
        }

        tbody.innerHTML = rows.map(u => {
            const rInfo = u.role_info || { label: u.role, bg: '#F1F5F9', color: '#475569' };
            const activeBadge = u.is_active === 1
                ? '<span class="badge badge-available">ใช้งานได้</span>'
                : '<span class="badge badge-cancelled">ระงับชั่วคราว</span>';
            const selfTag = u.is_self ? ' <span style="font-size:11px;color:#FF6B35;font-weight:600;">(คุณ)</span>' : '';
            const isSuper = u.role === 'superadmin';

            return `<tr>
                <td><strong>${esc(u.full_name)}</strong>${selfTag}</td>
                <td><code>${esc(u.username)}</code></td>
                <td>
                    <span style="display:inline-block;padding:3px 10px;border-radius:999px;font-size:12px;font-weight:600;background:${esc(rInfo.bg)};color:${esc(rInfo.color)};">
                        ${esc(rInfo.label)}
                    </span>
                </td>
                <td>${activeBadge}</td>
                <td style="font-size:13px;color:var(--gray);">${esc(u.created_at || '-')}</td>
                <td>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                        <button type="button" class="btn btn-sm btn-outline" onclick="openEditStaffModal(${u.id})" title="แก้ไข/เปลี่ยนรหัส">
                            <i class="fas fa-pen"></i> แก้ไข
                        </button>
                        ${!u.is_self && !isSuper ? `
                            <button type="button" class="btn btn-sm ${u.is_active === 1 ? 'btn-warning' : 'btn-success'}" onclick="toggleStaffActive(${u.id}, ${u.is_active === 1 ? 0 : 1})">
                                <i class="fas ${u.is_active === 1 ? 'fa-ban' : 'fa-check'}"></i> ${u.is_active === 1 ? 'ระงับ' : 'เปิดใช้'}
                            </button>
                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteStaffMember(${u.id}, '${esc(u.username)}')">
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

function openAddStaffModal() {
    document.getElementById('staffId').value = '';
    document.getElementById('staffModalTitle').innerHTML = '<i class="fas fa-user-plus"></i> เพิ่มพนักงานใหม่';
    document.getElementById('staffFullName').value = '';
    const uInput = document.getElementById('staffUsername');
    uInput.value = '';
    uInput.disabled = false;
    document.getElementById('staffPassword').value = '';
    document.getElementById('staffPassword').required = true;
    document.getElementById('staffPasswordLabel').innerHTML = 'รหัสผ่าน <span class="text-danger">*</span>';
    document.getElementById('staffPasswordHint').style.display = 'none';
    document.getElementById('staffRole').value = 'staff';
    document.getElementById('staffRole').disabled = false;
    document.getElementById('staffActive').value = '1';
    document.getElementById('staffActive').disabled = false;
    openModal('staffModal');
}

function openEditStaffModal(id) {
    const user = staffRowsCache.find(u => u.id === id);
    if (!user) return;

    document.getElementById('staffId').value = user.id;
    document.getElementById('staffModalTitle').innerHTML = '<i class="fas fa-user-pen"></i> แก้ไขข้อมูลพนักงาน';
    document.getElementById('staffFullName').value = user.full_name;
    const uInput = document.getElementById('staffUsername');
    uInput.value = user.username;
    uInput.disabled = true;

    const pInput = document.getElementById('staffPassword');
    pInput.value = '';
    pInput.required = false;
    document.getElementById('staffPasswordLabel').textContent = 'ตั้งรหัสผ่านใหม่ (ไม่บังคับ)';
    document.getElementById('staffPasswordHint').style.display = 'block';

    const roleSelect = document.getElementById('staffRole');
    if (user.role === 'superadmin') {
        roleSelect.disabled = true;
    } else {
        roleSelect.value = user.role;
        roleSelect.disabled = !!user.is_self;
    }

    const activeSelect = document.getElementById('staffActive');
    activeSelect.value = String(user.is_active);
    activeSelect.disabled = !!user.is_self;

    openModal('staffModal');
}

async function saveStaffMember(event) {
    event.preventDefault();
    const id = document.getElementById('staffId').value;
    const fullName = document.getElementById('staffFullName').value.trim();
    const username = document.getElementById('staffUsername').value.trim();
    const password = document.getElementById('staffPassword').value;
    const role = document.getElementById('staffRole').value;
    const isActive = parseInt(document.getElementById('staffActive').value, 10);

    try {
        if (id) {
            const payload = { id: parseInt(id, 10), full_name: fullName };
            if (!document.getElementById('staffRole').disabled) payload.role = role;
            if (!document.getElementById('staffActive').disabled) payload.is_active = isActive;
            if (password) payload.password = password;
            await API.put('staff.php', payload);
            showToast('อัปเดตข้อมูลพนักงานเรียบร้อย', 'success');
        } else {
            await API.post('staff.php', {
                full_name: fullName,
                username: username,
                password: password,
                role: role,
                is_active: isActive
            });
            showToast('เพิ่มพนักงานเรียบร้อยแล้ว', 'success');
        }
        closeModal('staffModal');
        await loadStaffList();
    } catch (err) {
        // Toast handled by API helper
    }
}

async function toggleStaffActive(id, nextActive) {
    try {
        await API.put('staff.php', { id: id, is_active: nextActive });
        showToast(nextActive === 1 ? 'เปิดใช้งานบัญชีแล้ว' : 'ระงับใช้งานชั่วคราวแล้ว', 'success');
        await loadStaffList();
    } catch (err) {
        // handled
    }
}

async function deleteStaffMember(id, username) {
    if (!confirm(`ต้องการลบบัญชีพนักงาน "${username}" ออกจากร้านใช่หรือไม่?`)) return;
    try {
        await API.delete('staff.php?id=' + id);
        showToast('ลบบัญชีพนักงานเรียบร้อย', 'success');
        await loadStaffList();
    } catch (err) {
        // handled
    }
}

document.addEventListener('DOMContentLoaded', loadStaffList);
</script>

<?php require_once 'footer.php'; ?>
