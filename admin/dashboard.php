<?php require_once 'header.php'; ?>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-chart-bar"></i> ภาพรวมร้านวันนี้</h3>
        <span style="color: var(--gray); font-size: 14px;"><?= date('d/m/Y') ?></span>
    </div>
    <div class="card-body">
        <div class="stats-grid" id="dashboardStats">
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-wallet"></i></div>
                <div class="stat-info">
                    <h4 id="todaySales">฿0</h4>
                    <p>ยอดขายวันนี้</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-clipboard-list"></i></div>
                <div class="stat-info">
                    <h4 id="todayOrders">0</h4>
                    <p>ออเดอร์วันนี้</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange"><i class="fas fa-clock"></i></div>
                <div class="stat-info">
                    <h4 id="pendingOrders">0</h4>
                    <p>รอดำเนินการ</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red"><i class="fas fa-chair"></i></div>
                <div class="stat-info">
                    <h4 id="occupiedTables">0</h4>
                    <p>โต๊ะที่ใช้งาน</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-history"></i> ออเดอร์ล่าสุด</h3>
    </div>
    <div class="card-body">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>เลขที่</th>
                        <th>โต๊ะ</th>
                        <th>ยอดรวม</th>
                        <th>สถานะ</th>
                        <th>เวลา</th>
                    </tr>
                </thead>
                <tbody id="recentOrders">
                    <tr><td colspan="5" class="text-center text-gray">กำลังโหลด...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
async function loadDashboard() {
    try {
        const result = await API.get('orders.php?action=dashboard');
        if (result.success) {
            const data = result.data;
            document.getElementById('todaySales').textContent = formatCurrency(data.today_sales);
            document.getElementById('todayOrders').textContent = data.today_orders;
            document.getElementById('pendingOrders').textContent = data.pending_orders;
            document.getElementById('occupiedTables').textContent = data.occupied_tables;
            
            const tbody = document.getElementById('recentOrders');
            if (data.recent_orders.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-gray">ยังไม่มีออเดอร์</td></tr>';
            } else {
                let html = '';
                data.recent_orders.forEach(order => {
                    const statusMap = {
                        'pending': 'รอดำเนินการ',
                        'preparing': 'กำลังทำ',
                        'ready': 'ทำเสร็จ',
                        'served': 'เสิร์ฟแล้ว',
                        'paid': 'ชำระแล้ว',
                        'cancelled': 'ยกเลิก'
                    };
                    html += `
                        <tr>
                            <td>#${order.order_no}</td>
                            <td>${order.table_name}</td>
                            <td class="text-primary">${formatCurrency(order.total_amount)}</td>
                            <td><span class="badge badge-${order.payment_status === 'paid' ? 'paid' : order.status}">${statusMap[order.status] || order.status}</span></td>
                            <td>${formatTime(order.created_at)}</td>
                        </tr>
                    `;
                });
                tbody.innerHTML = html;
            }
        }
    } catch (error) {
        console.error('Dashboard load error:', error);
    }
}

document.addEventListener('DOMContentLoaded', loadDashboard);
setInterval(loadDashboard, 30000);
</script>

<?php require_once 'footer.php'; ?>