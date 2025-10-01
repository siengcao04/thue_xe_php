<?php
session_start();
include("../../include/common.php");

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['admin_id'])) {
    redirect_to("admin/login.php");
}

// Lấy danh sách thanh toán với thông tin liên quan
$sql = "SELECT 
            tt.*,
            kh.ho_ten as ten_khach_hang,
            kh.email as email_khach_hang,
            dt.ma_don_thue,
            dt.tong_tien as tong_tien_don
        FROM thanh_toan tt
        JOIN don_thue dt ON tt.don_thue_id = dt.id
        JOIN khach_hang kh ON dt.khach_hang_id = kh.id
        ORDER BY tt.ngay_thanh_toan DESC";
$thanh_toan_list = db_select($sql);

// Thống kê thanh toán
$sql = "SELECT 
            SUM(so_tien) as tong_thanh_toan,
            COUNT(*) as so_giao_dich,
            SUM(CASE WHEN trang_thai = 'thanh_cong' THEN so_tien ELSE 0 END) as thanh_toan_thanh_cong,
            SUM(CASE WHEN trang_thai = 'that_bai' THEN 1 ELSE 0 END) as giao_dich_that_bai
        FROM thanh_toan";
$thong_ke = db_select($sql);
$stats = $thong_ke[0] ?? [];

// Thống kê theo phương thức thanh toán
$sql = "SELECT 
            phuong_thuc,
            COUNT(*) as so_luong,
            SUM(so_tien) as tong_tien
        FROM thanh_toan 
        WHERE trang_thai = 'thanh_cong'
        GROUP BY phuong_thuc
        ORDER BY tong_tien DESC";
$thong_ke_phuong_thuc = db_select($sql);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý thanh toán - XeDeep</title>
    <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
    <style>
        .payment-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 8px;
            text-align: center;
        }
        .stat-box.success {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        .stat-box.danger {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .payment-method {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85em;
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
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8em;
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
            <div class="admin-card-header clearfix">
                <h2>💳 Quản lý thanh toán</h2>
                <a href="create.php" class="btn btn-success">➕ Thêm thanh toán</a>
            </div>
            <div class="admin-card-body">
                <!-- Thống kê thanh toán -->
                <div class="payment-stats">
                    <div class="stat-box">
                        <div class="stat-number"><?= number_format($stats['tong_thanh_toan'] ?? 0) ?></div>
                        <div>Tổng thanh toán (VNĐ)</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number"><?= number_format($stats['so_giao_dich'] ?? 0) ?></div>
                        <div>Số giao dịch</div>
                    </div>
                    <div class="stat-box success">
                        <div class="stat-number"><?= number_format($stats['thanh_toan_thanh_cong'] ?? 0) ?></div>
                        <div>Thanh toán thành công (VNĐ)</div>
                    </div>
                    <div class="stat-box danger">
                        <div class="stat-number"><?= number_format($stats['giao_dich_that_bai'] ?? 0) ?></div>
                        <div>Giao dịch thất bại</div>
                    </div>
                </div>

                <!-- Thống kê theo phương thức -->
                <?php if (!empty($thong_ke_phuong_thuc)): ?>
                    <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
                        <h4 style="margin-top: 0;">Thống kê theo phương thức thanh toán:</h4>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <?php foreach ($thong_ke_phuong_thuc as $pt): ?>
                                <div style="text-align: center; padding: 1rem; background: white; border-radius: 6px;">
                                    <div class="payment-method method-<?= $pt['phuong_thuc'] ?>">
                                        <?= ucfirst(str_replace('_', ' ', $pt['phuong_thuc'])) ?>
                                    </div>
                                    <div style="margin-top: 0.5rem; font-weight: bold;"><?= number_format($pt['so_luong']) ?> giao dịch</div>
                                    <div style="color: #666;"><?= number_format($pt['tong_tien']) ?> VNĐ</div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Danh sách thanh toán -->
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Mã đơn thuê</th>
                                <th>Khách hàng</th>
                                <th>Số tiền</th>
                                <th>Phương thức</th>
                                <th>Trạng thái</th>
                                <th>Ngày thanh toán</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($thanh_toan_list)): ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 2rem;">Chưa có giao dịch thanh toán nào</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($thanh_toan_list as $index => $tt): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td>
                                            <span style="background: #e3f2fd; color: #1976d2; padding: 3px 8px; border-radius: 4px; font-size: 0.9em;">
                                                <?= htmlspecialchars($tt['ma_don_thue']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($tt['ten_khach_hang']) ?></strong><br>
                                            <small style="color: #666;"><?= htmlspecialchars($tt['email_khach_hang']) ?></small>
                                        </td>
                                        <td style="text-align: right; font-weight: bold;">
                                            <?= number_format($tt['so_tien']) ?> VNĐ
                                            <?php if ($tt['so_tien'] != $tt['tong_tien_don']): ?>
                                                <br><small style="color: #666;">/ <?= number_format($tt['tong_tien_don']) ?> VNĐ</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="payment-method method-<?= $tt['phuong_thuc'] ?>">
                                                <?= ucfirst(str_replace('_', ' ', $tt['phuong_thuc'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?= $tt['trang_thai'] ?>">
                                                <?= ucfirst(str_replace('_', ' ', $tt['trang_thai'])) ?>
                                            </span>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($tt['ngay_thanh_toan'])) ?></td>
                                        <td>
                                            <a href="view.php?id=<?= $tt['id'] ?>" 
                                               class="btn btn-info btn-sm" 
                                               title="Xem chi tiết">
                                                👁️ Xem
                                            </a>
                                            
                                            <a href="edit.php?id=<?= $tt['id'] ?>" 
                                               class="btn btn-warning btn-sm" 
                                               title="Chỉnh sửa">
                                                ✏️ Sửa
                                            </a>
                                            
                                            <a href="delete.php?id=<?= $tt['id'] ?>" 
                                               class="btn btn-danger btn-sm" 
                                               title="Xóa giao dịch"
                                               onclick="return confirm('Bạn có chắc muốn xóa giao dịch này?')">
                                                🗑️ Xóa
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>