<?php require_once 'header.php'; ?>
<?php
require_once '../config/db.php';
$categories = fetchAll(query("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC"));
?>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-book"></i> จัดการเมนูอาหาร</h3>
        <button class="btn btn-primary" onclick="openModal('menuModal')">
            <i class="fas fa-plus"></i> เพิ่มเมนู
        </button>
    </div>
    <div class="card-body">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>รูป</th>
                        <th>ชื่อเมนู</th>
                        <th>หมวดหมู่</th>
                        <th>ราคา</th>
                        <th>แนะนำ</th>
                        <th>พร้อมขาย</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody id="menuTableBody">
                    <tr><td colspan="7" class="text-center text-gray">กำลังโหลด...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal เพิ่ม/แก้ไขเมนู -->
<div class="modal-overlay" id="menuModal">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h3 id="menuModalTitle">เพิ่มเมนูใหม่</h3>
            <button class="modal-close" onclick="closeModal('menuModal')">&times;</button>
        </div>
        <form id="menuForm" onsubmit="saveMenu(event)">
            <input type="hidden" id="menuId" value="">
            <div class="form-row">
                <div class="form-group">
                    <label>ชื่อเมนู</label>
                    <input type="text" id="menuName" required placeholder="ชื่อเมนูอาหาร">
                </div>
                <div class="form-group">
                    <label>หมวดหมู่</label>
                    <select id="menuCategory" required>
                        <option value="">เลือกหมวดหมู่</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= $cat['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>รายละเอียด</label>
                <textarea id="menuDescription" placeholder="รายละเอียดเมนู (ไม่บังคับ)"></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>ราคา (บาท)</label>
                    <input type="number" id="menuPrice" step="0.50" required placeholder="0.00">
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <div style="display: flex; gap: 20px; padding-top: 8px;">
                        <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                            <input type="checkbox" id="menuRecommended" value="1"> เมนูแนะนำ
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
async function loadMenu() {
    try {
        const result = await API.get('menu_items.php');
        const tbody = document.getElementById('menuTableBody');
        
        if (!result.success || result.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-gray">ยังไม่มีเมนู</td></tr>';
            return;
        }
        
        let html = '';
        result.data.forEach(item => {
            html += `
                <tr>
                    <td><div style="width:40px;height:40px;background:var(--light-gray);border-radius:8px;display:flex;align-items:center;justify-content:center;"><i class="fas fa-utensils" style="color:var(--gray);"></i></div></td>
                    <td><strong>${item.name}</strong></td>
                    <td>${item.category_name || '-'}</td>
                    <td class="text-primary">${formatCurrency(item.price)}</td>
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
        document.getElementById('menuTableBody').innerHTML = '<tr><td colspan="7" class="text-center text-danger">โหลดข้อมูลไม่สำเร็จ</td></tr>';
    }
}

async function saveMenu(event) {
    event.preventDefault();
    const id = document.getElementById('menuId').value;
    const data = {
        category_id: document.getElementById('menuCategory').value,
        name: document.getElementById('menuName').value,
        description: document.getElementById('menuDescription').value,
        price: document.getElementById('menuPrice').value,
        is_recommended: document.getElementById('menuRecommended').checked ? 1 : 0
    };
    
    try {
        if (id) {
            data.id = id;
            await API.put('menu_items.php', data);
            showToast('อัปเดตเมนูเรียบร้อย', 'success');
        } else {
            await API.post('menu_items.php', data);
            showToast('เพิ่มเมนูเรียบร้อย', 'success');
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
    try {
        const result = await API.get('menu_items.php');
        const item = result.data.find(i => i.id == id);
        if (!item) return;
        
        document.getElementById('menuModalTitle').textContent = 'แก้ไขเมนู';
        document.getElementById('menuId').value = item.id;
        document.getElementById('menuName').value = item.name;
        document.getElementById('menuCategory').value = item.category_id;
        document.getElementById('menuDescription').value = item.description || '';
        document.getElementById('menuPrice').value = item.price;
        document.getElementById('menuRecommended').checked = item.is_recommended == 1;
        
        openModal('menuModal');
    } catch (error) {
        showToast('ไม่สามารถโหลดข้อมูลเมนู', 'error');
    }
}

async function deleteMenu(id) {
    if (!confirm('คุณแน่ใจที่จะลบเมนูนี้?')) return;
    try {
        await API.delete(`menu_items.php?id=${id}`);
        showToast('ลบเมนูเรียบร้อย', 'success');
        loadMenu();
    } catch (error) {
        showToast('ไม่สามารถลบเมนูได้', 'error');
    }
}

// Reset modal when closed
document.addEventListener('click', function(e) {
    if (e.target.id === 'menuModal' && !e.target.classList.contains('active')) {
        document.getElementById('menuForm').reset();
        document.getElementById('menuId').value = '';
        document.getElementById('menuModalTitle').textContent = 'เพิ่มเมนูใหม่';
    }
});

document.addEventListener('DOMContentLoaded', loadMenu);
</script>

<?php require_once 'footer.php'; ?>