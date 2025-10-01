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
    js_redirect_to('admin/khach_hang/index.php');
}

// Lấy thông tin khách hàng
$sql = "SELECT * FROM khach_hang WHERE id = ?";
$khach_hang = db_select($sql, [$id]);

if (empty($khach_hang)) {
    js_alert('Không tìm thấy khách hàng!');
    js_redirect_to('admin/khach_hang/index.php');
}

$khach_hang = $khach_hang[0];

// Xử lý form submit
if (is_post_method()) {
    $username = trim($_POST['username'] ?? '');
    $ho_ten = trim($_POST['ho_ten'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $sdt = trim($_POST['sdt'] ?? '');
    $dia_chi = trim($_POST['dia_chi'] ?? '');
    $ngay_sinh = trim($_POST['ngay_sinh'] ?? '');
    $gioi_tinh = trim($_POST['gioi_tinh'] ?? '');
    $so_cmnd = trim($_POST['so_cmnd'] ?? '');
    $bang_lai = trim($_POST['bang_lai'] ?? '');
    $trang_thai = (int)($_POST['trang_thai'] ?? 1);

    // Validate
    if (empty($username)) {
        $error = 'Vui lòng nhập tên đăng nhập!';
    } elseif (empty($ho_ten)) {
        $error = 'Vui lòng nhập họ tên!';
    } elseif (empty($sdt)) {
        $error = 'Vui lòng nhập số điện thoại!';
    } else {
        // Kiểm tra trùng username (trừ bản ghi hiện tại)
        $sql = "SELECT id FROM khach_hang WHERE username = ? AND id != ?";
        $existing = db_select($sql, [$username, $id]);
        
        if (!empty($existing)) {
            $error = 'Tên đăng nhập đã tồn tại!';
        } else {
            // Kiểm tra trùng email (nếu có)
            if (!empty($email)) {
                $sql = "SELECT id FROM khach_hang WHERE email = ? AND id != ?";
                $existing = db_select($sql, [$email, $id]);
                
                if (!empty($existing)) {
                    $error = 'Email đã tồn tại!';
                }
            }
            
            // Kiểm tra trùng CMND (nếu có)
            if (empty($error) && !empty($so_cmnd)) {
                $sql = "SELECT id FROM khach_hang WHERE so_cmnd = ? AND id != ?";
                $existing = db_select($sql, [$so_cmnd, $id]);
                
                if (!empty($existing)) {
                    $error = 'Số CMND đã tồn tại!';
                }
            }
            
            if (empty($error)) {
                // Upload avatar mới (nếu có)
                $avatar_moi = upload_and_return_filename('avatar', 'khach_hang');
                $avatar = $avatar_moi ?: $khach_hang['avatar']; // Giữ avatar cũ nếu không upload mới
                
                // Xử lý mật khẩu
                $password_update = '';
                $params = [$username, $ho_ten, $email ?: null, $sdt, $dia_chi ?: null, 
                          $ngay_sinh ?: null, $gioi_tinh ?: null, $so_cmnd ?: null, 
                          $bang_lai ?: null, $avatar, $trang_thai];
                
                // Nếu có mật khẩu mới thì cập nhật
                if (!empty($_POST['password'])) {
                    $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $password_update = ', password = ?';
                    array_splice($params, 1, 0, [$hashed_password]); // Chèn vào vị trí thứ 2
                }
                
                // Cập nhật database
                $sql = "UPDATE khach_hang SET username = ?" . $password_update . ", ho_ten = ?, email = ?, sdt = ?, dia_chi = ?, ngay_sinh = ?, gioi_tinh = ?, so_cmnd = ?, bang_lai = ?, avatar = ?, trang_thai = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
                $params[] = $id;
                
                $result = db_execute($sql, $params);
                
                if ($result) {
                    // Xóa avatar cũ nếu upload avatar mới
                    if ($avatar_moi && $khach_hang['avatar'] && $khach_hang['avatar'] != $avatar_moi) {
                        remove_file($khach_hang['avatar']);
                    }
                    
                    js_alert('Cập nhật khách hàng thành công!');
                    js_redirect_to('admin/khach_hang/index.php');
                } else {
                    $error = 'Có lỗi xảy ra khi cập nhật khách hàng!';
                }
            }
        }
    }
} else {
    // Load dữ liệu hiện tại vào form
    $_POST = [
        'username' => $khach_hang['username'],
        'ho_ten' => $khach_hang['ho_ten'],
        'email' => $khach_hang['email'],
        'sdt' => $khach_hang['sdt'],
        'dia_chi' => $khach_hang['dia_chi'],
        'ngay_sinh' => $khach_hang['ngay_sinh'],
        'gioi_tinh' => $khach_hang['gioi_tinh'],
        'so_cmnd' => $khach_hang['so_cmnd'],
        'bang_lai' => $khach_hang['bang_lai'],
        'trang_thai' => $khach_hang['trang_thai']
    ];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa khách hàng - XeDeep</title>
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
            <li><a href="index.php" class="active">Khách hàng</a></li>
            <li><a href="../don_thue/index.php">Đơn thuê</a></li>
            <li><a href="../admin/index.php">Quản trị viên</a></li>
        </ul>
    </nav>

    <!-- Container -->
    <div class="admin-container">
        <div class="admin-card">
            <div class="admin-card-header">
                <h2>✏️ Sửa khách hàng: <?= htmlspecialchars($khach_hang['ho_ten']) ?></h2>
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

                            <div class="form-group">
                                <label for="sdt">Số điện thoại <span style="color: red;">*</span></label>
                                <input type="tel" 
                                       id="sdt" 
                                       name="sdt" 
                                       class="form-control" 
                                       value="<?= htmlspecialchars($_POST['sdt'] ?? '') ?>"
                                       required>
                            </div>

                            <div class="form-group">
                                <label for="dia_chi">Địa chỉ</label>
                                <textarea id="dia_chi" 
                                          name="dia_chi" 
                                          class="form-control" 
                                          rows="3"><?= htmlspecialchars($_POST['dia_chi'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <!-- Cột phải -->
                        <div>
                            <div class="form-group">
                                <label for="ngay_sinh">Ngày sinh</label>
                                <input type="date" 
                                       id="ngay_sinh" 
                                       name="ngay_sinh" 
                                       class="form-control" 
                                       value="<?= $_POST['ngay_sinh'] ?? '' ?>">
                            </div>

                            <div class="form-group">
                                <label for="gioi_tinh">Giới tính</label>
                                <select id="gioi_tinh" name="gioi_tinh" class="form-control">
                                    <option value="">-- Chọn giới tính --</option>
                                    <option value="nam" <?= ($_POST['gioi_tinh'] ?? '') == 'nam' ? 'selected' : '' ?>>Nam</option>
                                    <option value="nu" <?= ($_POST['gioi_tinh'] ?? '') == 'nu' ? 'selected' : '' ?>>Nữ</option>
                                    <option value="khac" <?= ($_POST['gioi_tinh'] ?? '') == 'khac' ? 'selected' : '' ?>>Khác</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="so_cmnd">Số CMND/CCCD</label>
                                <input type="text" 
                                       id="so_cmnd" 
                                       name="so_cmnd" 
                                       class="form-control" 
                                       value="<?= htmlspecialchars($_POST['so_cmnd'] ?? '') ?>">
                            </div>

                            <div class="form-group">
                                <label for="bang_lai">Bằng lái xe</label>
                                <input type="text" 
                                       id="bang_lai" 
                                       name="bang_lai" 
                                       class="form-control" 
                                       value="<?= htmlspecialchars($_POST['bang_lai'] ?? '') ?>"
                                       placeholder="A1, A2, B1, B2...">
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
                                <?php if ($khach_hang['avatar']): ?>
                                    <div style="margin-bottom: 10px;">
                                        <img src="<?= upload($khach_hang['avatar']) ?>" 
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
                        <button type="submit" class="btn btn-success">💾 Cập nhật khách hàng</button>
                        <a href="index.php" class="btn btn-info">📋 Quay lại danh sách</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>