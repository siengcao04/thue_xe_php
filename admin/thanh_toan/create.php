<?php
session_start();
include("../../include/common.php");

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['admin_id'])) {
    redirect_to("admin/login.php");
}

$error = '';
$success = '';

// Lấy danh sách đơn thuê chưa thanh toán hoạc thanh toán chưa đủ
$sql = "SELECT 
            dt.*,
            kh.ho_ten as ten_khach_hang,
            COALESCE(SUM(tt.so_tien), 0) as da_thanh_toan
        FROM don_thue dt
        JOIN khach_hang kh ON dt.khach_hang_id = kh.id
        LEFT JOIN thanh_toan tt ON dt.id = tt.don_thue_id AND tt.trang_thai = 'thanh_cong'
        WHERE dt.trang_thai IN ('da_xac_nhan', 'dang_thue', 'hoan_thanh')
        GROUP BY dt.id
        HAVING da_thanh_toan < dt.tong_tien
        ORDER BY dt.ngay_thue DESC";
$don_thue_list = db_select($sql);

// Xử lý form submit
if (is_post_method()) {
    $don_thue_id = (int)($_POST['don_thue_id'] ?? 0);
    $so_tien = (float)($_POST['so_tien'] ?? 0);
    $phuong_thuc = trim($_POST['phuong_thuc'] ?? 'tien_mat');
    $trang_thai = trim($_POST['trang_thai'] ?? 'thanh_cong');
    $ghi_chu = trim($_POST['ghi_chu'] ?? '');

    // Validate
    if ($don_thue_id <= 0) {
        $error = 'Vui lòng chọn đơn thuê!';
    } elseif ($so_tien <= 0) {
        $error = 'Vui lòng nhập số tiền hợp lệ!';
    } else {
        // Kiểm tra đơn thuê có tồn tại và chưa thanh toán đủ
        $sql = "SELECT 
                    dt.id,
                    dt.tong_tien,
                    COALESCE(SUM(tt.so_tien), 0) as da_thanh_toan
                FROM don_thue dt
                LEFT JOIN thanh_toan tt ON dt.id = tt.don_thue_id AND tt.trang_thai = 'thanh_cong'
                WHERE dt.id = ? AND dt.trang_thai IN ('da_xac_nhan', 'dang_thue', 'hoan_thanh')
                GROUP BY dt.id";
        $don_thue = db_select($sql, [$don_thue_id]);
        
        if (empty($don_thue)) {
            $error = 'Đơn thuê không tồn tại hoặc không hợp lệ!';
        } else {
            $don = $don_thue[0];
            $con_lai = $don['tong_tien'] - $don['da_thanh_toan'];
            
            if ($so_tien > $con_lai) {
                $error = "Số tiền thanh toán không được vượt quá số tiền còn lại: " . number_format($con_lai) . " VNĐ";
            } else {
                // Thêm giao dịch thanh toán
                $sql = "INSERT INTO thanh_toan (don_thue_id, so_tien, phuong_thuc, trang_thai, ghi_chu, ngay_thanh_toan) 
                        VALUES (?, ?, ?, ?, ?, NOW())";
                $result = db_execute($sql, [$don_thue_id, $so_tien, $phuong_thuc, $trang_thai, $ghi_chu ?: null]);
                
                if ($result) {
                    js_alert('Thêm giao dịch thanh toán thành công!');
                    js_redirect_to('admin/thanh_toan/index.php');
                } else {
                    $error = 'Có lỗi xảy ra khi thêm giao dịch!';
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
    <title>Thêm thanh toán - XeDeep</title>
    <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
    <script>
        function updatePaymentInfo() {
            const select = document.getElementById('don_thue_id');
            const infoDiv = document.getElementById('payment-info');
            const soTienInput = document.getElementById('so_tien');
            
            if (select.value) {
                const option = select.options[select.selectedIndex];
                const tongTien = parseFloat(option.dataset.tongTien) || 0;
                const daThanhToan = parseFloat(option.dataset.daThanhToan) || 0;
                const conLai = tongTien - daThanhToan;
                
                infoDiv.innerHTML = `
                    <div style="background: #e3f2fd; padding: 1rem; border-radius: 6px; margin-top: 1rem;">
                        <strong>Thông tin thanh toán:</strong><br>
                        Tổng tiền đơn: <span style="color: #1976d2;">${new Intl.NumberFormat('vi-VN').format(tongTien)} VNĐ</span><br>
                        Đã thanh toán: <span style="color: #388e3c;">${new Intl.NumberFormat('vi-VN').format(daThanhToan)} VNĐ</span><br>
                        Còn lại: <span style="color: #d32f2f; font-weight: bold;">${new Intl.NumberFormat('vi-VN').format(conLai)} VNĐ</span>
                    </div>
                `;
                
                // Tự động điền số tiền còn lại
                soTienInput.value = conLai;
                soTienInput.max = conLai;
            } else {
                infoDiv.innerHTML = '';
                soTienInput.value = '';
                soTienInput.removeAttribute('max');
            }
        }
        
        function formatNumber(input) {
            let value = input.value.replace(/[^\d]/g, '');
            input.value = value;
        }
    </script>
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
                <h2>➕ Thêm giao dịch thanh toán</h2>
            </div>
            <div class="admin-card-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <?php if (empty($don_thue_list)): ?>
                    <div class="alert alert-info">
                        Không có đơn thuê nào cần thanh toán. Tất cả các đơn đã được thanh toán đầy đủ.
                    </div>
                    <div class="form-group">
                        <a href="index.php" class="btn btn-info">📋 Quay lại danh sách</a>
                    </div>
                <?php else: ?>
                    <form method="POST" class="admin-form">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                            <!-- Cột trái -->
                            <div>
                                <div class="form-group">
                                    <label for="don_thue_id">Đơn thuê <span style="color: red;">*</span></label>
                                    <select id="don_thue_id" 
                                            name="don_thue_id" 
                                            class="form-control" 
                                            required
                                            onchange="updatePaymentInfo()">
                                        <option value="">Chọn đơn thuê cần thanh toán</option>
                                        <?php foreach ($don_thue_list as $don): ?>
                                            <option value="<?= $don['id'] ?>"
                                                    data-tong-tien="<?= $don['tong_tien'] ?>"
                                                    data-da-thanh-toan="<?= $don['da_thanh_toan'] ?>"
                                                    <?= ($_POST['don_thue_id'] ?? '') == $don['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($don['ma_don_thue']) ?> - <?= htmlspecialchars($don['ten_khach_hang']) ?>
                                                (Còn lại: <?= number_format($don['tong_tien'] - $don['da_thanh_toan']) ?> VNĐ)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div id="payment-info"></div>
                                </div>

                                <div class="form-group">
                                    <label for="so_tien">Số tiền thanh toán <span style="color: red;">*</span></label>
                                    <input type="number" 
                                           id="so_tien" 
                                           name="so_tien" 
                                           class="form-control" 
                                           value="<?= htmlspecialchars($_POST['so_tien'] ?? '') ?>"
                                           min="1000"
                                           step="1000"
                                           placeholder="Nhập số tiền"
                                           required>
                                    <small style="color: #6c757d;">Số tiền tối thiểu: 1,000 VNĐ</small>
                                </div>

                                <div class="form-group">
                                    <label for="phuong_thuc">Phương thức thanh toán</label>
                                    <select id="phuong_thuc" name="phuong_thuc" class="form-control">
                                        <option value="tien_mat" <?= ($_POST["phuong_thuc"] ?? "tien_mat") == "tien_mat" ? "selected" : "" ?>>Tiền mặt</option>
                                        <option value="chuyen_khoan" <?= ($_POST["phuong_thuc"] ?? "tien_mat") == "chuyen_khoan" ? "selected" : "" ?>>Chuyển khoản</option>
                                        <option value="the_tin_dung" <?= ($_POST["phuong_thuc"] ?? "tien_mat") == "the_tin_dung" ? "selected" : "" ?>>Thẻ tín dụng</option>
                                        <option value="vi_dien_tu" <?= ($_POST["phuong_thuc"] ?? "tien_mat") == "vi_dien_tu" ? "selected" : "" ?>>Ví điện tử</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Cột phải -->
                            <div>
                                <div class="form-group">
                                    <label for="trang_thai">Trạng thái</label>
                                    <select id="trang_thai" name="trang_thai" class="form-control">
                                        <option value="thanh_cong" <?= ($_POST["trang_thai"] ?? "thanh_cong") == "thanh_cong" ? "selected" : "" ?>>Thành công</option>
                                        <option value="dang_xu_ly" <?= ($_POST["trang_thai"] ?? "thanh_cong") == "dang_xu_ly" ? "selected" : "" ?>>Đang xử lý</option>
                                        <option value="that_bai" <?= ($_POST["trang_thai"] ?? "thanh_cong") == "that_bai" ? "selected" : "" ?>>Thất bại</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="ghi_chu">Ghi chú</label>
                                    <textarea id="ghi_chu" 
                                              name="ghi_chu" 
                                              class="form-control" 
                                              rows="6"
                                              placeholder="Ghi chú về giao dịch (tuỳ chọn)"><?= htmlspecialchars($_POST["ghi_chu"] ?? "") ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-success">💾 Lưu giao dịch</button>
                            <a href="index.php" class="btn btn-info">📋 Quay lại danh sách</a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Tự động cập nhật thông tin khi trang tải
        window.onload = function() {
            updatePaymentInfo();
        };
    </script>
</body>
</html>