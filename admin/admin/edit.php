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
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    js_alert('ID không hợp lệ!');
    js_redirect_to('admin/admin/index.php');
}

// Không được sửa chính mình
if ($id == $_SESSION['admin_id']) {
    js_alert('Không thể sửa thông tin của chính mình!');
    js_redirect_to('admin/admin/index.php');
}

// Lấy thông tin admin
$sql = "SELECT * FROM admin WHERE id = ?";
$admin = db_select($sql, [$id]);

if (empty($admin)) {
    js_alert('Không tìm thấy admin!');
    js_redirect_to('admin/admin/index.php');
}

$admin = $admin[0];

// Xử lý form submit
if (is_post_method()) {
    $username = trim($_POST['username'] ?? '');
    $ho_ten = trim($_POST['ho_ten'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $sdt = trim($_POST['sdt'] ?? '');
    $vai_tro = trim($_POST['vai_tro'] ?? 'nhan_vien');
    $trang_thai = (int)($_POST['trang_thai'] ?? 1);

    // Validate
    if (empty($username)) {
        $error = 'Vui lòng nhập tên đăng nhập!';
    } elseif (empty($ho_ten)) {
        $error = 'Vui lòng nhập họ tên!';
    } else {
        // Kiểm tra trùng username (trừ bản ghi hiện tại)
        $sql = "SELECT id FROM admin WHERE username = ? AND id != ?";
        $existing = db_select($sql, [$username, $id]);
        
        if (!empty($existing)) {
            $error = 'Tên đăng nhập đã tồn tại!';
        } else {
            // Kiểm tra trùng email (nếu có)
            if (!empty($email)) {
                $sql = "SELECT id FROM admin WHERE email = ? AND id != ?";
                $existing = db_select($sql, [$email, $id]);
                
                if (!empty($existing)) {
                    $error = 'Email đã tồn tại!';
                }
            }
            
            if (empty($error)) {
                // Upload avatar mới (nếu có)
                $avatar_moi = upload_and_return_filename('avatar', 'admin');
                $avatar = $avatar_moi ?: $admin['avatar']; // Giữ avatar cũ nếu không upload mới
                
                // Xử lý mật khẩu
                $password_update = '';
                $params = [$username, $ho_ten, $email ?: null, $sdt ?: null, $vai_tro, $avatar, $trang_thai];
                
                // Nếu có mật khẩu mới thì cập nhật
                if (!empty($_POST['password'])) {
                    $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $password_update = ', password = ?';
                    array_splice($params, 1, 0, [$hashed_password]); // Chèn vào vị trí thứ 2
                }
                
                // Cập nhật database
                $sql = "UPDATE admin SET username = ?" . $password_update . ", ho_ten = ?, email = ?, sdt = ?, vai_tro = ?, avatar = ?, trang_thai = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
                $params[] = $id;
                
                $result = db_execute($sql, $params);
                
                if ($result) {
                    // Xóa avatar cũ nếu upload avatar mới
                    if ($avatar_moi && $admin['avatar'] && $admin['avatar'] != $avatar_moi) {
                        remove_file($admin['avatar']);
                    }
                    
                    js_alert('Cập nhật admin thành công!');
                    js_redirect_to('admin/admin/index.php');
                } else {
                    $error = 'Có lỗi xảy ra khi cập nhật admin!';
                }
            }
        }
    }
} else {
    // Load dữ liệu hiện tại vào form
    $_POST = [
        'username' => $admin['username'],
        'ho_ten' => $admin['ho_ten'],
        'email' => $admin['email'],
        'sdt' => $admin['sdt'],
        'vai_tro' => $admin['vai_tro'],
        'trang_thai' => $admin['trang_thai']
    ];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa Admin - XeDeep</title>
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
                <h2>✏️ Sửa admin: <?= htmlspecialchars($admin['ho_ten']) ?></h2>
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
                                <label for="password">Mật khẩu mới</label>
                                <input type="password" 
                                       id="password" 
                                       name="password" 
                                       class="form-control"
                                       placeholder="Để trống nếu không đổi mật khẩu">
                                <small style="color: #6c757d;">Chỉ nhập nếu muốn thay đổi mật khẩu</small>
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
                                <?php if ($admin['avatar']): ?>
                                    <div style="margin-bottom: 10px;">
                                        <img src="<?= upload($admin['avatar']) ?>" 
                                             alt="Avatar hiện tại" 
                                             style="width: 100px; height: 100px; object-fit: cover; border-radius: 50%;">
                                        <br><small>Avatar hiện tại</small>
                                    </div>
                                <?php endif; ?>
                                <input type="file" 
                                       id="avatar" 
                                       name="avatar" 
                                       class="form-control" 
                                       accept="image/*">
                                <small style="color: #6c757d;">Chọn file ảnh mới nếu muốn thay đổi</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-success">💾 Cập nhật admin</button>
                        <a href="index.php" class="btn btn-info">📋 Quay lại danh sách</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>