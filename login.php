<?php
session_start();
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = escape($_POST['username']);
    $password = $_POST['password'];
    
    $result = query("SELECT * FROM users WHERE username = '$username'");
    $user = fetchOne($result);
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        header('Location: admin/dashboard.php');
        exit;
    } else {
        $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - ระบบ POS ร้านอาหาร</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="login-page">
        <div class="login-box">
            <div class="logo">
                <i class="fas fa-utensils"></i>
                <h1>ระบบ POS ร้านอาหาร</h1>
                <p style="color: var(--gray); font-size: 14px; margin-top: 5px;">เข้าสู่ระบบเพื่อจัดการร้าน</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div style="background: #FFEBEE; color: #C62828; padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-size: 14px;">
                    <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> ชื่อผู้ใช้</label>
                    <input type="text" name="username" required placeholder="กรุณากรอกชื่อผู้ใช้">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> รหัสผ่าน</label>
                    <input type="password" name="password" required placeholder="กรุณากรอกรหัสผ่าน">
                </div>
                <button type="submit" class="btn btn-primary btn-lg btn-block">
                    <i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ
                </button>
            </form>
            
            <div style="text-align: center; margin-top: 20px; font-size: 13px; color: var(--gray);">
                <p>สำหรับลูกค้า: <a href="menu.php" style="color: var(--primary);">ดูเมนูอาหาร</a></p>
            </div>
        </div>
    </div>
</body>
</html>