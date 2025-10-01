<?php
session_start();
include("../../include/common.php");

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['admin_id'])) {
    redirect_to("admin/login.php");
}

$error = '';
$success = '';

// Lấy danh sách khách hàng và xe để chọn
$sql = "SELECT id, ho_ten, sdt FROM khach_hang WHERE trang_thai = 1 ORDER BY ho_ten";
$khach_hang_list = db_select($sql);

$sql = "SELECT x.id, x.ma_xe, x.ten_xe, x.gia_thue_ngay, x.gia_thue_gio, lx.ten_loai, hx.ten_hang 
        FROM xe x 
        LEFT JOIN loai_xe lx ON x.loai_xe_id = lx.id 
        LEFT JOIN hang_xe hx ON x.hang_xe_id = hx.id 
        WHERE x.trang_thai = 'san_sang'
        ORDER BY x.ten_xe";
$xe_list = db_select($sql);

// Xử lý form submit
if (is_post_method()) {
    $khach_hang_id = (int)($_POST['khach_hang_id'] ?? 0);
    $xe_id = (int)($_POST['xe_id'] ?? 0);
    $ngay_thue = trim($_POST['ngay_thue'] ?? '');
    $gio_thue = trim($_POST['gio_thue'] ?? '');
    $ngay_tra = trim($_POST['ngay_tra'] ?? '');
    $gio_tra = trim($_POST['gio_tra'] ?? '');
    $dia_diem_nhan = trim($_POST['dia_diem_nhan'] ?? '');
    $dia_diem_tra = trim($_POST['dia_diem_tra'] ?? '');
    $ghi_chu = trim($_POST['ghi_chu'] ?? '');
    $tien_dat_coc = (float)($_POST['tien_dat_coc'] ?? 0);

    // Validate
    if ($khach_hang_id <= 0) {
        $error = 'Vui lòng chọn khách hàng!';
    } elseif ($xe_id <= 0) {
        $error = 'Vui lòng chọn xe!';
    } elseif (empty($ngay_thue)) {
        $error = 'Vui lòng chọn ngày thuê!';
    } elseif (empty($ngay_tra)) {
        $error = 'Vui lòng chọn ngày trả!';
    } elseif ($ngay_tra <= $ngay_thue) {
        $error = 'Ngày trả phải sau ngày thuê!';
    } elseif (empty($dia_diem_nhan)) {
        $error = 'Vui lòng nhập địa điểm nhận xe!';
    } else {
        // Lấy thông tin xe để tính giá
        $sql = "SELECT * FROM xe WHERE id = ?";
        $xe_info = db_select($sql, [$xe_id]);
        
        if (empty($xe_info)) {
            $error = 'Xe không tồn tại!';
        } else {
            $xe_info = $xe_info[0];
            
            // Tính số ngày thuê
            $date1 = new DateTime($ngay_thue);
            $date2 = new DateTime($ngay_tra);
            $so_ngay = $date2->diff($date1)->days;
            if ($so_ngay == 0) $so_ngay = 1; // Ít nhất 1 ngày
            
            // Tính tổng tiền
            $gia_thue = $xe_info['gia_thue_ngay'] * $so_ngay;
            $tong_tien = $gia_thue;
            
            // Tạo mã đơn tự động
            $ma_don = 'DT' . date('ymd') . sprintf('%04d', rand(1, 9999));
            
            // Kiểm tra mã đơn có trùng không
            do {
                $sql = "SELECT id FROM don_thue WHERE ma_don = ?";
                $existing = db_select($sql, [$ma_don]);
                if (!empty($existing)) {
                    $ma_don = 'DT' . date('ymd') . sprintf('%04d', rand(1, 9999));
                }
            } while (!empty($existing));
            
            // Thêm vào database
            $sql = "INSERT INTO don_thue (ma_don, khach_hang_id, xe_id, ngay_thue, gio_thue, ngay_tra, gio_tra, dia_diem_nhan, dia_diem_tra, gia_thue, tien_dat_coc, tong_tien, ghi_chu, trang_thai, admin_xac_nhan, ngay_xac_nhan) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'da_xac_nhan', ?, CURRENT_TIMESTAMP)";
            $result = db_execute($sql, [
                $ma_don, $khach_hang_id, $xe_id, $ngay_thue, 
                $gio_thue ?: null, $ngay_tra, $gio_tra ?: null,
                $dia_diem_nhan, $dia_diem_tra ?: null, $gia_thue, 
                $tien_dat_coc, $tong_tien, $ghi_chu, $_SESSION['admin_id']
            ]);
            
            if ($result) {
                // Cập nhật trạng thái xe
                $sql = "UPDATE xe SET trang_thai = 'dang_thue' WHERE id = ?";
                db_execute($sql, [$xe_id]);
                
                js_alert('Tạo đơn thuê thành công!');
                js_redirect_to('admin/don_thue/index.php');
            } else {
                $error = 'Có lỗi xảy ra khi tạo đơn thuê!';
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
    <title>Tạo đơn thuê - XeDeep</title>
    <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
    <script>
        function calculateTotal() {
            const xeSelect = document.getElementById('xe_id');
            const ngayThue = document.getElementById('ngay_thue').value;
            const ngayTra = document.getElementById('ngay_tra').value;
            
            if (xeSelect.value && ngayThue && ngayTra) {
                const selectedOption = xeSelect.options[xeSelect.selectedIndex];
                const giaThueNgay = parseFloat(selectedOption.dataset.gia || 0);
                
                const date1 = new Date(ngayThue);
                const date2 = new Date(ngayTra);
                const soNgay = Math.max(1, Math.ceil((date2 - date1) / (1000 * 60 * 60 * 24)));
                
                const tongTien = giaThueNgay * soNgay;
                
                document.getElementById('so_ngay_display').textContent = soNgay + ' ngày';
                document.getElementById('gia_thue_display').textContent = new Intl.NumberFormat('vi-VN').format(giaThueNgay) + 'đ/ngày';
                document.getElementById('tong_tien_display').textContent = new Intl.NumberFormat('vi-VN').format(tongTien) + 'đ';
            }
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
            <li><a href="index.php" class="active">Đơn thuê</a></li>
            <li><a href="../admin/index.php">Quản trị viên</a></li>
        </ul>
    </nav>

    <!-- Container -->
    <div class="admin-container">
        <div class="admin-card">
            <div class="admin-card-header">
                <h2>➕ Tạo đơn thuê mới</h2>
            </div>
            <div class="admin-card-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" class="admin-form">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                        <!-- Cột trái -->
                        <div>
                            <div class="form-group">
                                <label for="khach_hang_id">Khách hàng <span style="color: red;">*</span></label>
                                <select id="khach_hang_id" name="khach_hang_id" class="form-control" required>
                                    <option value="">-- Chọn khách hàng --</option>
                                    <?php foreach ($khach_hang_list as $kh): ?>
                                        <option value="<?= $kh['id'] ?>" 
                                                <?= ($_POST['khach_hang_id'] ?? 0) == $kh['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($kh['ho_ten']) ?> - <?= htmlspecialchars($kh['sdt']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="xe_id">Xe <span style="color: red;">*</span></label>
                                <select id="xe_id" name="xe_id" class="form-control" required onchange="calculateTotal()">
                                    <option value="">-- Chọn xe --</option>
                                    <?php foreach ($xe_list as $xe): ?>
                                        <option value="<?= $xe['id'] ?>" 
                                                data-gia="<?= $xe['gia_thue_ngay'] ?>"
                                                <?= ($_POST['xe_id'] ?? 0) == $xe['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($xe['ma_xe']) ?> - <?= htmlspecialchars($xe['ten_xe']) ?> 
                                            (<?= htmlspecialchars($xe['ten_loai']) ?> - <?= htmlspecialchars($xe['ten_hang']) ?>) 
                                            - <?= number_format($xe['gia_thue_ngay']) ?>đ/ngày
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="ngay_thue">Ngày thuê <span style="color: red;">*</span></label>
                                <input type="date" 
                                       id="ngay_thue" 
                                       name="ngay_thue" 
                                       class="form-control" 
                                       value="<?= $_POST['ngay_thue'] ?? date('Y-m-d') ?>"
                                       min="<?= date('Y-m-d') ?>"
                                       onchange="calculateTotal()"
                                       required>
                            </div>

                            <div class="form-group">
                                <label for="gio_thue">Giờ thuê</label>
                                <input type="time" 
                                       id="gio_thue" 
                                       name="gio_thue" 
                                       class="form-control" 
                                       value="<?= $_POST['gio_thue'] ?? '08:00' ?>">
                            </div>

                            <div class="form-group">
                                <label for="ngay_tra">Ngày trả <span style="color: red;">*</span></label>
                                <input type="date" 
                                       id="ngay_tra" 
                                       name="ngay_tra" 
                                       class="form-control" 
                                       value="<?= $_POST['ngay_tra'] ?? date('Y-m-d', strtotime('+1 day')) ?>"
                                       min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                                       onchange="calculateTotal()"
                                       required>
                            </div>

                            <div class="form-group">
                                <label for="gio_tra">Giờ trả</label>
                                <input type="time" 
                                       id="gio_tra" 
                                       name="gio_tra" 
                                       class="form-control" 
                                       value="<?= $_POST['gio_tra'] ?? '18:00' ?>">
                            </div>
                        </div>

                        <!-- Cột phải -->
                        <div>
                            <div class="form-group">
                                <label for="dia_diem_nhan">Địa điểm nhận xe <span style="color: red;">*</span></label>
                                <textarea id="dia_diem_nhan" 
                                          name="dia_diem_nhan" 
                                          class="form-control" 
                                          rows="3" 
                                          placeholder="Nhập địa chỉ cụ thể..."
                                          required><?= htmlspecialchars($_POST['dia_diem_nhan'] ?? '') ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="dia_diem_tra">Địa điểm trả xe</label>
                                <textarea id="dia_diem_tra" 
                                          name="dia_diem_tra" 
                                          class="form-control" 
                                          rows="3" 
                                          placeholder="Để trống nếu trả tại nơi nhận xe"><?= htmlspecialchars($_POST['dia_diem_tra'] ?? '') ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="tien_dat_coc">Tiền đặt cọc (VNĐ)</label>
                                <input type="number" 
                                       id="tien_dat_coc" 
                                       name="tien_dat_coc" 
                                       class="form-control" 
                                       value="<?= $_POST['tien_dat_coc'] ?? 0 ?>"
                                       min="0" 
                                       step="10000">
                            </div>

                            <div class="form-group">
                                <label for="ghi_chu">Ghi chú</label>
                                <textarea id="ghi_chu" 
                                          name="ghi_chu" 
                                          class="form-control" 
                                          rows="3" 
                                          placeholder="Ghi chú thêm..."><?= htmlspecialchars($_POST['ghi_chu'] ?? '') ?></textarea>
                            </div>

                            <!-- Thông tin tính toán -->
                            <div class="admin-card" style="background: #f8f9fa; padding: 1rem; margin-top: 1rem;">
                                <h4 style="margin-bottom: 1rem; color: #495057;">💰 Thông tin thanh toán</h4>
                                <p><strong>Số ngày thuê:</strong> <span id="so_ngay_display">-- ngày</span></p>
                                <p><strong>Giá thuê:</strong> <span id="gia_thue_display">--đ/ngày</span></p>
                                <p><strong>Tổng tiền:</strong> <span id="tong_tien_display" style="color: #e74c3c; font-size: 1.2em;">--đ</span></p>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-success">💾 Tạo đơn thuê</button>
                        <a href="index.php" class="btn btn-info">📋 Quay lại danh sách</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Tính toán tự động khi trang load
        window.onload = function() {
            calculateTotal();
        };
    </script>
</body>
</html>