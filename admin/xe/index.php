<?php
session_start();
include("../../include/common.php");

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['admin_id'])) {
    redirect_to("admin/login.php");
}

// Lấy danh sách xe với thông tin liên kết
$sql = "SELECT x.*, lx.ten_loai, hx.ten_hang 
        FROM xe x 
        LEFT JOIN loai_xe lx ON x.loai_xe_id = lx.id 
        LEFT JOIN hang_xe hx ON x.hang_xe_id = hx.id 
        ORDER BY x.created_at DESC";
$xe_list = db_select($sql);

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý xe - XeDeep</title>
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
            <div class="admin-card-header d-flex justify-content-between align-items-center">
                <h2>🚙 Quản lý xe</h2>
                <a href="create.php" class="btn btn-success">➕ Thêm xe mới</a>
            </div>
            <div class="admin-card-body">
                <?php if (empty($xe_list)): ?>
                    <div class="text-center">
                        <p style="color: #7f8c8d; margin: 2rem 0;">Chưa có xe nào được tạo.</p>
                        <a href="create.php" class="btn btn-primary">Thêm xe đầu tiên</a>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Ảnh</th>
                                    <th>Mã xe</th>
                                    <th>Tên xe</th>
                                    <th>Loại</th>
                                    <th>Hãng</th>
                                    <th>Biển số</th>
                                    <th>Giá thuê/ngày</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($xe_list as $xe): ?>
                                <tr>
                                    <td>
                                        <?php if ($xe['hinh_anh']): ?>
                                            <img src="<?= upload($xe['hinh_anh']) ?>" 
                                                 alt="<?= htmlspecialchars($xe['ten_xe']) ?>"
                                                 style="width: 60px; height: 40px; object-fit: cover; border-radius: 4px;">
                                        <?php else: ?>
                                            <div style="width: 60px; height: 40px; background: #f8f9fa; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #6c757d; font-size: 12px;">
                                                No Image
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?= htmlspecialchars($xe['ma_xe']) ?></strong></td>
                                    <td><?= htmlspecialchars($xe['ten_xe']) ?></td>
                                    <td><?= htmlspecialchars($xe['ten_loai'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($xe['ten_hang'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($xe['bien_so'] ?? 'Chưa có') ?></td>
                                    <td><?= number_format($xe['gia_thue_ngay']) ?>đ</td>
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
                                    <td>
                                        <a href="edit.php?id=<?= $xe['id'] ?>" class="btn btn-warning">✏️ Sửa</a>
                                        <a href="delete.php?id=<?= $xe['id'] ?>" 
                                           class="btn btn-danger" 
                                           onclick="return confirm('Bạn có chắc chắn muốn xóa xe này? Thao tác này không thể hoàn tác!')">
                                           🗑️ Xóa
                                        </a>
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