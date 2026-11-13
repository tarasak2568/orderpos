<?php require_once 'header.php'; ?>
<?php
require_once '../config/db.php';
$shopId = currentShopId();
$isRetail = (($currentShop['shop_type'] ?? 'restaurant') === 'retail');
$itemLabel = $isRetail ? 'สินค้า' : 'เมนูอาหาร';
$categories = fetchAll(query("SELECT * FROM categories WHERE shop_id = $shopId AND is_active = 1 ORDER BY sort_order ASC"));
?>

<div class="card">
    <div class="card-header" style="flex-wrap:wrap;gap:10px;">
        <h3><i class="fas <?= $isRetail ? 'fa-boxes-stacked' : 'fa-book' ?>"></i> จัดการ<?= $itemLabel ?> & สต๊อก</h3>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <button class="btn btn-outline" onclick="openModal('categoryModal')">
                <i class="fas fa-folder-plus"></i> เพิ่มหมวดหมู่
            </button>
            <button class="btn btn-primary" onclick="openAddMenuModal()">
                <i class="fas fa-plus"></i> เพิ่ม<?= $itemLabel ?>
            </button>
        </div>
    </div>
    <div class="card-body">
        <div style="margin-bottom:16px;display:flex;gap:10px;flex-wrap:wrap;">
            <input type="text" id="adminMenuSearch" placeholder="ค้นหาชื่อ<?= $itemLabel ?> หรือรหัสบาร์โค้ด (SKU)..." oninput="loadMenu()" style="flex:1;min-width:220px;padding:10px 14px;border:1.5px solid #E2E8F0;border-radius:8px;font-family:inherit;">
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>รหัส/บาร์โค้ด</th>
                        <th>ชื่อ<?= $itemLabel ?></th>
                        <th>หมวดหมู่</th>
                        <th>ราคา</th>
                        <th>สต๊อกคงเหลือ</th>
                        <th>แนะนำ/ขายดี</th>
                        <th>สถานะ</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody id="menuTableBody">
                    <tr><td colspan="8" class="text-center text-gray">กำลังโหลด...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal เพิ่มหมวดหมู่ -->
<div class="modal-overlay" id="categoryModal">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-folder-plus"></i> เพิ่มหมวดหมู่ใหม่</h3>
            <button class="modal-close" onclick="closeModal('categoryModal')">&times;</button>
        </div>
        <form id="categoryForm" onsubmit="saveCategory(event)">
            <div class="form-group">
                <label>ชื่อหมวดหมู่ *</label>
                <input type="text" id="catName" required placeholder="เช่น เครื่องดื่ม, ขนมขบเคี้ยว, ของใช้ประจำวัน">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('categoryModal')">ยกเลิก</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> บันทึกหมวดหมู่</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal เพิ่ม/แก้ไขเมนูหรือสินค้า -->
<div class="modal-overlay" id="menuModal">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h3 id="menuModalTitle">เพิ่ม<?= $itemLabel ?>ใหม่</h3>
            <button class="modal-close" onclick="closeModal('menuModal')">&times;</button>
        </div>
        <form id="menuForm" onsubmit="saveMenu(event)">
            <input type="hidden" id="menuId" value="">
            <div class="form-row">
                <div class="form-group">
                    <label>ชื่อ<?= $itemLabel ?> *</label>
                    <input type="text" id="menuName" required placeholder="ระบุชื่อ<?= $itemLabel ?>">
                </div>
                <div class="form-group">
                    <label>หมวดหมู่ *</label>
                    <select id="menuCategory" required>
                        <option value="">เลือกหมวดหมู่</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>รหัสสินค้า / บาร์โค้ด (SKU - ไม่บังคับ)</label>
                    <input type="text" id="menuSku" placeholder="สแกนหรือกรอกบาร์โค้ด เช่น 8850999320001">
                </div>
                <div class="form-group">
                    <label>จำนวนสต๊อกคงเหลือ (เว้นว่าง = ไม่จำกัดสต๊อก)</label>
                    <input type="number" id="menuStock" min="0" step="1" placeholder="ไม่จำกัดสต๊อก">
                </div>
            </div>
            <div class="form-group">
                <label>รายละเอียดเพิ่มเติม</label>
                <textarea id="menuDescription" placeholder="รายละเอียดขนาด รุ่น หรือคำอธิบาย (ไม่บังคับ)"></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>ราคาขาย (บาท) *</label>
                    <input type="number" id="menuPrice" step="0.25" required placeholder="0.00">
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <div style="display: flex; gap: 20px; padding-top: 8px;">
                        <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                            <input type="checkbox" id="menuRecommended" value="1"> สินค้าขายดี / แนะนำ
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('menuModal')">ยกเลิก</button>
                <button type="submit" class="btn btn-primary" id="menuSubmitBtn"><i class="fas fa-save"></i> บันทึก</button>
            </div>
        </form>
    </div>
</div>

<script>
let allLoadedItems = [];

function openAddMenuModal() {
    document.getElementById('menuForm').reset();
    document.getElementById('menuId').value = '';
    document.getElementById('menuModalTitle').textContent = 'เพิ่ม<?= $itemLabel ?>ใหม่';
    openModal('menuModal');
}

async function saveCategory(event) {
    event.preventDefault();
    const name = document.getElementById('catName').value.trim();
    if (!name) return;
    try {
        await API.post('categories.php', { name });
        showToast('เพิ่มหมวดหมู่เรียบร้อย', 'success');
        closeModal('categoryModal');
        setTimeout(() => location.reload(), 600);
    } catch (error) {
        showToast('ไม่สามารถเพิ่มหมวดหมู่ได้', 'error');
    }
}

async function loadMenu() {
    try {
        const q = document.getElementById('adminMenuSearch')?.value.trim() || '';
        const url = 'menu_items.php?all=1' + (q ? '&search=' + encodeURIComponent(q) : '');
        const result = await API.get(url);
        const tbody = document.getElementById('menuTableBody');
        
        if (!result.success || result.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center text-gray">ยังไม่มีรายการ<?= $itemLabel ?></td></tr>';
            return;
        }
        
        allLoadedItems = result.data;
        let html = '';
        result.data.forEach(item => {
            let stockBadge = '<span style="color:var(--gray);font-size:12px;">ไม่จำกัด</span>';
            if (item.stock_qty !== null && item.stock_qty !== undefined) {
                const badgeClass = item.stock_qty > 5 ? 'badge-available' : (item.stock_qty > 0 ? 'badge-preparing' : 'badge-cancelled');
                stockBadge = `<span class="badge ${badgeClass}">${item.stock_qty} ชิ้น</span>`;
            }

            html += `
                <tr>
                    <td><code style="font-size:12px;color:#475569;">${item.sku || '-'}</code></td>
                    <td><strong>${item.name}</strong></td>
                    <td>${item.category_name || '-'}</td>
                    <td class="text-primary">${formatCurrency(item.price)}</td>
                    <td>${stockBadge}</td>
                    <td>${item.is_recommended ? '<span class="badge badge-ready">แนะนำ</span>' : '-'}</td>
                    <td>${item.is_available ? '<span class="badge badge-available">พร้อมขาย</span>' : '<span class="badge badge-cancelled">หยุดขาย</span>'}</td>
                    <td class="actions">
                        <button class="btn btn-sm btn-info" onclick="editMenu(${item.id})"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-sm btn-danger" onclick="deleteMenu(${item.id})"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            `;
        });
        tbody.innerHTML = html;
    } catch (error) {
        document.getElementById('menuTableBody').innerHTML = '<tr><td colspan="8" class="text-center text-danger">โหลดข้อมูลไม่สำเร็จ</td></tr>';
    }
}

async function saveMenu(event) {
    event.preventDefault();
    const id = document.getElementById('menuId').value;
    const stockVal = document.getElementById('menuStock').value.trim();
    const data = {
        category_id: document.getElementById('menuCategory').value,
        sku: document.getElementById('menuSku').value.trim(),
        name: document.getElementById('menuName').value.trim(),
        description: document.getElementById('menuDescription').value.trim(),
        price: document.getElementById('menuPrice').value,
        stock_qty: stockVal === '' ? null : parseInt(stockVal, 10),
        is_recommended: document.getElementById('menuRecommended').checked ? 1 : 0
    };
    
    try {
        if (id) {
            data.id = id;
            await API.put('menu_items.php', data);
            showToast('อัปเดต<?= $itemLabel ?>เรียบร้อย', 'success');
        } else {
            await API.post('menu_items.php', data);
            showToast('เพิ่ม<?= $itemLabel ?>เรียบร้อย', 'success');
        }
        closeModal('menuModal');
        loadMenu();
        document.getElementById('menuForm').reset();
        document.getElementById('menuId').value = '';
    } catch (error) {
        showToast('ไม่สามารถบันทึกข้อมูลได้', 'error');
    }
}

async function editMenu(id) {
    const item = allLoadedItems.find(i => i.id == id);
    if (!item) return;
    
    document.getElementById('menuModalTitle').textContent = 'แก้ไข<?= $itemLabel ?>';
    document.getElementById('menuId').value = item.id;
    document.getElementById('menuName').value = item.name;
    document.getElementById('menuCategory').value = item.category_id;
    document.getElementById('menuSku').value = item.sku || '';
    document.getElementById('menuStock').value = (item.stock_qty !== null && item.stock_qty !== undefined) ? item.stock_qty : '';
    document.getElementById('menuDescription').value = item.description || '';
    document.getElementById('menuPrice').value = item.price;
    document.getElementById('menuRecommended').checked = item.is_recommended == 1;
    
    openModal('menuModal');
}

async function deleteMenu(id) {
    if (!confirm('คุณแน่ใจที่จะลบรายการนี้?')) return;
    try {
        await API.delete(`menu_items.php?id=${id}`);
        showToast('ลบรายการเรียบร้อย', 'success');
        loadMenu();
    } catch (error) {
        showToast('ไม่สามารถลบได้', 'error');
    }
}

document.addEventListener('DOMContentLoaded', loadMenu);
</script>

<?php require_once 'footer.php'; ?>
