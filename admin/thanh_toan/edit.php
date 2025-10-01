<?php
session_start();
include("../../include/common.php");

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['admin_id'])) {
    redirect_to("admin/login.php");
}

$error = '';
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    js_alert('ID không hợp lệ!');
    js_redirect_to('admin/thanh_toan/index.php');
}

// Lấy thông tin thanh toán
$sql = "SELECT 
            tt.*,
            dt.ma_don_thue,
            dt.tong_tien as tong_tien_don,
            kh.ho_ten as ten_khach_hang
        FROM thanh_toan tt
        JOIN don_thue dt ON tt.don_thue_id = dt.id
        JOIN khach_hang kh ON dt.khach_hang_id = kh.id
        WHERE tt.id = ?";
$thanh_toan = db_select($sql, [$id]);

if (empty($thanh_toan)) {
    js_alert('Không tìm thấy giao dịch thanh toán!');
    js_redirect_to('admin/thanh_toan/index.php');
}

$tt = $thanh_toan[0];

// Tính tổng đã thanh toán (trừ giao dịch hiện tại)
$sql = "SELECT COALESCE(SUM(so_tien), 0) as da_thanh_toan
        FROM thanh_toan 
        WHERE don_thue_id = ? AND trang_thai = 'thanh_cong' AND id != ?";
$result = db_select($sql, [$tt['don_thue_id'], $id]);
$da_thanh_toan_khac = $result[0]['da_thanh_toan'] ?? 0;

// Xử lý form submit
if (is_post_method()) {
    $so_tien = (float)($_POST['so_tien'] ?? 0);
    $phuong_thuc = trim($_POST['phuong_thuc'] ?? 'tien_mat');
    $trang_thai = trim($_POST['trang_thai'] ?? 'thanh_cong');
    $ghi_chu = trim($_POST['ghi_chu'] ?? '');

    // Validate
    if ($so_tien <= 0) {
        $error = 'Vui lòng nhập số tiền hợp lệ!';
    } else {
        // Kiểm tra tổng thanh toán không vượt quá tổng tiền đơn
        $tong_sau_sua = $da_thanh_toan_khac + ($trang_thai == 'thanh_cong' ? $so_tien : 0);
        
        if ($tong_sau_sua > $tt['tong_tien_don']) {
            $error = "Tổng thanh toán không được vượt quá tổng tiền đơn: " . number_format($tt['tong_tien_don']) . " VNĐ";
        } else {
            // Cập nhật thanh toán
            $sql = "UPDATE thanh_toan 
                    SET so_tien = ?, phuong_thuc = ?, trang_thai = ?, ghi_chu = ?
                    WHERE id = ?";
            $result = db_execute($sql, [$so_tien, $phuong_thuc, $trang_thai, $ghi_chu ?: null, $id]);
            
            if ($result) {
                js_alert('Cập nhật giao dịch thanh toán thành công!');
                js_redirect_to('admin/thanh_toan/index.php');
            } else {
                $error = 'Có lỗi xảy ra khi cập nhật giao dịch!';
            }
        }
    }
} else {
    // Load dữ liệu hiện tại vào form
    $_POST = [
        'so_tien' => $tt['so_tien'],
        'phuong_thuc' => $tt['phuong_thuc'],
        'trang_thai' => $tt['trang_thai'],
        'ghi_chu' => $tt['ghi_chu']
    ];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa thanh toán - XeDeep</title>
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
            <li><a href="../admin/index.php">Quản trị viên</a></li>
            <li><a href="../bao_cao/index.php">📊 Báo cáo</a></li>
            <li><a href="../danh_gia/index.php">⭐ Đánh giá</a></li>
            <li><a href="index.php" class="active">💳 Thanh toán</a></li>
        </ul>
    </nav>

    <!-- Container -->
    <div class="admin-container">
        <div class="admin-card">
            <div class="admin-card-header">
                <h2>✏️ Sửa giao dịch thanh toán</h2>
            </div>
            <div class="admin-card-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <!-- Thông tin đơn thuê -->
                <div style="background: #e3f2fd; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
                    <h4 style="margin-top: 0; color: #1976d2;">📋 Thông tin đơn thuê</h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        <div>
                            <strong>Mã đơn thuê:</strong><br>
                            <span style="color: #1976d2;"><?= htmlspecialchars($tt['ma_don_thue']) ?></span>
                        </div>
                        <div>
                            <strong>Khách hàng:</strong><br>
                            <?= htmlspecialchars($tt['ten_khach_hang']) ?>
                        </div>
                        <div>
                            <strong>Tổng tiền đơn:</strong><br>
                            <span style="color: #d32f2f; font-weight: bold;"><?= number_format($tt['tong_tien_don']) ?> VNĐ</span>
                        </div>
                        <div>
                            <strong>Đã thanh toán khác:</strong><br>
                            <span style="color: #388e3c;"><?= number_format($da_thanh_toan_khac) ?> VNĐ</span>
                        </div>
                    </div>
                </div>

                <form method="POST" class="admin-form">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                        <!-- Cột trái -->
                        <div>
                            <div class="form-group">
                                <label for="so_tien">Số tiền thanh toán <span style="color: red;">*</span></label>
                                <input type="number" 
                                       id="so_tien" 
                                       name="so_tien" 
                                       class="form-control" 
                                       value="<?= htmlspecialchars($_POST['so_tien'] ?? '') ?>"
                                       min="1000"
                                       step="1000"
                                       required>
                                <small style="color: #6c757d;">Số tiền tối thiểu: 1,000 VNĐ</small>
                            </div>

                            <div class="form-group">
                                <label for="phuong_thuc">Phương thức thanh toán</label>
                                <select id="phuong_thuc" name="phuong_thuc" class="form-control">
                                    <option value="tien_mat" <?= ($_POST['phuong_thuc'] ?? 'tien_mat') == 'tien_mat' ? 'selected' : '' ?>>Tiền mặt</option>
                                    <option value="chuyen_khoan" <?= ($_POST['phuong_thuc'] ?? 'tien_mat') == 'chuyen_khoan' ? 'selected' : '' ?>>Chuyển khoản</option>
                                    <option value="the_tin_dung" <?= ($_POST['phuong_thuc'] ?? 'tien_mat') == 'the_tin_dung' ? 'selected' : '' ?>>Thẻ tín dụng</option>
                                    <option value="vi_dien_tu" <?= ($_POST['phuong_thuc'] ?? 'tien_mat') == 'vi_dien_tu' ? 'selected' : '' ?>>Ví điện tử</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="trang_thai">Trạng thái</label>
                                <select id="trang_thai" name="trang_thai" class="form-control">
                                    <option value="thanh_cong" <?= ($_POST['trang_thai'] ?? 'thanh_cong') == 'thanh_cong' ? 'selected' : '' ?>>Thành công</option>
                                    <option value="dang_xu_ly" <?= ($_POST['trang_thai'] ?? 'thanh_cong') == 'dang_xu_ly' ? 'selected' : '' ?>>Đang xử lý</option>
                                    <option value="that_bai" <?= ($_POST['trang_thai'] ?? 'thanh_cong') == 'that_bai' ? 'selected' : '' ?>>Thất bại</option>
                                </select>
                            </div>
                        </div>

                        <!-- Cột phải -->
                        <div>
                            <div class="form-group">
                                <label for="ghi_chu">Ghi chú</label>
                                <textarea id="ghi_chu" 
                                          name="ghi_chu" 
                                          class="form-control" 
                                          rows="8"
                                          placeholder="Ghi chú về giao dịch (tùy chọn)"><?= htmlspecialchars($_POST['ghi_chu'] ?? '') ?></textarea>
                            </div>

                            <!-- Thông tin giao dịch cũ -->
                            <div style="background: #f8f9fa; padding: 1rem; border-radius: 6px;">
                                <strong>Thông tin cũ:</strong><br>
                                Ngày thanh toán: <?= date('d/m/Y H:i', strtotime($tt['ngay_thanh_toan'])) ?><br>
                                Số tiền cũ: <?= number_format($tt['so_tien']) ?> VNĐ<br>
                                Phương thức cũ: <?= ucfirst(str_replace('_', ' ', $tt['phuong_thuc'])) ?><br>
                                Trạng thái cũ: <?= ucfirst(str_replace('_', ' ', $tt['trang_thai'])) ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-success">💾 Cập nhật giao dịch</button>
                        <a href="index.php" class="btn btn-info">📋 Quay lại danh sách</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>