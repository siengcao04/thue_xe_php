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
    js_redirect_to('admin/don_thue/index.php');
}

// Lấy thông tin đơn thuê
$sql = "SELECT dt.*, kh.ho_ten as ten_khach_hang, x.ten_xe, x.ma_xe
        FROM don_thue dt 
        LEFT JOIN khach_hang kh ON dt.khach_hang_id = kh.id 
        LEFT JOIN xe x ON dt.xe_id = x.id 
        WHERE dt.id = ?";
$don_thue = db_select($sql, [$id]);

if (empty($don_thue)) {
    js_alert('Không tìm thấy đơn thuê!');
    js_redirect_to('admin/don_thue/index.php');
}

$don_thue = $don_thue[0];

// Lấy danh sách khách hàng và xe để chọn
$sql = "SELECT id, ho_ten, sdt FROM khach_hang WHERE trang_thai = 1 ORDER BY ho_ten";
$khach_hang_list = db_select($sql);

$sql = "SELECT x.id, x.ma_xe, x.ten_xe, x.gia_thue_ngay, x.gia_thue_gio, lx.ten_loai, hx.ten_hang 
        FROM xe x 
        LEFT JOIN loai_xe lx ON x.loai_xe_id = lx.id 
        LEFT JOIN hang_xe hx ON x.hang_xe_id = hx.id 
        WHERE x.trang_thai IN ('san_sang', 'dang_thue') OR x.id = ?
        ORDER BY x.ten_xe";
$xe_list = db_select($sql, [$don_thue['xe_id']]);

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
    $trang_thai = $_POST['trang_thai'] ?? $don_thue['trang_thai'];
    $ly_do_huy = trim($_POST['ly_do_huy'] ?? '');

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
    } elseif ($trang_thai == 'huy' && empty($ly_do_huy)) {
        $error = 'Vui lòng nhập lý do hủy!';
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
            
            // Cập nhật database
            $sql = "UPDATE don_thue SET khach_hang_id = ?, xe_id = ?, ngay_thue = ?, gio_thue = ?, ngay_tra = ?, gio_tra = ?, dia_diem_nhan = ?, dia_diem_tra = ?, gia_thue = ?, tien_dat_coc = ?, tong_tien = ?, ghi_chu = ?, trang_thai = ?, ly_do_huy = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $result = db_execute($sql, [
                $khach_hang_id, $xe_id, $ngay_thue, 
                $gio_thue ?: null, $ngay_tra, $gio_tra ?: null,
                $dia_diem_nhan, $dia_diem_tra ?: null, $gia_thue, 
                $tien_dat_coc, $tong_tien, $ghi_chu, $trang_thai,
                $trang_thai == 'huy' ? $ly_do_huy : null, $id
            ]);
            
            if ($result) {
                // Cập nhật trạng thái xe nếu có thay đổi xe hoặc trạng thái đơn
                if ($xe_id != $don_thue['xe_id'] || $trang_thai != $don_thue['trang_thai']) {
                    // Trả xe cũ về trạng thái sẵn sàng nếu đổi xe
                    if ($xe_id != $don_thue['xe_id']) {
                        $sql = "UPDATE xe SET trang_thai = 'san_sang' WHERE id = ?";
                        db_execute($sql, [$don_thue['xe_id']]);
                    }
                    
                    // Cập nhật trạng thái xe mới
                    $xe_status = '';
                    switch ($trang_thai) {
                        case 'da_xac_nhan':
                        case 'dang_thue':
                            $xe_status = 'dang_thue';
                            break;
                        case 'da_tra':
                        case 'huy':
                            $xe_status = 'san_sang';
                            break;
                    }
                    
                    if ($xe_status) {
                        $sql = "UPDATE xe SET trang_thai = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
                        db_execute($sql, [$xe_status, $xe_id]);
                    }
                }
                
                js_alert('Cập nhật đơn thuê thành công!');
                js_redirect_to('admin/don_thue/index.php');
            } else {
                $error = 'Có lỗi xảy ra khi cập nhật đơn thuê!';
            }
        }
    }
} else {
    // Load dữ liệu hiện tại vào form
    $_POST = [
        'khach_hang_id' => $don_thue['khach_hang_id'],
        'xe_id' => $don_thue['xe_id'],
        'ngay_thue' => $don_thue['ngay_thue'],
        'gio_thue' => $don_thue['gio_thue'],
        'ngay_tra' => $don_thue['ngay_tra'],
        'gio_tra' => $don_thue['gio_tra'],
        'dia_diem_nhan' => $don_thue['dia_diem_nhan'],
        'dia_diem_tra' => $don_thue['dia_diem_tra'],
        'tien_dat_coc' => $don_thue['tien_dat_coc'],
        'ghi_chu' => $don_thue['ghi_chu'],
        'trang_thai' => $don_thue['trang_thai'],
        'ly_do_huy' => $don_thue['ly_do_huy']
    ];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa đơn thuê - XeDeep</title>
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
        
        function toggleLyDoHuy() {
            const trangThai = document.getElementById('trang_thai').value;
            const lyDoHuyGroup = document.getElementById('ly_do_huy_group');
            
            if (trangThai === 'huy') {
                lyDoHuyGroup.style.display = 'block';
                document.getElementById('ly_do_huy').required = true;
            } else {
                lyDoHuyGroup.style.display = 'none';
                document.getElementById('ly_do_huy').required = false;
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
                <h2>✏️ Sửa đơn thuê: <?= htmlspecialchars($don_thue['ma_don']) ?></h2>
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
                                       value="<?= $_POST['ngay_thue'] ?? '' ?>"
                                       onchange="calculateTotal()"
                                       required>
                            </div>

                            <div class="form-group">
                                <label for="gio_thue">Giờ thuê</label>
                                <input type="time" 
                                       id="gio_thue" 
                                       name="gio_thue" 
                                       class="form-control" 
                                       value="<?= $_POST['gio_thue'] ?? '' ?>">
                            </div>

                            <div class="form-group">
                                <label for="ngay_tra">Ngày trả <span style="color: red;">*</span></label>
                                <input type="date" 
                                       id="ngay_tra" 
                                       name="ngay_tra" 
                                       class="form-control" 
                                       value="<?= $_POST['ngay_tra'] ?? '' ?>"
                                       onchange="calculateTotal()"
                                       required>
                            </div>

                            <div class="form-group">
                                <label for="gio_tra">Giờ trả</label>
                                <input type="time" 
                                       id="gio_tra" 
                                       name="gio_tra" 
                                       class="form-control" 
                                       value="<?= $_POST['gio_tra'] ?? '' ?>">
                            </div>

                            <div class="form-group">
                                <label for="trang_thai">Trạng thái</label>
                                <select id="trang_thai" name="trang_thai" class="form-control" onchange="toggleLyDoHuy()">
                                    <option value="cho_xac_nhan" <?= ($_POST['trang_thai'] ?? '') == 'cho_xac_nhan' ? 'selected' : '' ?>>Chờ xác nhận</option>
                                    <option value="da_xac_nhan" <?= ($_POST['trang_thai'] ?? '') == 'da_xac_nhan' ? 'selected' : '' ?>>Đã xác nhận</option>
                                    <option value="dang_thue" <?= ($_POST['trang_thai'] ?? '') == 'dang_thue' ? 'selected' : '' ?>>Đang thuê</option>
                                    <option value="da_tra" <?= ($_POST['trang_thai'] ?? '') == 'da_tra' ? 'selected' : '' ?>>Đã trả</option>
                                    <option value="huy" <?= ($_POST['trang_thai'] ?? '') == 'huy' ? 'selected' : '' ?>>Hủy</option>
                                </select>
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
                                          required><?= htmlspecialchars($_POST['dia_diem_nhan'] ?? '') ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="dia_diem_tra">Địa điểm trả xe</label>
                                <textarea id="dia_diem_tra" 
                                          name="dia_diem_tra" 
                                          class="form-control" 
                                          rows="3"><?= htmlspecialchars($_POST['dia_diem_tra'] ?? '') ?></textarea>
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
                                          rows="3"><?= htmlspecialchars($_POST['ghi_chu'] ?? '') ?></textarea>
                            </div>

                            <div class="form-group" id="ly_do_huy_group" style="display: <?= ($_POST['trang_thai'] ?? '') == 'huy' ? 'block' : 'none' ?>;">
                                <label for="ly_do_huy">Lý do hủy <span style="color: red;">*</span></label>
                                <textarea id="ly_do_huy" 
                                          name="ly_do_huy" 
                                          class="form-control" 
                                          rows="3"><?= htmlspecialchars($_POST['ly_do_huy'] ?? '') ?></textarea>
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
                        <button type="submit" class="btn btn-success">💾 Cập nhật đơn thuê</button>
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
            toggleLyDoHuy();
        };
    </script>
</body>
</html>