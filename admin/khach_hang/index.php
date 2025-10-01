<?php
session_start();
include("../../include/common.php");

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['admin_id'])) {
    redirect_to("admin/login.php");
}

// Lấy danh sách khách hàng
$sql = "SELECT * FROM khach_hang ORDER BY created_at DESC";
$khach_hang_list = db_select($sql);

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý khách hàng - XeDeep</title>
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
            <li><a href="index.php" class="active">Khách hàng</a></li>
            <li><a href="../don_thue/index.php">Đơn thuê</a></li>
            <li><a href="../admin/index.php">Quản trị viên</a></li>
        </ul>
    </nav>

    <!-- Container -->
    <div class="admin-container">
        <div class="admin-card">
            <div class="admin-card-header d-flex justify-content-between align-items-center">
                <h2>👥 Quản lý khách hàng</h2>
                <a href="create.php" class="btn btn-success">➕ Thêm khách hàng</a>
            </div>
            <div class="admin-card-body">
                <?php if (empty($khach_hang_list)): ?>
                    <div class="text-center">
                        <p style="color: #7f8c8d; margin: 2rem 0;">Chưa có khách hàng nào.</p>
                        <a href="create.php" class="btn btn-primary">Thêm khách hàng đầu tiên</a>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Họ tên</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>SĐT</th>
                                    <th>CMND</th>
                                    <th>Trạng thái</th>
                                    <th>Ngày đăng ký</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($khach_hang_list as $kh): ?>
                                <tr>
                                    <td><?= $kh['id'] ?></td>
                                    <td><strong><?= htmlspecialchars($kh['ho_ten']) ?></strong></td>
                                    <td><?= htmlspecialchars($kh['username']) ?></td>
                                    <td><?= htmlspecialchars($kh['email'] ?? 'Chưa có') ?></td>
                                    <td><?= htmlspecialchars($kh['sdt']) ?></td>
                                    <td><?= htmlspecialchars($kh['so_cmnd'] ?? 'Chưa có') ?></td>
                                    <td>
                                        <?php if ($kh['trang_thai'] == 1): ?>
                                            <span class="status-badge status-active">Hoạt động</span>
                                        <?php else: ?>
                                            <span class="status-badge status-inactive">Bị khóa</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($kh['created_at'])) ?></td>
                                    <td>
                                        <a href="edit.php?id=<?= $kh['id'] ?>" class="btn btn-warning">✏️ Sửa</a>
                                        <?php if ($kh['trang_thai'] == 1): ?>
                                            <a href="delete.php?id=<?= $kh['id'] ?>&action=lock" 
                                               class="btn btn-danger" 
                                               onclick="return confirm('Bạn có chắc chắn muốn khóa tài khoản này?')">
                                               🔒 Khóa
                                            </a>
                                        <?php else: ?>
                                            <a href="delete.php?id=<?= $kh['id'] ?>&action=unlock" 
                                               class="btn btn-success" 
                                               onclick="return confirm('Bạn có chắc chắn muốn mở khóa tài khoản này?')">
                                               🔓 Mở khóa
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