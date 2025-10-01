<?php
session_start();
include("../include/common.php");

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['admin_id'])) {
    redirect_to("login.php");
}

// Lấy thống kê tổng quan
$stats = [];

// Tổng số xe
$sql = "SELECT COUNT(*) as total FROM xe";
$result = db_select($sql);
$stats['total_xe'] = $result[0]['total'];

// Xe đang hoạt động
$sql = "SELECT COUNT(*) as total FROM xe WHERE trang_thai = 'san_sang'";
$result = db_select($sql);
$stats['xe_san_sang'] = $result[0]['total'];

// Xe đang được thuê
$sql = "SELECT COUNT(*) as total FROM xe WHERE trang_thai = 'dang_thue'";
$result = db_select($sql);
$stats['xe_dang_thue'] = $result[0]['total'];

// Tổng số khách hàng
$sql = "SELECT COUNT(*) as total FROM khach_hang";
$result = db_select($sql);
$stats['total_khach_hang'] = $result[0]['total'];

// Đơn thuê hôm nay
$sql = "SELECT COUNT(*) as total FROM don_thue WHERE DATE(created_at) = CURDATE()";
$result = db_select($sql);
$stats['don_thue_hom_nay'] = $result[0]['total'];

// Đơn thuê đang chờ xác nhận
$sql = "SELECT COUNT(*) as total FROM don_thue WHERE trang_thai = 'cho_xac_nhan'";
$result = db_select($sql);
$stats['don_cho_xac_nhan'] = $result[0]['total'];

// Doanh thu tháng này
$sql = "SELECT COALESCE(SUM(tong_tien), 0) as total FROM don_thue WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) AND trang_thai = 'da_tra'";
$result = db_select($sql);
$stats['doanh_thu_thang'] = $result[0]['total'];

// Lấy 5 đơn thuê mới nhất
$sql = "SELECT dt.*, kh.ho_ten, x.ten_xe 
        FROM don_thue dt 
        JOIN khach_hang kh ON dt.khach_hang_id = kh.id 
        JOIN xe x ON dt.xe_id = x.id 
        ORDER BY dt.created_at DESC 
        LIMIT 5";
$don_thue_moi = db_select($sql);

// Lấy 5 xe được thuê nhiều nhất
$sql = "SELECT x.*, COUNT(dt.id) as so_lan_thue, lx.ten_loai
        FROM xe x 
        LEFT JOIN don_thue dt ON x.id = dt.xe_id 
        JOIN loai_xe lx ON x.loai_xe_id = lx.id
        GROUP BY x.id 
        ORDER BY so_lan_thue DESC 
        LIMIT 5";
$xe_pho_bien = db_select($sql);

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Quản trị XeDeep</title>
    <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
</head>
<body>
    <!-- Header -->
    <div class="admin-header clearfix">
        <h1>🚗 Hệ thống quản lý thuê xe XeDeep</h1>
        <div class="user-info">
            <span>Xin chào, <?= htmlspecialchars($_SESSION['admin_ho_ten'] ?? $_SESSION['admin_username'] ?? 'Admin') ?></span>
            <a href="logout.php">Đăng xuất</a>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="admin-nav">
        <ul>
            <li><a href="index.php" class="active">Dashboard</a></li>
            <li><a href="loai_xe/index.php">Loại xe</a></li>
            <li><a href="hang_xe/index.php">Hãng xe</a></li>
            <li><a href="xe/index.php">Quản lý xe</a></li>
            <li><a href="khach_hang/index.php">Khách hàng</a></li>
            <li><a href="don_thue/index.php">Đơn thuê</a></li>
            <li><a href="admin/index.php">Quản trị viên</a></li>
        </ul>
    </nav>

    <!-- Container -->
    <div class="admin-container">
        <!-- Statistics Cards -->
        <div class="stats-container">
            <div class="stats-card">
                <div class="stats-number"><?= number_format($stats['total_xe']) ?></div>
                <div class="stats-label">Tổng số xe</div>
            </div>
            
            <div class="stats-card success">
                <div class="stats-number"><?= number_format($stats['xe_san_sang']) ?></div>
                <div class="stats-label">Xe sẵn sàng</div>
            </div>
            
            <div class="stats-card warning">
                <div class="stats-number"><?= number_format($stats['xe_dang_thue']) ?></div>
                <div class="stats-label">Xe đang thuê</div>
            </div>
            
            <div class="stats-card">
                <div class="stats-number"><?= number_format($stats['total_khach_hang']) ?></div>
                <div class="stats-label">Khách hàng</div>
            </div>
            
            <div class="stats-card danger">
                <div class="stats-number"><?= number_format($stats['don_cho_xac_nhan']) ?></div>
                <div class="stats-label">Đơn chờ duyệt</div>
            </div>
            
            <div class="stats-card success">
                <div class="stats-number"><?= number_format($stats['don_thue_hom_nay']) ?></div>
                <div class="stats-label">Đơn thuê hôm nay</div>
            </div>
            
            <div class="stats-card" style="grid-column: span 2;">
                <div class="stats-number"><?= number_format($stats['doanh_thu_thang']) ?>đ</div>
                <div class="stats-label">Doanh thu tháng này</div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <!-- Đơn thuê mới nhất -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2>📋 Đơn thuê mới nhất</h2>
                </div>
                <div class="admin-card-body">
                    <?php if (empty($don_thue_moi)): ?>
                        <p style="text-align: center; color: #7f8c8d;">Chưa có đơn thuê nào</p>
                    <?php else: ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Mã đơn</th>
                                    <th>Khách hàng</th>
                                    <th>Xe</th>
                                    <th>Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($don_thue_moi as $don): ?>
                                <tr>
                                    <td><?= htmlspecialchars($don['ma_don']) ?></td>
                                    <td><?= htmlspecialchars($don['ho_ten']) ?></td>
                                    <td><?= htmlspecialchars($don['ten_xe']) ?></td>
                                    <td>
                                        <?php
                                        $status_class = '';
                                        $status_text = '';
                                        switch($don['trang_thai']) {
                                            case 'cho_xac_nhan':
                                                $status_class = 'status-pending';
                                                $status_text = 'Chờ xác nhận';
                                                break;
                                            case 'da_xac_nhan':
                                                $status_class = 'status-active';
                                                $status_text = 'Đã xác nhận';
                                                break;
                                            case 'dang_thue':
                                                $status_class = 'status-warning';
                                                $status_text = 'Đang thuê';
                                                break;
                                            case 'da_tra':
                                                $status_class = 'status-active';
                                                $status_text = 'Đã trả';
                                                break;
                                            case 'huy':
                                                $status_class = 'status-inactive';
                                                $status_text = 'Đã hủy';
                                                break;
                                        }
                                        ?>
                                        <span class="status-badge <?= $status_class ?>"><?= $status_text ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="text-center mt-3">
                            <a href="don_thue/index.php" class="btn btn-primary">Xem tất cả</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Xe phổ biến -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2>🏆 Xe được thuê nhiều nhất</h2>
                </div>
                <div class="admin-card-body">
                    <?php if (empty($xe_pho_bien)): ?>
                        <p style="text-align: center; color: #7f8c8d;">Chưa có dữ liệu</p>
                    <?php else: ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Tên xe</th>
                                    <th>Loại</th>
                                    <th>Số lần thuê</th>
                                    <th>Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($xe_pho_bien as $xe): ?>
                                <tr>
                                    <td><?= htmlspecialchars($xe['ten_xe']) ?></td>
                                    <td><?= htmlspecialchars($xe['ten_loai']) ?></td>
                                    <td><strong><?= $xe['so_lan_thue'] ?></strong></td>
                                    <td>
                                        <?php
                                        $status_class = '';
                                        $status_text = '';
                                        switch($xe['trang_thai']) {
                                            case 'san_sang':
                                                $status_class = 'status-active';
                                                $status_text = 'Sẵn sàng';
                                                break;
                                            case 'dang_thue':
                                                $status_class = 'status-pending';
                                                $status_text = 'Đang thuê';
                                                break;
                                            case 'bao_tri':
                                                $status_class = 'status-warning';
                                                $status_text = 'Bảo trì';
                                                break;
                                            case 'khong_hoat_dong':
                                                $status_class = 'status-inactive';
                                                $status_text = 'Không hoạt động';
                                                break;
                                        }
                                        ?>
                                        <span class="status-badge <?= $status_class ?>"><?= $status_text ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="text-center mt-3">
                            <a href="xe/index.php" class="btn btn-primary">Xem tất cả</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>