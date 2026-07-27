<?php
session_start();

// ตรวจสอบสิทธิ์ admin
if (!isset($_SESSION['login_id']) || $_SESSION['login_type'] != 1) {
    header("Location: ../index.php");
    exit;
}

include('../admin/db_connect.php');

// ดึงรายชื่อผู้ใช้ทั้งหมด
$users = $conn->query("SELECT * FROM users ORDER BY date_created DESC");
?>

<style>
    .user-card {
        transition: all 0.2s;
        border: 1px solid #e0e0e0;
    }
    .user-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }
    .user-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #007bff;
    }
    .status-badge {
        font-size: 0.85rem;
        padding: 5px 12px;
        border-radius: 50px;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fa fa-users mr-2"></i>จัดการผู้ใช้งานทั้งหมด</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>รูปโปรไฟล์</th>
                                    <th>ชื่อ-นามสกุล</th>
                                    <th>ชื่อผู้ใช้</th>
                                    <th>อีเมล</th>
                                    <th>เบอร์โทร</th>
                                    <th>ประเภทผู้ใช้</th>
                                    <th>สถานะ</th>
                                    <th>วันที่สมัคร</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $cnt = 1;
                                while($row = $users->fetch_assoc()): 
                                    $user_type = ($row['type'] == 1) ? 'Admin' : (($row['type'] == 2) ? 'Staff' : 'Subscriber');
                                    $type_badge = ($row['type'] == 1) ? 'danger' : (($row['type'] == 2) ? 'warning' : 'info');
                                    
                                    $status_badge = '';
                                    if($row['is_verified'] == 1) {
                                        $status_badge = '<span class="badge badge-success status-badge"><i class="fa fa-check"></i> ยืนยันแล้ว</span>';
                                    } else {
                                        $status_badge = '<span class="badge badge-secondary status-badge"><i class="fa fa-clock"></i> รอยืนยัน</span>';
                                    }
                                ?>
                                <tr>
                                    <td><?= $cnt++ ?></td>
                                    <td>
                                        <img src="../assets/uploads/<?php echo !empty($row['avatar']) ? $row['avatar'] : 'no-image-available.png'; ?>" 
                                             alt="Avatar" 
                                             class="user-avatar"
                                             onerror="this.src='../assets/uploads/no-image-available.png'">
                                    </td>
                                    <td><?= htmlspecialchars($row['name']) ?></td>
                                    <td><?= htmlspecialchars($row['username']) ?></td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td><?= htmlspecialchars($row['contact'] ?? 'ไม่ได้ระบุ') ?></td>
                                    <td>
                                        <span class="badge badge-<?= $type_badge ?> status-badge">
                                            <?= $user_type ?>
                                        </span>
                                    </td>
                                    <td><?= $status_badge ?></td>
                                    <td><?= date("d/m/Y", strtotime($row['date_created'])) ?></td>
                                    <td>
                                        <a href="../profile.php?admin_edit_id=<?= $row['id'] ?>" 
                                           class="btn btn-primary btn-sm"
                                           title="ดูและแก้ไขข้อมูล">
                                            <i class="fa fa-edit"></i> แก้ไข
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// เมื่อกดปุ่มแก้ไข ให้เปิดหน้า profile.php พร้อมพารามิเตอร์ admin_edit_id
$(document).ready(function() {
    $('.btn-edit-user').click(function(e) {
        e.preventDefault();
        var userId = $(this).data('id');
        window.location.href = '../profile.php?admin_edit_id=' + userId;
    });
});
</script>