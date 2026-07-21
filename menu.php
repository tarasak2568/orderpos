<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>เมนูอาหาร</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="menu-page">
        <!-- Header -->
        <div class="menu-header">
            <h1><i class="fas fa-utensils"></i> <span id="shopName">เมนูอาหาร</span></h1>
            <div class="table-info" id="tableInfo"></div>
        </div>
        
        <!-- Categories -->
        <div class="menu-categories"></div>
        
        <!-- Menu Items -->
        <div class="menu-items"></div>
        
        <!-- Cart Bar -->
        <div class="cart-bar">
            <div class="cart-info" onclick="customerMenu.toggleCart()">
                <div class="cart-count">0</div>
                <span>ดูตะกร้า</span>
            </div>
            <div class="cart-total">฿0</div>
            <button class="btn btn-primary" onclick="customerMenu.toggleCart()">
                <i class="fas fa-shopping-cart"></i> สั่งเลย
            </button>
        </div>
        
        <!-- Cart Sidebar -->
        <div class="cart-sidebar">
            <div class="cart-sidebar-header">
                <h3><i class="fas fa-shopping-cart"></i> ตะกร้าสินค้า</h3>
                <button class="modal-close" onclick="customerMenu.toggleCart()">&times;</button>
            </div>
            <div class="cart-items"></div>
            <div class="cart-sidebar-footer">
                <div class="total-row">
                    <span>รวมทั้งสิ้น</span>
                    <span>฿0</span>
                </div>
                <div class="form-group">
                    <textarea id="orderNote" placeholder="หมายเหตุเพิ่มเติม (ไม่บังคับ)" style="width:100%;padding:10px;border:2px solid #E0E0E0;border-radius:8px;font-family:inherit;resize:vertical;min-height:60px;"></textarea>
                </div>
                <button class="btn btn-primary btn-lg btn-block" onclick="customerMenu.submitOrder()">
                    <i class="fas fa-paper-plane"></i> ส่งรายการสั่งซื้อ
                </button>
            </div>
        </div>
    </div>
    
    <script src="assets/js/script.js?v=6"></script>
</body>
</html>