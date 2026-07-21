<?php require_once 'header.php'; ?>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-qrcode"></i> QR Code โต๊ะ</h3>
        <button class="btn btn-primary" onclick="window.print()">
            <i class="fas fa-print"></i> พิมพ์ทั้งหมด
        </button>
    </div>
    <div class="card-body">
        <p style="color: var(--gray); margin-bottom: 20px;">
            <i class="fas fa-info-circle"></i> สแกน QR Code เพื่อดูเมนูและสั่งอาหารผ่านมือถือ
        </p>
        <div class="qr-grid" id="qrGrid">
            <div class="text-center text-gray" style="padding: 40px;">กำลังโหลด...</div>
        </div>
    </div>
</div>

<style>
.qr-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
}
.qr-card {
    background: white;
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    transition: transform 0.2s;
}
.qr-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.qr-card img {
    width: 200px;
    height: 200px;
    margin: 10px 0;
    border-radius: 8px;
}
.qr-card h3 {
    margin: 0 0 5px 0;
    font-size: 18px;
}
.qr-card p {
    color: var(--gray);
    font-size: 13px;
    margin: 0 0 10px 0;
}
.qr-card .qr-url {
    font-size: 11px;
    color: var(--gray);
    word-break: break-all;
    background: #f5f5f5;
    padding: 5px 8px;
    border-radius: 4px;
    margin-bottom: 10px;
}
.qr-card .btn-sm {
    font-size: 12px;
    padding: 5px 12px;
}
@media print {
    .navbar, .card-header .btn, .qr-card .btn-sm, .qr-card .qr-url { display: none; }
    .qr-grid { grid-template-columns: repeat(3, 1fr); }
    .qr-card { break-inside: avoid; border: 1px solid #ddd; }
}
</style>

<script>
async function loadQRCodes() {
    try {
        const result = await API.get('qr_tables.php');
        const grid = document.getElementById('qrGrid');
        
        if (!result.success || result.data.length === 0) {
            grid.innerHTML = '<div class="text-center text-gray" style="padding: 40px;">ยังไม่มีโต๊ะในระบบ</div>';
            return;
        }
        
        let html = '';
        result.data.forEach(table => {
            html += `
                <div class="qr-card">
                    <h3>${table.table_name}</h3>
                    <p>รหัส: ${table.table_code} | ${table.capacity} ที่นั่ง</p>
                    <img src="${table.qr_image}" alt="QR Code ${table.table_code}" 
                         onerror="this.src='https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(table.qr_url)}'">
                    <div class="qr-url">${table.qr_url}</div>
                    <button class="btn btn-sm btn-primary" onclick="downloadQR('${table.qr_image}', '${table.table_code}')">
                        <i class="fas fa-download"></i> ดาวน์โหลด
                    </button>
                    <button class="btn btn-sm btn-outline" onclick="copyURL('${table.qr_url}')">
                        <i class="fas fa-copy"></i> คัดลอกลิงก์
                    </button>
                </div>
            `;
        });
        grid.innerHTML = html;
    } catch (error) {
        console.error(error);
        document.getElementById('qrGrid').innerHTML = '<div class="text-center text-danger" style="padding: 40px;">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>';
    }
}

function downloadQR(imageUrl, name) {
    const link = document.createElement('a');
    link.href = imageUrl;
    link.download = `QR_${name}.png`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function copyURL(url) {
    navigator.clipboard.writeText(url).then(() => {
        showToast('คัดลอกลิงก์เรียบร้อย', 'success');
    }).catch(() => {
        showToast('ไม่สามารถคัดลอกได้', 'error');
    });
}

document.addEventListener('DOMContentLoaded', loadQRCodes);
</script>

<?php require_once 'footer.php'; ?>