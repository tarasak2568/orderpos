<?php require_once 'header.php'; ?>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-clipboard-list"></i> ดูออเดอร์ทั้งหมด</h3>
        <div style="display:flex;gap:8px;">
            <select id="orderFilter" onchange="loadOrders()" style="padding:8px;border:2px solid #E0E0E0;border-radius:8px;font-family:inherit;">
                <option value="">ทั้งหมด</option>
                <option value="pending">รอดำเนินการ</option>
                <option value="preparing">กำลังทำ</option>
                <option value="ready">ทำเสร็จ</option>
                <option value="served">เสิร์ฟแล้ว</option>
                <option value="paid">ชำระแล้ว</option>
                <option value="cancelled">ยกเลิก</option>
            </select>
        </div>
    </div>
    <div class="card-body">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>เลขที่</th>
                        <th>โต๊ะ</th>
                        <th>รายการ</th>
                        <th>ยอดรวม</th>
                        <th>สถานะ</th>
                        <th>การชำระ</th>
                        <th>เวลา</th>
                    </tr>
                </thead>
                <tbody id="ordersBody">
                    <tr><td colspan="7" class="text-center text-gray">กำลังโหลด...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
async function loadOrders() {
    try {
        const status = document.getElementById('orderFilter').value;
        let url = 'orders.php?action=list';
        if (status) url += '&status=' + status;
        
        const result = await API.get(url);
        const tbody = document.getElementById('ordersBody');
        
        if (!result.success || result.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-gray">ไม่มีออเดอร์</td></tr>';
            return;
        }
        
        const statusMap = {
            'pending': 'รอดำเนินการ', 'preparing': 'กำลังทำ', 'ready': 'ทำเสร็จ',
            'served': 'เสิร์ฟแล้ว', 'paid': 'ชำระแล้ว', 'cancelled': 'ยกเลิก'
        };
        
        let html = '';
        result.data.forEach(order => {
            const items = order.items || [];
            const itemList = items.map(i => `${i.quantity}x ${i.name}`).join(', ');
            html += `<tr>
                <td>#${order.order_no}</td>
                <td>${order.table_name}</td>
                <td style="font-size:13px;">${itemList}</td>
                <td class="text-primary">${formatCurrency(order.total_amount)}</td>
                <td><span class="badge badge-${order.status}">${statusMap[order.status] || order.status}</span></td>
                <td><span class="badge badge-${order.payment_status === 'paid' ? 'paid' : 'unpaid'}">${order.payment_status === 'paid' ? 'ชำระแล้ว' : 'ยังไม่ชำระ'}</span></td>
                <td>${formatDateTime(order.created_at)}</td>
            </tr>`;
        });
        tbody.innerHTML = html;
    } catch (error) {
        console.error(error);
    }
}

document.addEventListener('DOMContentLoaded', loadOrders);
setInterval(loadOrders, 30000);
</script>

<?php require_once 'footer.php'; ?>