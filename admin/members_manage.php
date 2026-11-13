<?php
require_once 'header.php';
requireShopRoles(['superadmin', 'admin', 'staff']);
$canManageLoyalty = hasAnyRole(['superadmin', 'admin']);
$tierCatalog = getMemberTierCatalog();
?>

<div class="stats-grid" style="margin-bottom: 20px;">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-address-card"></i></div>
        <div class="stat-info">
            <h4 id="mbrStatTotal">0</h4>
            <p>สมาชิกลูกค้าทั้งหมด</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-coins"></i></div>
        <div class="stat-info">
            <h4 id="mbrStatPoints">0</h4>
            <p>แต้มสะสมคงเหลือรวม</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-baht-sign"></i></div>
        <div class="stat-info">
            <h4 id="mbrStatSpent">฿0</h4>
            <p>ยอดซื้อสะสมจากสมาชิก</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fas fa-gem"></i></div>
        <div class="stat-info">
            <h4 id="mbrStatPremium">0</h4>
            <p>สมาชิก Gold / VIP</p>
        </div>
    </div>
</div>

<!-- Loyalty Rules Summary Banner -->
<div class="card" style="margin-bottom: 20px;">
    <div class="card-header" style="flex-wrap: wrap; gap: 10px;">
        <div>
            <h3 style="margin: 0 0 4px 0;"><i class="fas fa-gift" style="color: var(--primary);"></i> กติกาสะสมแต้ม & แลกส่วนลดสมาชิก</h3>
            <span style="font-size: 13.5px; color: var(--gray);" id="loyaltySummaryText">กำลังโหลดตั้งค่าระบบแต้ม...</span>
        </div>
        <?php if ($canManageLoyalty): ?>
        <button type="button" class="btn btn-outline btn-sm" onclick="openModal('loyaltySettingsModal')">
            <i class="fas fa-sliders"></i> ตั้งค่าอัตราสะสมแต้ม
        </button>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header" style="flex-wrap: wrap; gap: 10px;">
        <h3><i class="fas fa-users"></i> ทะเบียนสมาชิกลูกค้าประจำ (CRM)</h3>
        <button type="button" class="btn btn-primary" onclick="openAddMemberModal()">
            <i class="fas fa-user-plus"></i> สมัครสมาชิกใหม่
        </button>
    </div>
    <div class="card-body">
        <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 16px;">
            <input type="text" id="memberSearchInput" placeholder="ค้นหาด้วยเบอร์โทร, ชื่อ-นามสกุล, รหัสสมาชิก หรือ LINE ID..." oninput="loadMembers()"
                   style="flex: 1; min-width: 240px; padding: 10px 14px; border: 1.5px solid #E2E8F0; border-radius: 8px; font-family: inherit;">
            <select id="memberTierFilter" onchange="loadMembers()"
                    style="padding: 10px 14px; border: 1.5px solid #E2E8F0; border-radius: 8px; font-family: inherit;">
                <option value="">ทุกระดับสมาชิก</option>
                <?php foreach ($tierCatalog as $tKey => $tInfo): ?>
                    <option value="<?= htmlspecialchars($tKey) ?>"><?= htmlspecialchars($tInfo['label']) ?> (ส่วนลด <?= $tInfo['default_discount'] ?>%)</option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>รหัส / ชื่อสมาชิก</th>
                        <th>เบอร์โทรศัพท์</th>
                        <th>ระดับ (Tier)</th>
                        <th>ส่วนลดประจำตัว</th>
                        <th>แต้มสะสม</th>
                        <th>ยอดซื้อสะสม / ครั้ง</th>
                        <th>สถานะ</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody id="membersTableBody">
                    <tr><td colspan="8" class="text-center text-gray">กำลังโหลดข้อมูลสมาชิก...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add / Edit Member Modal -->
<div class="modal-overlay" id="memberModal">
    <div class="modal">
        <div class="modal-header">
            <h3 id="memberModalTitle"><i class="fas fa-user-plus"></i> สมัครสมาชิกใหม่</h3>
            <button type="button" class="modal-close" onclick="closeModal('memberModal')">&times;</button>
        </div>
        <form id="memberForm" onsubmit="saveMember(event)">
            <input type="hidden" id="memberId" value="">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                    <input type="text" id="memberFullName" required placeholder="เช่น คุณนภา ใจดี">
                </div>
                <div class="form-group">
                    <label>เบอร์โทรศัพท์ (ใช้ค้นหาหน้า POS) <span class="text-danger">*</span></label>
                    <input type="tel" id="memberPhone" required minlength="8" placeholder="0812345678">
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>รหัสสมาชิก (เว้นว่างให้สร้างอัตโนมัติ)</label>
                    <input type="text" id="memberCode" placeholder="เช่น VIP-001">
                </div>
                <div class="form-group">
                    <label>LINE ID</label>
                    <input type="text" id="memberLineId" placeholder="@lineid หรือชื่อไลน์">
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>ระดับสมาชิก (Tier)</label>
                    <select id="memberTier" onchange="applyDefaultTierDiscount()">
                        <option value="bronze">Bronze (ส่วนลดเริ่มต้น 0%)</option>
                        <option value="silver">Silver (ส่วนลดเริ่มต้น 3%)</option>
                        <option value="gold">Gold (ส่วนลดเริ่มต้น 5%)</option>
                        <option value="vip">VIP (ส่วนลดเริ่มต้น 10%)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>ส่วนลดอัตโนมัติ (%)</label>
                    <input type="number" id="memberDiscount" min="0" max="100" step="0.5" value="0">
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>อีเมล (ไม่บังคับ)</label>
                    <input type="email" id="memberEmail" placeholder="customer@example.com">
                </div>
                <div class="form-group" id="memberInitPointsGroup">
                    <label>แต้มต้อนรับเริ่มต้น</label>
                    <input type="number" id="memberInitPoints" min="0" value="0">
                </div>
                <div class="form-group" id="memberActiveGroup" style="display: none;">
                    <label>สถานะสมาชิก</label>
                    <select id="memberActive">
                        <option value="1">ใช้งานปกติ</option>
                        <option value="0">ระงับชั่วคราว</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>หมายเหตุ (เช่น แพ้อาหาร / สินค้าที่ชอบ)</label>
                <textarea id="memberNote" rows="2" placeholder="ระบุข้อมูลเพิ่มเติมสำหรับพนักงานหน้าร้าน..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('memberModal')">ยกเลิก</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> บันทึกข้อมูล</button>
            </div>
        </form>
    </div>
</div>

<!-- Adjust Points Modal -->
<div class="modal-overlay" id="adjustPointsModal">
    <div class="modal" style="max-width: 440px;">
        <div class="modal-header">
            <h3><i class="fas fa-coins"></i> ปรับแต้มสะสม - <span id="adjMemberName"></span></h3>
            <button type="button" class="modal-close" onclick="closeModal('adjustPointsModal')">&times;</button>
        </div>
        <form onsubmit="submitAdjustPoints(event)">
            <input type="hidden" id="adjMemberId" value="">
            <div style="background: #FFF7ED; padding: 12px 14px; border-radius: 10px; margin-bottom: 14px; font-size: 14px;">
                แต้มคงเหลือปัจจุบัน: <strong id="adjCurrentPoints" style="color: var(--primary); font-size: 18px;">0</strong> แต้ม
            </div>
            <div class="form-group">
                <label>จำนวนแต้มที่ต้องการปรับ (ใส่ค่าบวกเพื่อเพิ่ม หรือใส่ค่าลบ `-` เพื่อหักแต้ม)</label>
                <input type="number" id="adjDeltaPoints" required placeholder="เช่น 50 หรือ -20">
            </div>
            <div class="form-group">
                <label>เหตุผลในการปรับแต้ม <span class="text-danger">*</span></label>
                <input type="text" id="adjReason" required placeholder="เช่น แต้มพิเศษวันเกิด / แลกของสมนาคุณหน้าร้าน">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('adjustPointsModal')">ยกเลิก</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> ยืนยันปรับแต้ม</button>
            </div>
        </form>
    </div>
</div>

<!-- Member History Modal -->
<div class="modal-overlay" id="memberHistoryModal">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h3><i class="fas fa-clock-rotate-left"></i> ประวัติสมาชิก - <span id="histMemberName"></span></h3>
            <button type="button" class="modal-close" onclick="closeModal('memberHistoryModal')">&times;</button>
        </div>
        <div id="memberHistoryBody" style="display: flex; flex-direction: column; gap: 18px;">
            <div class="text-center text-gray">กำลังโหลดประวัติ...</div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeModal('memberHistoryModal')">ปิด</button>
        </div>
    </div>
</div>

<?php if ($canManageLoyalty): ?>
<!-- Loyalty Settings Modal -->
<div class="modal-overlay" id="loyaltySettingsModal">
    <div class="modal" style="max-width: 460px;">
        <div class="modal-header">
            <h3><i class="fas fa-sliders"></i> ตั้งค่ากติกาสะสมแต้มของร้าน</h3>
            <button type="button" class="modal-close" onclick="closeModal('loyaltySettingsModal')">&times;</button>
        </div>
        <form onsubmit="saveLoyaltySettings(event)">
            <div class="form-group">
                <label>ยอดซื้อกี่บาท ได้รับ 1 แต้ม (ใส่ 0 เพื่อปิดการสะสมแต้มอัตโนมัติ)</label>
                <input type="number" id="cfgPointRate" min="0" step="1" required value="25">
                <small style="color: var(--gray); font-size: 12px;">เช่น ใส่ 25 = ลูกค้าจ่ายทุก 25 บาท รับ 1 แต้ม</small>
            </div>
            <div class="form-group">
                <label>มูลค่าแลกส่วนลด (1 แต้ม = ส่วนลดกี่บาท)</label>
                <input type="number" id="cfgRedeemValue" min="0" step="0.25" required value="1">
                <small style="color: var(--gray); font-size: 12px;">เช่น ใส่ 1 = 100 แต้ม ใช้แลกส่วนลดได้ 100 บาท หน้าแคชเชียร์</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('loyaltySettingsModal')">ยกเลิก</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> บันทึกกติกา</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
const canManageLoyalty = <?= $canManageLoyalty ? 'true' : 'false' ?>;
const defaultDiscountsByTier = { bronze: 0, silver: 3, gold: 5, vip: 10 };
let membersCache = [];

function esc(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

async function loadMembers() {
    try {
        const q = document.getElementById('memberSearchInput').value.trim();
        const tier = document.getElementById('memberTierFilter').value;
        const params = new URLSearchParams();
        if (q) params.set('q', q);
        if (tier) params.set('tier', tier);

        const res = await API.get('members.php' + (params.toString() ? '?' + params.toString() : ''));
        const rows = res.data || [];
        membersCache = rows;

        if (res.stats) {
            document.getElementById('mbrStatTotal').textContent = res.stats.total_members;
            document.getElementById('mbrStatPoints').textContent = Number(res.stats.total_points || 0).toLocaleString();
            document.getElementById('mbrStatSpent').textContent = formatCurrency(res.stats.total_spent || 0);
            document.getElementById('mbrStatPremium').textContent = res.stats.premium_members;
        }

        if (res.loyalty_config) {
            const rate = parseInt(res.loyalty_config.member_point_rate, 10) || 0;
            const val = parseFloat(res.loyalty_config.member_redeem_value) || 0;
            const summary = rate > 0
                ? `ซื้อครบทุกๆ <strong>${formatCurrency(rate)}</strong> รับ <strong>1 แต้ม</strong> | แลกส่วนลด <strong>1 แต้ม = ${formatCurrency(val)}</strong> (คำนวณหลังหักส่วนลด Tier)`
                : `ปิดระบบสะสมแต้มอัตโนมัติ (ยังใช้ส่วนลดตามระดับ Tier ได้ตามปกติ)`;
            document.getElementById('loyaltySummaryText').innerHTML = summary;

            const cfgRateEl = document.getElementById('cfgPointRate');
            const cfgValEl = document.getElementById('cfgRedeemValue');
            if (cfgRateEl) cfgRateEl.value = rate;
            if (cfgValEl) cfgValEl.value = val;
        }

        const tbody = document.getElementById('membersTableBody');
        if (rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center text-gray">ยังไม่มีข้อมูลสมาชิก กด "สมัครสมาชิกใหม่" เพื่อเริ่มต้น</td></tr>';
            return;
        }

        tbody.innerHTML = rows.map(m => {
            const tInfo = m.tier_info || { label: m.tier, bg: '#F1F5F9', color: '#334155' };
            const statusBadge = m.is_active === 1
                ? '<span class="badge badge-available">ปกติ</span>'
                : '<span class="badge badge-cancelled">ระงับ</span>';

            return `<tr>
                <td>
                    <strong>${esc(m.full_name)}</strong>
                    <div style="font-size:12px;color:var(--gray);">${esc(m.member_code || '-')}${m.line_id ? ' · LINE: ' + esc(m.line_id) : ''}</div>
                </td>
                <td><code>${esc(m.phone)}</code></td>
                <td>
                    <span style="display:inline-block;padding:3px 10px;border-radius:999px;font-size:12px;font-weight:700;background:${esc(tInfo.bg)};color:${esc(tInfo.color)};">
                        ${esc(tInfo.label)}
                    </span>
                </td>
                <td>${m.discount_percent > 0 ? `<strong style="color:#E65100;">-${m.discount_percent}%</strong>` : '<span class="text-gray">0%</span>'}</td>
                <td><strong style="color:#FF6B35;font-size:15px;">${Number(m.points).toLocaleString()}</strong> แต้ม</td>
                <td>
                    <div>${formatCurrency(m.total_spent)}</div>
                    <div style="font-size:12px;color:var(--gray);">ใช้บริการ ${m.visit_count} ครั้ง</div>
                </td>
                <td>${statusBadge}</td>
                <td>
                    <div style="display:flex;gap:5px;flex-wrap:wrap;">
                        <button type="button" class="btn btn-sm btn-outline" onclick="openMemberHistory(${m.id})" title="ดูประวัติแต้มและบิล">
                            <i class="fas fa-history"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline" onclick="openEditMemberModal(${m.id})" title="แก้ไขข้อมูล">
                            <i class="fas fa-pen"></i>
                        </button>
                        ${canManageLoyalty ? `
                            <button type="button" class="btn btn-sm btn-warning" onclick="openAdjustPoints(${m.id})" title="เพิ่ม/หักแต้ม">
                                <i class="fas fa-coins"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteMember(${m.id}, '${esc(m.full_name)}')" title="ลบสมาชิก">
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

function applyDefaultTierDiscount() {
    if (document.getElementById('memberId').value) return;
    const tier = document.getElementById('memberTier').value;
    document.getElementById('memberDiscount').value = defaultDiscountsByTier[tier] ?? 0;
}

function openAddMemberModal() {
    document.getElementById('memberId').value = '';
    document.getElementById('memberModalTitle').innerHTML = '<i class="fas fa-user-plus"></i> สมัครสมาชิกใหม่';
    document.getElementById('memberForm').reset();
    document.getElementById('memberTier').value = 'bronze';
    document.getElementById('memberDiscount').value = '0';
    document.getElementById('memberInitPoints').value = '0';
    document.getElementById('memberInitPointsGroup').style.display = 'block';
    document.getElementById('memberActiveGroup').style.display = 'none';
    openModal('memberModal');
}

function openEditMemberModal(id) {
    const m = membersCache.find(item => item.id === id);
    if (!m) return;

    document.getElementById('memberId').value = m.id;
    document.getElementById('memberModalTitle').innerHTML = '<i class="fas fa-user-pen"></i> แก้ไขข้อมูลสมาชิก';
    document.getElementById('memberFullName').value = m.full_name;
    document.getElementById('memberPhone').value = m.phone;
    document.getElementById('memberCode').value = m.member_code || '';
    document.getElementById('memberLineId').value = m.line_id || '';
    document.getElementById('memberEmail').value = m.email || '';
    document.getElementById('memberTier').value = m.tier || 'bronze';
    document.getElementById('memberDiscount').value = m.discount_percent ?? 0;
    document.getElementById('memberNote').value = m.note || '';
    document.getElementById('memberActive').value = String(m.is_active);
    document.getElementById('memberInitPointsGroup').style.display = 'none';
    document.getElementById('memberActiveGroup').style.display = 'block';
    openModal('memberModal');
}

async function saveMember(event) {
    event.preventDefault();
    const id = document.getElementById('memberId').value;
    const payload = {
        full_name: document.getElementById('memberFullName').value.trim(),
        phone: document.getElementById('memberPhone').value.trim(),
        member_code: document.getElementById('memberCode').value.trim(),
        line_id: document.getElementById('memberLineId').value.trim(),
        email: document.getElementById('memberEmail').value.trim(),
        tier: document.getElementById('memberTier').value,
        discount_percent: parseFloat(document.getElementById('memberDiscount').value) || 0,
        note: document.getElementById('memberNote').value.trim()
    };

    try {
        if (id) {
            payload.id = parseInt(id, 10);
            payload.is_active = parseInt(document.getElementById('memberActive').value, 10);
            await API.put('members.php', payload);
            showToast('อัปเดตข้อมูลสมาชิกเรียบร้อย', 'success');
        } else {
            payload.points = parseInt(document.getElementById('memberInitPoints').value, 10) || 0;
            await API.post('members.php', payload);
            showToast('สมัครสมาชิกเรียบร้อยแล้ว', 'success');
        }
        closeModal('memberModal');
        await loadMembers();
    } catch (e) {
        // handled
    }
}

function openAdjustPoints(id) {
    const m = membersCache.find(item => item.id === id);
    if (!m) return;
    document.getElementById('adjMemberId').value = m.id;
    document.getElementById('adjMemberName').textContent = m.full_name;
    document.getElementById('adjCurrentPoints').textContent = Number(m.points).toLocaleString();
    document.getElementById('adjDeltaPoints').value = '';
    document.getElementById('adjReason').value = '';
    openModal('adjustPointsModal');
}

async function submitAdjustPoints(event) {
    event.preventDefault();
    const id = parseInt(document.getElementById('adjMemberId').value, 10);
    const delta = parseInt(document.getElementById('adjDeltaPoints').value, 10);
    const reason = document.getElementById('adjReason').value.trim();

    if (!delta) {
        showToast('กรุณาระบุจำนวนแต้มที่ไม่เป็น 0', 'warning');
        return;
    }

    try {
        await API.post('members.php?action=adjust_points', {
            member_id: id,
            change_points: delta,
            reason: reason
        });
        showToast('ปรับยอดแต้มเรียบร้อยแล้ว', 'success');
        closeModal('adjustPointsModal');
        await loadMembers();
    } catch (e) {
        // handled
    }
}

async function openMemberHistory(id) {
    const body = document.getElementById('memberHistoryBody');
    body.innerHTML = '<div class="text-center text-gray" style="padding:20px;">กำลังโหลดประวัติ...</div>';
    openModal('memberHistoryModal');

    try {
        const res = await API.get('members.php?action=history&member_id=' + id);
        const m = res.data.member;
        const logs = res.data.point_logs || [];
        const orders = res.data.orders || [];

        document.getElementById('histMemberName').textContent = `${m.full_name} (${m.phone})`;

        const logsHtml = logs.length === 0
            ? '<p class="text-gray" style="font-size:13px;">ยังไม่มีประวัติการเคลื่อนไหวแต้ม</p>'
            : logs.map(l => {
                const pts = parseInt(l.change_points, 10);
                const sign = pts > 0 ? `+${pts}` : `${pts}`;
                const cls = pts >= 0 ? 'color:#059669;' : 'color:#DC2626;';
                return `<div style="display:flex;justify-content:space-between;align-items:center;padding:8px 12px;background:#F8FAFC;border-radius:8px;font-size:13px;">
                    <div>
                        <div>${esc(l.reason)}</div>
                        <small class="text-gray">${esc(l.created_at)}${l.created_by_name ? ' · โดย ' + esc(l.created_by_name) : ''}</small>
                    </div>
                    <strong style="${cls}font-size:15px;">${sign} แต้ม</strong>
                </div>`;
            }).join('');

        const ordersHtml = orders.length === 0
            ? '<p class="text-gray" style="font-size:13px;">ยังไม่มีประวัติบิลสั่งซื้อ</p>'
            : `<div class="table-container"><table>
                <thead><tr><th>เลขบิล</th><th>ส่วนลด</th><th>ยอดสุทธิ</th><th>รับ/ใช้แต้ม</th><th>วันที่</th></tr></thead>
                <tbody>
                    ${orders.map(o => `<tr>
                        <td>#${esc(o.order_no)}</td>
                        <td>${parseFloat(o.discount_amount) > 0 ? formatCurrency(o.discount_amount) : '-'}</td>
                        <td><strong>${formatCurrency(o.total_amount)}</strong></td>
                        <td><span style="color:#059669;">+${o.points_earned}</span> / <span style="color:#DC2626;">-${o.points_redeemed}</span></td>
                        <td style="font-size:12px;">${esc(o.created_at)}</td>
                    </tr>`).join('')}
                </tbody>
            </table></div>`;

        body.innerHTML = `
            <div>
                <h4 style="margin-bottom:8px;"><i class="fas fa-coins" style="color:#FF6B35;"></i> ประวัติการได้รับ/ใช้แต้มล่าสุด</h4>
                <div style="display:flex;flex-direction:column;gap:6px;max-height:220px;overflow-y:auto;">${logsHtml}</div>
            </div>
            <div>
                <h4 style="margin-bottom:8px;"><i class="fas fa-receipt" style="color:#FF6B35;"></i> ประวัติคำสั่งซื้อของสมาชิก</h4>
                ${ordersHtml}
            </div>
        `;
    } catch (e) {
        body.innerHTML = '<div class="text-center text-danger">ไม่สามารถโหลดประวัติสมาชิกได้</div>';
    }
}

async function saveLoyaltySettings(event) {
    event.preventDefault();
    try {
        await API.post('members.php?action=loyalty_settings', {
            member_point_rate: parseInt(document.getElementById('cfgPointRate').value, 10) || 0,
            member_redeem_value: parseFloat(document.getElementById('cfgRedeemValue').value) || 0
        });
        showToast('บันทึกกติกาแต้มสมาชิกเรียบร้อยแล้ว', 'success');
        closeModal('loyaltySettingsModal');
        await loadMembers();
    } catch (e) {
        // handled
    }
}

async function deleteMember(id, name) {
    if (!confirm(`ต้องการลบสมาชิก "${name}" ออกจากระบบใช่หรือไม่? (ประวัติบิลเดิมยังคงอยู่)`)) return;
    try {
        await API.delete('members.php?id=' + id);
        showToast('ลบข้อมูลสมาชิกเรียบร้อย', 'success');
        await loadMembers();
    } catch (e) {
        // handled
    }
}

document.addEventListener('DOMContentLoaded', loadMembers);
</script>

<?php require_once 'footer.php'; ?>
