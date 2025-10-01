<?php
session_start();
include("../../include/common.php");

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['admin_id'])) {
    redirect_to("admin/login.php");
}

// Lấy danh sách hãng xe
$sql = "SELECT * FROM hang_xe ORDER BY created_at DESC";
$hang_xe_list = db_select($sql);

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý hãng xe - XeDeep</title>
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
            <li><a href="index.php" class="active">Hãng xe</a></li>
            <li><a href="../xe/index.php">Quản lý xe</a></li>
            <li><a href="../khach_hang/index.php">Khách hàng</a></li>
            <li><a href="../don_thue/index.php">Đơn thuê</a></li>
            <li><a href="../admin/index.php">Quản trị viên</a></li>
        </ul>
    </nav>

    <!-- Container -->
    <div class="admin-container">
        <div class="admin-card">
            <div class="admin-card-header d-flex justify-content-between align-items-center">
                <h2>🏭 Quản lý hãng xe</h2>
                <a href="create.php" class="btn btn-success">➕ Thêm hãng xe</a>
            </div>
            <div class="admin-card-body">
                <?php if (empty($hang_xe_list)): ?>
                    <div class="text-center">
                        <p style="color: #7f8c8d; margin: 2rem 0;">Chưa có hãng xe nào được tạo.</p>
                        <a href="create.php" class="btn btn-primary">Tạo hãng xe đầu tiên</a>
                    </div>
                <?php else: ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tên hãng xe</th>
                                <th>Mô tả</th>
                                <th>Trạng thái</th>
                                <th>Ngày tạo</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($hang_xe_list as $hang_xe): ?>
                            <tr>
                                <td><?= $hang_xe['id'] ?></td>
                                <td><strong><?= htmlspecialchars($hang_xe['ten_hang']) ?></strong></td>
                                <td><?= htmlspecialchars($hang_xe['mo_ta'] ?? 'Không có mô tả') ?></td>
                                <td>
                                    <?php if ($hang_xe['trang_thai'] == 1): ?>
                                        <span class="status-badge status-active">Hoạt động</span>
                                    <?php else: ?>
                                        <span class="status-badge status-inactive">Không hoạt động</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($hang_xe['created_at'])) ?></td>
                                <td>
                                    <a href="edit.php?id=<?= $hang_xe['id'] ?>" class="btn btn-warning">✏️ Sửa</a>
                                    <a href="delete.php?id=<?= $hang_xe['id'] ?>" 
                                       class="btn btn-danger" 
                                       onclick="return confirm('Bạn có chắc chắn muốn xóa hãng xe này? Thao tác này không thể hoàn tác!')">
                                       🗑️ Xóa
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>