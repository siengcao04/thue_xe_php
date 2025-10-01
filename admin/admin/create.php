<?php
session_start();
include("../../include/common.php");

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['admin_id'])) {
    redirect_to("admin/login.php");
}

// Chỉ admin mới được quản lý tài khoản admin khác
if (($_SESSION['admin_vai_tro'] ?? '') !== 'admin') {
    js_alert('Bạn không có quyền truy cập chức năng này!');
    js_redirect_to('admin/index.php');
}

$error = '';
$success = '';

// Xử lý form submit
if (is_post_method()) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $ho_ten = trim($_POST['ho_ten'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $sdt = trim($_POST['sdt'] ?? '');
    $vai_tro = trim($_POST['vai_tro'] ?? 'nhan_vien');
    $trang_thai = (int)($_POST['trang_thai'] ?? 1);

    // Validate
    if (empty($username)) {
        $error = 'Vui lòng nhập tên đăng nhập!';
    } elseif (empty($password)) {
        $error = 'Vui lòng nhập mật khẩu!';
    } elseif (empty($ho_ten)) {
        $error = 'Vui lòng nhập họ tên!';
    } else {
        // Kiểm tra trùng username
        $sql = "SELECT id FROM admin WHERE username = ?";
        $existing = db_select($sql, [$username]);
        
        if (!empty($existing)) {
            $error = 'Tên đăng nhập đã tồn tại!';
        } else {
            // Kiểm tra trùng email (nếu có)
            if (!empty($email)) {
                $sql = "SELECT id FROM admin WHERE email = ?";
                $existing = db_select($sql, [$email]);
                
                if (!empty($existing)) {
                    $error = 'Email đã tồn tại!';
                }
            }
            
            if (empty($error)) {
                // Mã hóa mật khẩu
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Upload avatar
                $avatar = upload_and_return_filename('avatar', 'admin');
                
                // Thêm vào database
                $sql = "INSERT INTO admin (username, password, ho_ten, email, sdt, vai_tro, avatar, trang_thai) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $result = db_execute($sql, [
                    $username, $hashed_password, $ho_ten, 
                    $email ?: null, $sdt ?: null, $vai_tro, 
                    $avatar, $trang_thai
                ]);
                
                if ($result) {
                    js_alert('Thêm admin thành công!');
                    js_redirect_to('admin/admin/index.php');
                } else {
                    $error = 'Có lỗi xảy ra khi thêm admin!';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm Admin - XeDeep</title>
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
            <li><a href="../loai_xe/index.php">Loại xe</a></li>
            <li><a href="../hang_xe/index.php">Hãng xe</a></li>
            <li><a href="../xe/index.php">Quản lý xe</a></li>
            <li><a href="../khach_hang/index.php">Khách hàng</a></li>
            <li><a href="../don_thue/index.php">Đơn thuê</a></li>
            <li><a href="index.php" class="active">Quản trị viên</a></li>
        </ul>
    </nav>

    <!-- Container -->
    <div class="admin-container">
        <div class="admin-card">
            <div class="admin-card-header">
                <h2>➕ Thêm Admin mới</h2>
            </div>
            <div class="admin-card-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" class="admin-form">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                        <!-- Cột trái -->
                        <div>
                            <div class="form-group">
                                <label for="username">Tên đăng nhập <span style="color: red;">*</span></label>
                                <input type="text" 
                                       id="username" 
                                       name="username" 
                                       class="form-control" 
                                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                                       required>
                            </div>

                            <div class="form-group">
                                <label for="password">Mật khẩu <span style="color: red;">*</span></label>
                                <input type="password" 
                                       id="password" 
                                       name="password" 
                                       class="form-control" 
                                       required>
                            </div>

                            <div class="form-group">
                                <label for="ho_ten">Họ tên <span style="color: red;">*</span></label>
                                <input type="text" 
                                       id="ho_ten" 
                                       name="ho_ten" 
                                       class="form-control" 
                                       value="<?= htmlspecialchars($_POST['ho_ten'] ?? '') ?>"
                                       required>
                            </div>

                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" 
                                       id="email" 
                                       name="email" 
                                       class="form-control" 
                                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                            </div>
                        </div>

                        <!-- Cột phải -->
                        <div>
                            <div class="form-group">
                                <label for="sdt">Số điện thoại</label>
                                <input type="tel" 
                                       id="sdt" 
                                       name="sdt" 
                                       class="form-control" 
                                       value="<?= htmlspecialchars($_POST['sdt'] ?? '') ?>">
                            </div>

                            <div class="form-group">
                                <label for="vai_tro">Vai trò</label>
                                <select id="vai_tro" name="vai_tro" class="form-control">
                                    <option value="nhan_vien" <?= ($_POST['vai_tro'] ?? 'nhan_vien') == 'nhan_vien' ? 'selected' : '' ?>>Nhân viên</option>
                                    <option value="admin" <?= ($_POST['vai_tro'] ?? 'nhan_vien') == 'admin' ? 'selected' : '' ?>>Admin</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="trang_thai">Trạng thái</label>
                                <select id="trang_thai" name="trang_thai" class="form-control">
                                    <option value="1" <?= ($_POST['trang_thai'] ?? 1) == 1 ? 'selected' : '' ?>>Hoạt động</option>
                                    <option value="0" <?= ($_POST['trang_thai'] ?? 1) == 0 ? 'selected' : '' ?>>Bị khóa</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="avatar">Avatar</label>
                                <input type="file" 
                                       id="avatar" 
                                       name="avatar" 
                                       class="form-control" 
                                       accept="image/*">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-success">💾 Lưu admin</button>
                        <a href="index.php" class="btn btn-info">📋 Quay lại danh sách</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>