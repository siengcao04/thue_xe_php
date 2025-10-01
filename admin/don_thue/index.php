<?php
session_start();
include("../../include/common.php");

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['admin_id'])) {
    redirect_to("admin/login.php");
}

// Lấy danh sách đơn thuê với thông tin liên kết
$sql = "SELECT dt.*, kh.ho_ten as ten_khach_hang, kh.sdt as sdt_khach_hang, 
               x.ten_xe, x.ma_xe, x.bien_so,
               a.ho_ten as ten_admin_xac_nhan
        FROM don_thue dt 
        LEFT JOIN khach_hang kh ON dt.khach_hang_id = kh.id 
        LEFT JOIN xe x ON dt.xe_id = x.id 
        LEFT JOIN admin a ON dt.admin_xac_nhan = a.id
        ORDER BY dt.created_at DESC";
$don_thue_list = db_select($sql);

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đơn thuê - XeDeep</title>
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
            <li><a href="index.php" class="active">Đơn thuê</a></li>
            <li><a href="../admin/index.php">Quản trị viên</a></li>
        </ul>
    </nav>

    <!-- Container -->
    <div class="admin-container">
        <div class="admin-card">
            <div class="admin-card-header d-flex justify-content-between align-items-center">
                <h2>📋 Quản lý đơn thuê xe</h2>
                <a href="create.php" class="btn btn-success">➕ Tạo đơn thuê</a>
            </div>
            <div class="admin-card-body">
                <?php if (empty($don_thue_list)): ?>
                    <div class="text-center">
                        <p style="color: #7f8c8d; margin: 2rem 0;">Chưa có đơn thuê nào.</p>
                        <a href="create.php" class="btn btn-primary">Tạo đơn thuê đầu tiên</a>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Mã đơn</th>
                                    <th>Khách hàng</th>
                                    <th>Xe</th>
                                    <th>Ngày thuê</th>
                                    <th>Ngày trả</th>
                                    <th>Tổng tiền</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($don_thue_list as $don): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($don['ma_don']) ?></strong></td>
                                    <td>
                                        <?= htmlspecialchars($don['ten_khach_hang']) ?><br>
                                        <small><?= htmlspecialchars($don['sdt_khach_hang']) ?></small>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($don['ten_xe']) ?><br>
                                        <small><?= htmlspecialchars($don['ma_xe']) ?> - <?= htmlspecialchars($don['bien_so'] ?? 'N/A') ?></small>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($don['ngay_thue'])) ?></td>
                                    <td><?= date('d/m/Y', strtotime($don['ngay_tra'])) ?></td>
                                    <td><strong><?= number_format($don['tong_tien']) ?>đ</strong></td>
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
                                    <td>
                                        <a href="edit.php?id=<?= $don['id'] ?>" class="btn btn-warning">✏️ Sửa</a>
                                        
                                        <?php if ($don['trang_thai'] == 'cho_xac_nhan'): ?>
                                            <a href="update_status.php?id=<?= $don['id'] ?>&status=da_xac_nhan" 
                                               class="btn btn-success" 
                                               onclick="return confirm('Xác nhận đơn thuê này?')">
                                               ✅ Duyệt
                                            </a>
                                            <a href="update_status.php?id=<?= $don['id'] ?>&status=huy" 
                                               class="btn btn-danger" 
                                               onclick="return confirm('Hủy đơn thuê này?')">
                                               ❌ Hủy
                                            </a>
                                        <?php elseif ($don['trang_thai'] == 'da_xac_nhan'): ?>
                                            <a href="update_status.php?id=<?= $don['id'] ?>&status=dang_thue" 
                                               class="btn btn-info" 
                                               onclick="return confirm('Khách hàng đã nhận xe?')">
                                               🚗 Đã giao xe
                                            </a>
                                        <?php elseif ($don['trang_thai'] == 'dang_thue'): ?>
                                            <a href="update_status.php?id=<?= $don['id'] ?>&status=da_tra" 
                                               class="btn btn-success" 
                                               onclick="return confirm('Khách hàng đã trả xe?')">
                                               ✅ Đã trả xe
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>