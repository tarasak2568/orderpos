<?php require_once 'header.php'; ?>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-chair"></i> จัดการโต๊ะ</h3>
        <button class="btn btn-primary" onclick="openAddTableModal()">
            <i class="fas fa-plus"></i> เพิ่มโต๊ะ
        </button>
    </div>
    <div class="card-body">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>รหัสโต๊ะ</th>
                        <th>ชื่อโต๊ะ</th>
                        <th>ที่นั่ง</th>
                        <th>สถานะ</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <tr><td colspan="5" class="text-center text-gray">กำลังโหลด...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal-overlay" id="tableModal">
    <div class="modal">
        <div class="modal-header">
            <h3 id="tableModalTitle">เพิ่มโต๊ะ</h3>
            <button class="modal-close" onclick="closeModal('tableModal')">&times;</button>
        </div>
        <form id="tableForm" onsubmit="saveTable(event)">
            <input type="hidden" id="tableId" value="">
            <div class="form-group">
                <label>รหัสโต๊ะ</label>
                <input type="text" id="tableCode" required placeholder="เช่น T01, T02">
            </div>
            <div class="form-group">
                <label>ชื่อโต๊ะ</label>
                <input type="text" id="tableName" required placeholder="เช่น โต๊ะ 1, VIP 1">
            </div>
            <div class="form-group">
                <label>จำนวนที่นั่ง</label>
                <input type="number" id="tableCapacity" value="4" min="1">
            </div>
            <div class="form-group">
                <label>สถานะโต๊ะ</label>
                <select id="tableStatus">
                    <option value="available">ว่าง (พร้อมรับลูกค้า)</option>
                    <option value="occupied">ไม่ว่าง (มีลูกค้าใช้งาน)</option>
                    <option value="reserved">จองแล้ว</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('tableModal')">ยกเลิก</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> บันทึก</button>
            </div>
        </form>
    </div>
</div>

<script>
let loadedTables = [];

function escHtml(str) {
    return String(str ?? '').replace(/[&<>"']/g, s => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[s]));
}

function openAddTableModal() {
    document.getElementById('tableForm').reset();
    document.getElementById('tableId').value = '';
    document.getElementById('tableCapacity').value = 4;
    document.getElementById('tableStatus').value = 'available';
    document.getElementById('tableModalTitle').textContent = 'เพิ่มโต๊ะ';
    openModal('tableModal');
}

function editTable(id) {
    const t = loadedTables.find(row => row.id == id);
    if (!t) return;
    document.getElementById('tableId').value = t.id;
    document.getElementById('tableCode').value = t.table_code || '';
    document.getElementById('tableName').value = t.table_name || '';
    document.getElementById('tableCapacity').value = t.capacity || 4;
    document.getElementById('tableStatus').value = t.status || 'available';
    document.getElementById('tableModalTitle').textContent = 'แก้ไขข้อมูลโต๊ะ';
    openModal('tableModal');
}

async function loadTables() {
    try {
        const result = await API.get('tables.php');
        const tbody = document.getElementById('tableBody');
        if (!result.success || result.data.length === 0) {
            loadedTables = [];
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-gray">ยังไม่มีโต๊ะ</td></tr>';
            return;
        }
        loadedTables = result.data;
        let html = '';
        result.data.forEach(t => {
            const statusText = t.status === 'available' ? 'ว่าง' : t.status === 'occupied' ? 'ไม่ว่าง' : 'จอง';
            html += `<tr>
                <td><strong>${escHtml(t.table_code)}</strong></td>
                <td>${escHtml(t.table_name)}</td>
                <td>${escHtml(t.capacity)} ที่นั่ง</td>
                <td><span class="badge badge-${escHtml(t.status)}">${statusText}</span></td>
                <td class="actions">
                    <button class="btn btn-sm btn-info" onclick="editTable(${t.id})" title="แก้ไขโต๊ะ"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-sm btn-danger" onclick="deleteTable(${t.id})" title="ลบโต๊ะ"><i class="fas fa-trash"></i></button>
                </td>
            </tr>`;
        });
        tbody.innerHTML = html;
    } catch (error) {
        console.error(error);
    }
}

async function saveTable(event) {
    event.preventDefault();
    const id = document.getElementById('tableId').value.trim();
    const data = {
        table_code: document.getElementById('tableCode').value.trim(),
        table_name: document.getElementById('tableName').value.trim(),
        capacity: parseInt(document.getElementById('tableCapacity').value, 10) || 4,
        status: document.getElementById('tableStatus').value || 'available'
    };
    try {
        let res;
        if (id) {
            data.id = id;
            res = await API.put('tables.php', data);
        } else {
            res = await API.post('tables.php', data);
        }
        if (res && res.success === false) {
            showToast(res.error || 'ไม่สามารถบันทึกได้', 'error');
            return;
        }
        showToast(id ? 'อัปเดตข้อมูลโต๊ะเรียบร้อย' : 'เพิ่มโต๊ะเรียบร้อย', 'success');
        closeModal('tableModal');
        document.getElementById('tableForm').reset();
        document.getElementById('tableId').value = '';
        loadTables();
    } catch (error) {
        showToast('ไม่สามารถบันทึกได้', 'error');
    }
}

async function deleteTable(id) {
    if (!confirm('คุณแน่ใจที่จะลบโต๊ะนี้?')) return;
    try {
        await API.delete(`tables.php?id=${id}`);
        showToast('ลบโต๊ะเรียบร้อย', 'success');
        loadTables();
    } catch (error) {
        showToast('ไม่สามารถลบโต๊ะได้', 'error');
    }
}

document.addEventListener('DOMContentLoaded', loadTables);
</script>

<?php require_once 'footer.php'; ?>