<?php
session_start();
include("../../include/common.php");

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['admin_id'])) {
    redirect_to("admin/login.php");
}

// Lấy danh sách đánh giá với thông tin liên quan
$sql = "SELECT 
            dg.*,
            kh.ho_ten as ten_khach_hang,
            kh.email as email_khach_hang,
            x.ten_xe,
            dt.ma_don_thue
        FROM danh_gia dg
        JOIN khach_hang kh ON dg.khach_hang_id = kh.id
        JOIN don_thue dt ON dg.don_thue_id = dt.id
        JOIN xe x ON dt.xe_id = x.id
        ORDER BY dg.ngay_danh_gia DESC";
$danh_gia_list = db_select($sql);

// Thống kê đánh giá
$sql = "SELECT 
            AVG(diem_danh_gia) as diem_trung_binh,
            COUNT(*) as tong_danh_gia,
            SUM(CASE WHEN diem_danh_gia >= 4 THEN 1 ELSE 0 END) as danh_gia_tot,
            SUM(CASE WHEN diem_danh_gia <= 2 THEN 1 ELSE 0 END) as danh_gia_kem
        FROM danh_gia";
$thong_ke = db_select($sql);
$stats = $thong_ke[0] ?? [];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đánh giá - XeDeep</title>
    <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
    <style>
        .rating-stars {
            color: #ffc107;
            font-size: 1.2em;
        }
        .rating-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
        .stat-box.good {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        .stat-box.bad {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .comment-cell {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .full-comment {
            white-space: normal;
            word-wrap: break-word;
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
            <li><a href="index.php" class="active">⭐ Đánh giá</a></li>
        </ul>
    </nav>

    <!-- Container -->
    <div class="admin-container">
        <div class="admin-card">
            <div class="admin-card-header">
                <h2>⭐ Quản lý đánh giá khách hàng</h2>
            </div>
            <div class="admin-card-body">
                <!-- Thống kê đánh giá -->
                <div class="rating-stats">
                    <div class="stat-box">
                        <div class="stat-number"><?= number_format($stats['diem_trung_binh'] ?? 0, 1) ?>/5</div>
                        <div>Điểm trung bình</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number"><?= number_format($stats['tong_danh_gia'] ?? 0) ?></div>
                        <div>Tổng đánh giá</div>
                    </div>
                    <div class="stat-box good">
                        <div class="stat-number"><?= number_format($stats['danh_gia_tot'] ?? 0) ?></div>
                        <div>Đánh giá tốt (≥4⭐)</div>
                    </div>
                    <div class="stat-box bad">
                        <div class="stat-number"><?= number_format($stats['danh_gia_kem'] ?? 0) ?></div>
                        <div>Đánh giá kém (≤2⭐)</div>
                    </div>
                </div>

                <!-- Danh sách đánh giá -->
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Khách hàng</th>
                                <th>Mã đơn thuê</th>
                                <th>Xe</th>
                                <th>Điểm</th>
                                <th>Bình luận</th>
                                <th>Ngày đánh giá</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($danh_gia_list)): ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 2rem;">Chưa có đánh giá nào</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($danh_gia_list as $index => $dg): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($dg['ten_khach_hang']) ?></strong><br>
                                            <small style="color: #666;"><?= htmlspecialchars($dg['email_khach_hang']) ?></small>
                                        </td>
                                        <td>
                                            <span style="background: #e3f2fd; color: #1976d2; padding: 3px 8px; border-radius: 4px; font-size: 0.9em;">
                                                <?= htmlspecialchars($dg['ma_don_thue']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($dg['ten_xe']) ?></td>
                                        <td>
                                            <span class="rating-stars">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <?= $i <= $dg['diem_danh_gia'] ? '⭐' : '☆' ?>
                                                <?php endfor; ?>
                                            </span>
                                            <br>
                                            <small>(<?= $dg['diem_danh_gia'] ?>/5)</small>
                                        </td>
                                        <td>
                                            <?php if ($dg['noi_dung']): ?>
                                                <div class="comment-cell" title="<?= htmlspecialchars($dg['noi_dung']) ?>">
                                                    <?= htmlspecialchars($dg['noi_dung']) ?>
                                                </div>
                                            <?php else: ?>
                                                <em style="color: #999;">Không có bình luận</em>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($dg['ngay_danh_gia'])) ?></td>
                                        <td>
                                            <a href="view.php?id=<?= $dg['id'] ?>" 
                                               class="btn btn-info btn-sm" 
                                               title="Xem chi tiết">
                                                👁️ Xem
                                            </a>
                                            
                                            <a href="delete.php?id=<?= $dg['id'] ?>" 
                                               class="btn btn-danger btn-sm" 
                                               title="Xóa đánh giá"
                                               onclick="return confirm('Bạn có chắc muốn xóa đánh giá này?')">
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

    <script>
        // Toggle full comment on click
        document.querySelectorAll('.comment-cell').forEach(cell => {
            cell.addEventListener('click', function() {
                this.classList.toggle('full-comment');
            });
        });
    </script>
</body>
</html>