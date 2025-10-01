<?php
session_start();
include("../../include/common.php");

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['admin_id'])) {
    redirect_to("admin/login.php");
}

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    js_alert('ID không hợp lệ!');
    js_redirect_to('admin/thanh_toan/index.php');
}

// Lấy thông tin thanh toán chi tiết
$sql = "SELECT 
            tt.*,
            dt.ma_don_thue,
            dt.ngay_thue,
            dt.ngay_tra,
            dt.tong_tien as tong_tien_don,
            dt.trang_thai as trang_thai_don,
            kh.ho_ten as ten_khach_hang,
            kh.email as email_khach_hang,
            kh.sdt as sdt_khach_hang,
            x.ten_xe,
            x.bien_so
        FROM thanh_toan tt
        JOIN don_thue dt ON tt.don_thue_id = dt.id
        JOIN khach_hang kh ON dt.khach_hang_id = kh.id
        JOIN xe x ON dt.xe_id = x.id
        WHERE tt.id = ?";
$thanh_toan = db_select($sql, [$id]);

if (empty($thanh_toan)) {
    js_alert('Không tìm thấy giao dịch thanh toán!');
    js_redirect_to('admin/thanh_toan/index.php');
}

$tt = $thanh_toan[0];

// Lấy tất cả giao dịch thanh toán của đơn này
$sql = "SELECT * FROM thanh_toan WHERE don_thue_id = ? ORDER BY ngay_thanh_toan DESC";
$tat_ca_thanh_toan = db_select($sql, [$tt['don_thue_id']]);

// Tính tổng đã thanh toán
$tong_da_thanh_toan = 0;
foreach ($tat_ca_thanh_toan as $payment) {
    if ($payment['trang_thai'] == 'thanh_cong') {
        $tong_da_thanh_toan += $payment['so_tien'];
    }
}
$con_lai = $tt['tong_tien_don'] - $tong_da_thanh_toan;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết thanh toán - XeDeep</title>
    <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
    <style>
        .payment-display {
            text-align: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .payment-amount {
            font-size: 3rem;
            font-weight: bold;
            margin: 1rem 0;
        }
        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        .detail-card {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }
        .detail-label {
            font-weight: bold;
            color: #495057;
            margin-bottom: 0.5rem;
        }
        .detail-value {
            color: #212529;
            font-size: 1.1rem;
        }
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: bold;
        }
        .status-thanh_cong {
            background: #d4edda;
            color: #155724;
        }
        .status-that_bai {
            background: #f8d7da;
            color: #721c24;
        }
        .status-dang_xu_ly {
            background: #fff3cd;
            color: #856404;
        }
        .payment-method {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.9em;
            font-weight: bold;
        }
        .method-tien_mat {
            background: #e8f5e8;
            color: #2e7d32;
        }
        .method-chuyen_khoan {
            background: #e3f2fd;
            color: #1976d2;
        }
        .method-the_tin_dung {
            background: #fff3e0;
            color: #f57c00;
        }
        .method-vi_dien_tu {
            background: #f3e5f5;
            color: #7b1fa2;
        }
    </style>
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
                <h2>💳 Chi tiết giao dịch thanh toán</h2>
            </div>
            <div class="admin-card-body">
                <!-- Hiển thị số tiền thanh toán -->
                <div class="payment-display">
                    <h3>Số tiền giao dịch</h3>
                    <div class="payment-amount"><?= number_format($tt['so_tien']) ?> VNĐ</div>
                    <div>
                        <span class="payment-method method-<?= $tt['phuong_thuc'] ?>">
                            <?= ucfirst(str_replace('_', ' ', $tt['phuong_thuc'])) ?>
                        </span>
                        <span class="status-badge status-<?= $tt['trang_thai'] ?>" style="margin-left: 1rem;">
                            <?= ucfirst(str_replace('_', ' ', $tt['trang_thai'])) ?>
                        </span>
                    </div>
                </div>

                <!-- Thông tin chi tiết -->
                <div class="detail-grid">
                    <!-- Thông tin khách hàng -->
                    <div class="detail-card">
                        <h4 style="margin-top: 0; color: #007bff;">👤 Thông tin khách hàng</h4>
                        <div class="detail-label">Họ tên:</div>
                        <div class="detail-value"><?= htmlspecialchars($tt['ten_khach_hang']) ?></div>
                        
                        <div class="detail-label" style="margin-top: 1rem;">Email:</div>
                        <div class="detail-value"><?= htmlspecialchars($tt['email_khach_hang']) ?></div>
                        
                        <?php if ($tt['sdt_khach_hang']): ?>
                            <div class="detail-label" style="margin-top: 1rem;">Số điện thoại:</div>
                            <div class="detail-value"><?= htmlspecialchars($tt['sdt_khach_hang']) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Thông tin đơn thuê -->
                    <div class="detail-card">
                        <h4 style="margin-top: 0; color: #007bff;">📋 Thông tin đơn thuê</h4>
                        <div class="detail-label">Mã đơn thuê:</div>
                        <div class="detail-value"><?= htmlspecialchars($tt['ma_don_thue']) ?></div>
                        
                        <div class="detail-label" style="margin-top: 1rem;">Xe thuê:</div>
                        <div class="detail-value"><?= htmlspecialchars($tt['ten_xe']) ?> (<?= htmlspecialchars($tt['bien_so']) ?>)</div>
                        
                        <div class="detail-label" style="margin-top: 1rem;">Thời gian thuê:</div>
                        <div class="detail-value"><?= date('d/m/Y', strtotime($tt['ngay_thue'])) ?> - <?= date('d/m/Y', strtotime($tt['ngay_tra'])) ?></div>
                        
                        <div class="detail-label" style="margin-top: 1rem;">Trạng thái đơn:</div>
                        <div class="detail-value"><?= ucfirst(str_replace('_', ' ', $tt['trang_thai_don'])) ?></div>
                    </div>
                </div>

                <!-- Thông tin giao dịch -->
                <div class="detail-card" style="margin-bottom: 2rem;">
                    <h4 style="margin-top: 0; color: #007bff;">💰 Thông tin giao dịch</h4>
                    <div class="detail-grid">
                        <div>
                            <div class="detail-label">Ngày thanh toán:</div>
                            <div class="detail-value"><?= date('d/m/Y H:i:s', strtotime($tt['ngay_thanh_toan'])) ?></div>
                            
                            <div class="detail-label" style="margin-top: 1rem;">Số tiền:</div>
                            <div class="detail-value"><?= number_format($tt['so_tien']) ?> VNĐ</div>
                        </div>
                        <div>
                            <div class="detail-label">Phương thức:</div>
                            <div class="detail-value">
                                <span class="payment-method method-<?= $tt['phuong_thuc'] ?>">
                                    <?= ucfirst(str_replace('_', ' ', $tt['phuong_thuc'])) ?>
                                </span>
                            </div>
                            
                            <div class="detail-label" style="margin-top: 1rem;">Trạng thái:</div>
                            <div class="detail-value">
                                <span class="status-badge status-<?= $tt['trang_thai'] ?>">
                                    <?= ucfirst(str_replace('_', ' ', $tt['trang_thai'])) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($tt['ghi_chu']): ?>
                        <div style="margin-top: 1.5rem;">
                            <div class="detail-label">Ghi chú:</div>
                            <div style="background: #fff; padding: 1rem; border-radius: 6px; border-left: 4px solid #28a745;">
                                <?= nl2br(htmlspecialchars($tt['ghi_chu'])) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Tổng quan thanh toán đơn -->
                <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 1.5rem; border-radius: 10px; margin-bottom: 2rem;">
                    <h4 style="margin-top: 0;">Tổng quan thanh toán đơn thuê</h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; text-align: center;">
                        <div>
                            <div style="font-size: 1.8rem; font-weight: bold;"><?= number_format($tt['tong_tien_don']) ?></div>
                            <div>Tổng tiền đơn</div>
                        </div>
                        <div>
                            <div style="font-size: 1.8rem; font-weight: bold;"><?= number_format($tong_da_thanh_toan) ?></div>
                            <div>Đã thanh toán</div>
                        </div>
                        <div>
                            <div style="font-size: 1.8rem; font-weight: bold; color: <?= $con_lai > 0 ? '#ffeb3b' : '#4caf50' ?>;"><?= number_format($con_lai) ?></div>
                            <div>Còn lại</div>
                        </div>
                    </div>
                </div>

                <!-- Lịch sử thanh toán đơn -->
                <?php if (count($tat_ca_thanh_toan) > 1): ?>
                    <div style="background: white; padding: 1.5rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 2rem;">
                        <h4 style="margin-top: 0; color: #333;">Lịch sử tất cả giao dịch của đơn này:</h4>
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>STT</th>
                                        <th>Số tiền</th>
                                        <th>Phương thức</th>
                                        <th>Trạng thái</th>
                                        <th>Ngày thanh toán</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tat_ca_thanh_toan as $index => $payment): ?>
                                        <tr <?= $payment['id'] == $tt['id'] ? 'style="background: #e3f2fd;"' : '' ?>>
                                            <td><?= $index + 1 ?><?= $payment['id'] == $tt['id'] ? ' (Hiện tại)' : '' ?></td>
                                            <td style="text-align: right; font-weight: bold;"><?= number_format($payment['so_tien']) ?> VNĐ</td>
                                            <td>
                                                <span class="payment-method method-<?= $payment['phuong_thuc'] ?>">
                                                    <?= ucfirst(str_replace('_', ' ', $payment['phuong_thuc'])) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?= $payment['trang_thai'] ?>">
                                                    <?= ucfirst(str_replace('_', ' ', $payment['trang_thai'])) ?>
                                                </span>
                                            </td>
                                            <td><?= date('d/m/Y H:i', strtotime($payment['ngay_thanh_toan'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Nút thao tác -->
                <div class="form-group">
                    <a href="index.php" class="btn btn-info">📋 Quay lại danh sách</a>
                    <a href="edit.php?id=<?= $tt['id'] ?>" class="btn btn-warning">✏️ Chỉnh sửa</a>
                    <a href="delete.php?id=<?= $tt['id'] ?>" 
                       class="btn btn-danger"
                       onclick="return confirm('Bạn có chắc muốn xóa giao dịch này?')">
                        🗑️ Xóa giao dịch
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>