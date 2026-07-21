<?php require_once 'header.php'; ?>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-chair"></i> จัดการโต๊ะ</h3>
        <button class="btn btn-primary" onclick="openModal('tableModal')">
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
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('tableModal')">ยกเลิก</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> บันทึก</button>
            </div>
        </form>
    </div>
</div>

<script>
async function loadTables() {
    try {
        const result = await API.get('tables.php');
        const tbody = document.getElementById('tableBody');
        if (!result.success || result.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-gray">ยังไม่มีโต๊ะ</td></tr>';
            return;
        }
        let html = '';
        result.data.forEach(t => {
            const statusText = t.status === 'available' ? 'ว่าง' : t.status === 'occupied' ? 'ไม่ว่าง' : 'จอง';
            html += `<tr>
                <td><strong>${t.table_code}</strong></td>
                <td>${t.table_name}</td>
                <td>${t.capacity} ที่นั่ง</td>
                <td><span class="badge badge-${t.status}">${statusText}</span></td>
                <td class="actions">
                    <button class="btn btn-sm btn-danger" onclick="deleteTable(${t.id})"><i class="fas fa-trash"></i></button>
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
    const data = {
        table_code: document.getElementById('tableCode').value,
        table_name: document.getElementById('tableName').value,
        capacity: document.getElementById('tableCapacity').value
    };
    try {
        await API.post('tables.php', data);
        showToast('เพิ่มโต๊ะเรียบร้อย', 'success');
        closeModal('tableModal');
        document.getElementById('tableForm').reset();
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