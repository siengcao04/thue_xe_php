<?php
session_start();
include("../../include/common.php");

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['admin_id'])) {
    redirect_to("admin/login.php");
}

// Chỉ admin mới được quản lý tài khoản admin khác
if (($_SESSION['admin_vai_tro'] ?? '') !== 'admin') {
    js_alert('Bạn không có quyền truy cập chức năng này!');
    js_redirect_to('admin/index.php');
}

// Lấy danh sách admin
$sql = "SELECT * FROM admin ORDER BY created_at DESC";
$admin_list = db_select($sql);

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Admin - XeDeep</title>
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
            <li><a href="index.php" class="active">Quản trị viên</a></li>
        </ul>
    </nav>

    <!-- Container -->
    <div class="admin-container">
        <div class="admin-card">
            <div class="admin-card-header d-flex justify-content-between align-items-center">
                <h2>👨‍💼 Quản lý tài khoản Admin</h2>
                <a href="create.php" class="btn btn-success">➕ Thêm Admin</a>
            </div>
            <div class="admin-card-body">
                <?php if (empty($admin_list)): ?>
                    <div class="text-center">
                        <p style="color: #7f8c8d; margin: 2rem 0;">Chưa có tài khoản admin nào.</p>
                        <a href="create.php" class="btn btn-primary">Tạo admin đầu tiên</a>
                    </div>
                <?php else: ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Họ tên</th>
                                <th>Email</th>
                                <th>SĐT</th>
                                <th>Vai trò</th>
                                <th>Trạng thái</th>
                                <th>Ngày tạo</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($admin_list as $admin): ?>
                            <tr>
                                <td><?= $admin['id'] ?></td>
                                <td><strong><?= htmlspecialchars($admin['username']) ?></strong></td>
                                <td><?= htmlspecialchars($admin['ho_ten']) ?></td>
                                <td><?= htmlspecialchars($admin['email'] ?? 'Chưa có') ?></td>
                                <td><?= htmlspecialchars($admin['sdt'] ?? 'Chưa có') ?></td>
                                <td>
                                    <?php if ($admin['vai_tro'] == 'admin'): ?>
                                        <span class="status-badge status-active">Admin</span>
                                    <?php else: ?>
                                        <span class="status-badge status-pending">Nhân viên</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($admin['trang_thai'] == 1): ?>
                                        <span class="status-badge status-active">Hoạt động</span>
                                    <?php else: ?>
                                        <span class="status-badge status-inactive">Bị khóa</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d/m/Y', strtotime($admin['created_at'])) ?></td>
                                <td>
                                    <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                                        <a href="edit.php?id=<?= $admin['id'] ?>" class="btn btn-warning">✏️ Sửa</a>
                                        <?php if ($admin['trang_thai'] == 1): ?>
                                            <a href="delete.php?id=<?= $admin['id'] ?>&action=lock" 
                                               class="btn btn-danger" 
                                               onclick="return confirm('Bạn có chắc chắn muốn khóa tài khoản này?')">
                                               🔒 Khóa
                                            </a>
                                        <?php else: ?>
                                            <a href="delete.php?id=<?= $admin['id'] ?>&action=unlock" 
                                               class="btn btn-success" 
                                               onclick="return confirm('Bạn có chắc chắn muốn mở khóa tài khoản này?')">
                                               🔓 Mở khóa
                                            </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="status-badge status-active">Tài khoản hiện tại</span>
                                    <?php endif; ?>
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