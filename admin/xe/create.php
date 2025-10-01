<?php
session_start();
include("../../include/common.php");

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['admin_id'])) {
    redirect_to("admin/login.php");
}

$error = '';
$success = '';

// Lấy danh sách loại xe và hãng xe cho dropdown
$sql = "SELECT * FROM loai_xe WHERE trang_thai = 1 ORDER BY ten_loai";
$loai_xe_list = db_select($sql);

$sql = "SELECT * FROM hang_xe WHERE trang_thai = 1 ORDER BY ten_hang";
$hang_xe_list = db_select($sql);

// Xử lý form submit
if (is_post_method()) {
    $ma_xe = trim($_POST['ma_xe'] ?? '');
    $ten_xe = trim($_POST['ten_xe'] ?? '');
    $loai_xe_id = (int)($_POST['loai_xe_id'] ?? 0);
    $hang_xe_id = (int)($_POST['hang_xe_id'] ?? 0);
    $bien_so = trim($_POST['bien_so'] ?? '');
    $so_cho_ngoi = (int)($_POST['so_cho_ngoi'] ?? 2);
    $gia_thue_ngay = (float)($_POST['gia_thue_ngay'] ?? 0);
    $gia_thue_gio = (float)($_POST['gia_thue_gio'] ?? 0);
    $mo_ta = trim($_POST['mo_ta'] ?? '');
    $trang_thai = $_POST['trang_thai'] ?? 'san_sang';

    // Validate
    if (empty($ma_xe)) {
        $error = 'Vui lòng nhập mã xe!';
    } elseif (empty($ten_xe)) {
        $error = 'Vui lòng nhập tên xe!';
    } elseif ($loai_xe_id <= 0) {
        $error = 'Vui lòng chọn loại xe!';
    } elseif ($hang_xe_id <= 0) {
        $error = 'Vui lòng chọn hãng xe!';
    } elseif ($gia_thue_ngay <= 0) {
        $error = 'Vui lòng nhập giá thuê ngày!';
    } else {
        // Kiểm tra trùng mã xe
        $sql = "SELECT id FROM xe WHERE ma_xe = ?";
        $existing = db_select($sql, [$ma_xe]);
        
        if (!empty($existing)) {
            $error = 'Mã xe đã tồn tại!';
        } else {
            // Kiểm tra trùng biển số (nếu có)
            if (!empty($bien_so)) {
                $sql = "SELECT id FROM xe WHERE bien_so = ?";
                $existing = db_select($sql, [$bien_so]);
                
                if (!empty($existing)) {
                    $error = 'Biển số đã tồn tại!';
                }
            }
            
            if (empty($error)) {
                // Upload ảnh
                $hinh_anh = upload_and_return_filename('hinh_anh', 'xe');
                
                // Thêm vào database
                $sql = "INSERT INTO xe (ma_xe, ten_xe, loai_xe_id, hang_xe_id, bien_so, so_cho_ngoi, gia_thue_ngay, gia_thue_gio, mo_ta, hinh_anh, trang_thai) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $result = db_execute($sql, [
                    $ma_xe, $ten_xe, $loai_xe_id, $hang_xe_id, 
                    $bien_so ?: null, $so_cho_ngoi, $gia_thue_ngay, 
                    $gia_thue_gio ?: null, $mo_ta, $hinh_anh, $trang_thai
                ]);
                
                if ($result) {
                    js_alert('Thêm xe thành công!');
                    js_redirect_to('admin/xe/index.php');
                } else {
                    $error = 'Có lỗi xảy ra khi thêm xe!';
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
    <title>Thêm xe - XeDeep</title>
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
            <li><a href="index.php" class="active">Quản lý xe</a></li>
            <li><a href="../khach_hang/index.php">Khách hàng</a></li>
            <li><a href="../don_thue/index.php">Đơn thuê</a></li>
            <li><a href="../admin/index.php">Quản trị viên</a></li>
        </ul>
    </nav>

    <!-- Container -->
    <div class="admin-container">
        <div class="admin-card">
            <div class="admin-card-header">
                <h2>➕ Thêm xe mới</h2>
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
                                <label for="ma_xe">Mã xe <span style="color: red;">*</span></label>
                                <input type="text" 
                                       id="ma_xe" 
                                       name="ma_xe" 
                                       class="form-control" 
                                       value="<?= htmlspecialchars($_POST['ma_xe'] ?? '') ?>"
                                       placeholder="Ví dụ: XM001, OT001..."
                                       required>
                            </div>

                            <div class="form-group">
                                <label for="ten_xe">Tên xe <span style="color: red;">*</span></label>
                                <input type="text" 
                                       id="ten_xe" 
                                       name="ten_xe" 
                                       class="form-control" 
                                       value="<?= htmlspecialchars($_POST['ten_xe'] ?? '') ?>"
                                       placeholder="Ví dụ: Honda Air Blade 125..."
                                       required>
                            </div>

                            <div class="form-group">
                                <label for="loai_xe_id">Loại xe <span style="color: red;">*</span></label>
                                <select id="loai_xe_id" name="loai_xe_id" class="form-control" required>
                                    <option value="">-- Chọn loại xe --</option>
                                    <?php foreach ($loai_xe_list as $loai): ?>
                                        <option value="<?= $loai['id'] ?>" 
                                                <?= ($_POST['loai_xe_id'] ?? 0) == $loai['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($loai['ten_loai']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="hang_xe_id">Hãng xe <span style="color: red;">*</span></label>
                                <select id="hang_xe_id" name="hang_xe_id" class="form-control" required>
                                    <option value="">-- Chọn hãng xe --</option>
                                    <?php foreach ($hang_xe_list as $hang): ?>
                                        <option value="<?= $hang['id'] ?>" 
                                                <?= ($_POST['hang_xe_id'] ?? 0) == $hang['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($hang['ten_hang']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="bien_so">Biển số xe</label>
                                <input type="text" 
                                       id="bien_so" 
                                       name="bien_so" 
                                       class="form-control" 
                                       value="<?= htmlspecialchars($_POST['bien_so'] ?? '') ?>"
                                       placeholder="Ví dụ: 29B1-12345">
                            </div>
                        </div>

                        <!-- Cột phải -->
                        <div>
                            <div class="form-group">
                                <label for="so_cho_ngoi">Số chỗ ngồi</label>
                                <input type="number" 
                                       id="so_cho_ngoi" 
                                       name="so_cho_ngoi" 
                                       class="form-control" 
                                       value="<?= $_POST['so_cho_ngoi'] ?? 2 ?>"
                                       min="1" 
                                       max="50">
                            </div>

                            <div class="form-group">
                                <label for="gia_thue_ngay">Giá thuê/ngày (VNĐ) <span style="color: red;">*</span></label>
                                <input type="number" 
                                       id="gia_thue_ngay" 
                                       name="gia_thue_ngay" 
                                       class="form-control" 
                                       value="<?= $_POST['gia_thue_ngay'] ?? '' ?>"
                                       min="0" 
                                       step="1000"
                                       placeholder="150000"
                                       required>
                            </div>

                            <div class="form-group">
                                <label for="gia_thue_gio">Giá thuê/giờ (VNĐ)</label>
                                <input type="number" 
                                       id="gia_thue_gio" 
                                       name="gia_thue_gio" 
                                       class="form-control" 
                                       value="<?= $_POST['gia_thue_gio'] ?? '' ?>"
                                       min="0" 
                                       step="1000"
                                       placeholder="20000">
                            </div>

                            <div class="form-group">
                                <label for="trang_thai">Trạng thái</label>
                                <select id="trang_thai" name="trang_thai" class="form-control">
                                    <option value="san_sang" <?= ($_POST['trang_thai'] ?? 'san_sang') == 'san_sang' ? 'selected' : '' ?>>Sẵn sàng</option>
                                    <option value="bao_tri" <?= ($_POST['trang_thai'] ?? 'san_sang') == 'bao_tri' ? 'selected' : '' ?>>Bảo trì</option>
                                    <option value="khong_hoat_dong" <?= ($_POST['trang_thai'] ?? 'san_sang') == 'khong_hoat_dong' ? 'selected' : '' ?>>Không hoạt động</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="hinh_anh">Hình ảnh xe</label>
                                <input type="file" 
                                       id="hinh_anh" 
                                       name="hinh_anh" 
                                       class="form-control" 
                                       accept="image/*">
                                <small style="color: #6c757d;">Chọn file ảnh (JPG, PNG, GIF...)</small>
                            </div>
                        </div>
                    </div>

                    <!-- Mô tả -->
                    <div class="form-group">
                        <label for="mo_ta">Mô tả</label>
                        <textarea id="mo_ta" 
                                  name="mo_ta" 
                                  class="form-control" 
                                  rows="4" 
                                  placeholder="Mô tả chi tiết về xe..."><?= htmlspecialchars($_POST['mo_ta'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-success">💾 Lưu xe</button>
                        <a href="index.php" class="btn btn-info">📋 Quay lại danh sách</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>