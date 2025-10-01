<?php
session_start();
include("../../include/common.php");

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['admin_id'])) {
    redirect_to("admin/login.php");
}

$error = '';
$success = '';
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    js_alert('ID không hợp lệ!');
    js_redirect_to('admin/loai_xe/index.php');
}

// Lấy thông tin loại xe
$sql = "SELECT * FROM loai_xe WHERE id = ?";
$loai_xe = db_select($sql, [$id]);

if (empty($loai_xe)) {
    js_alert('Không tìm thấy loại xe!');
    js_redirect_to('admin/loai_xe/index.php');
}

$loai_xe = $loai_xe[0];

// Xử lý form submit
if (is_post_method()) {
    $ten_loai = trim($_POST['ten_loai'] ?? '');
    $mo_ta = trim($_POST['mo_ta'] ?? '');
    $trang_thai = (int)($_POST['trang_thai'] ?? 1);

    // Validate
    if (empty($ten_loai)) {
        $error = 'Vui lòng nhập tên loại xe!';
    } else {
        // Kiểm tra trùng tên (trừ bản ghi hiện tại)
        $sql = "SELECT id FROM loai_xe WHERE ten_loai = ? AND id != ?";
        $existing = db_select($sql, [$ten_loai, $id]);
        
        if (!empty($existing)) {
            $error = 'Tên loại xe đã tồn tại!';
        } else {
            // Cập nhật database
            $sql = "UPDATE loai_xe SET ten_loai = ?, mo_ta = ?, trang_thai = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $result = db_execute($sql, [$ten_loai, $mo_ta, $trang_thai, $id]);
            
            if ($result) {
                js_alert('Cập nhật loại xe thành công!');
                js_redirect_to('admin/loai_xe/index.php');
            } else {
                $error = 'Có lỗi xảy ra khi cập nhật loại xe!';
            }
        }
    }
} else {
    // Load dữ liệu hiện tại vào form
    $_POST['ten_loai'] = $loai_xe['ten_loai'];
    $_POST['mo_ta'] = $loai_xe['mo_ta'];
    $_POST['trang_thai'] = $loai_xe['trang_thai'];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa loại xe - XeDeep</title>
    <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
</head>
<body>
    <!-- Header -->
    <div class="admin-header clearfix">
        <h1>🚗 Hệ thống quản lý thuê xe XeDeep</h1>
        <div class="user-info">
            <span>Xin chào, <?= htmlspecialchars($_SESSION['admin_ho_ten'] ?? $_SESSION['admin_username'] ?? 'Admin') ?></span>
            <a href="../logout.php">Đăng xuất</a>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="admin-nav">
        <ul>
            <li><a href="../index.php">Dashboard</a></li>
            <li><a href="index.php" class="active">Loại xe</a></li>
            <li><a href="../hang_xe/index.php">Hãng xe</a></li>
            <li><a href="../xe/index.php">Quản lý xe</a></li>
            <li><a href="../khach_hang/index.php">Khách hàng</a></li>
            <li><a href="../don_thue/index.php">Đơn thuê</a></li>
            <li><a href="../admin/index.php">Quản trị viên</a></li>
        </ul>
    </nav>

    <!-- Container -->
    <div class="admin-container">
        <div class="admin-card">
            <div class="admin-card-header">
                <h2>✏️ Sửa loại xe: <?= htmlspecialchars($loai_xe['ten_loai']) ?></h2>
            </div>
            <div class="admin-card-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <form method="POST" class="admin-form">
                    <div class="form-group">
                        <label for="ten_loai">Tên loại xe <span style="color: red;">*</span></label>
                        <input type="text" 
                               id="ten_loai" 
                               name="ten_loai" 
                               class="form-control" 
                               value="<?= htmlspecialchars($_POST['ten_loai'] ?? '') ?>"
                               placeholder="Ví dụ: Xe máy, Ô tô, Xe đạp..."
                               required>
                    </div>

                    <div class="form-group">
                        <label for="mo_ta">Mô tả</label>
                        <textarea id="mo_ta" 
                                  name="mo_ta" 
                                  class="form-control" 
                                  rows="4" 
                                  placeholder="Mô tả chi tiết về loại xe này..."><?= htmlspecialchars($_POST['mo_ta'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="trang_thai">Trạng thái</label>
                        <select id="trang_thai" name="trang_thai" class="form-control">
                            <option value="1" <?= ($_POST['trang_thai'] ?? 1) == 1 ? 'selected' : '' ?>>Hoạt động</option>
                            <option value="0" <?= ($_POST['trang_thai'] ?? 1) == 0 ? 'selected' : '' ?>>Không hoạt động</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-success">💾 Cập nhật loại xe</button>
                        <a href="index.php" class="btn btn-info">📋 Quay lại danh sách</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>