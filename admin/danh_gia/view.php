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
    js_redirect_to('admin/danh_gia/index.php');
}

// Lấy thông tin đánh giá chi tiết
$sql = "SELECT 
            dg.*,
            kh.ho_ten as ten_khach_hang,
            kh.email as email_khach_hang,
            kh.sdt as sdt_khach_hang,
            x.ten_xe,
            x.bien_so,
            lx.ten_loai,
            hx.ten_hang,
            dt.ma_don_thue,
            dt.ngay_thue,
            dt.ngay_tra,
            dt.tong_tien
        FROM danh_gia dg
        JOIN khach_hang kh ON dg.khach_hang_id = kh.id
        JOIN don_thue dt ON dg.don_thue_id = dt.id
        JOIN xe x ON dt.xe_id = x.id
        JOIN loai_xe lx ON x.loai_xe_id = lx.id
        JOIN hang_xe hx ON x.hang_xe_id = hx.id
        WHERE dg.id = ?";
$danh_gia = db_select($sql, [$id]);

if (empty($danh_gia)) {
    js_alert('Không tìm thấy đánh giá!');
    js_redirect_to('admin/danh_gia/index.php');
}

$dg = $danh_gia[0];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết đánh giá - XeDeep</title>
    <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
    <style>
        .rating-display {
            text-align: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .rating-stars {
            font-size: 3rem;
            margin: 1rem 0;
        }
        .rating-score {
            font-size: 2rem;
            font-weight: bold;
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
        .comment-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .comment-text {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            border-left: 4px solid #28a745;
            font-style: italic;
            line-height: 1.6;
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
                <h2>⭐ Chi tiết đánh giá</h2>
            </div>
            <div class="admin-card-body">
                <!-- Hiển thị điểm đánh giá -->
                <div class="rating-display">
                    <h3>Điểm đánh giá từ khách hàng</h3>
                    <div class="rating-stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <?= $i <= $dg['diem_danh_gia'] ? '⭐' : '☆' ?>
                        <?php endfor; ?>
                    </div>
                    <div class="rating-score"><?= $dg['diem_danh_gia'] ?>/5 điểm</div>
                </div>

                <!-- Thông tin chi tiết -->
                <div class="detail-grid">
                    <!-- Thông tin khách hàng -->
                    <div class="detail-card">
                        <h4 style="margin-top: 0; color: #007bff;">👤 Thông tin khách hàng</h4>
                        <div class="detail-label">Họ tên:</div>
                        <div class="detail-value"><?= htmlspecialchars($dg['ten_khach_hang']) ?></div>
                        
                        <div class="detail-label" style="margin-top: 1rem;">Email:</div>
                        <div class="detail-value"><?= htmlspecialchars($dg['email_khach_hang']) ?></div>
                        
                        <?php if ($dg['sdt_khach_hang']): ?>
                            <div class="detail-label" style="margin-top: 1rem;">Số điện thoại:</div>
                            <div class="detail-value"><?= htmlspecialchars($dg['sdt_khach_hang']) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Thông tin đơn thuê -->
                    <div class="detail-card">
                        <h4 style="margin-top: 0; color: #007bff;">📋 Thông tin đơn thuê</h4>
                        <div class="detail-label">Mã đơn thuê:</div>
                        <div class="detail-value"><?= htmlspecialchars($dg['ma_don_thue']) ?></div>
                        
                        <div class="detail-label" style="margin-top: 1rem;">Ngày thuê:</div>
                        <div class="detail-value"><?= date('d/m/Y', strtotime($dg['ngay_thue'])) ?></div>
                        
                        <div class="detail-label" style="margin-top: 1rem;">Ngày trả:</div>
                        <div class="detail-value"><?= date('d/m/Y', strtotime($dg['ngay_tra'])) ?></div>
                        
                        <div class="detail-label" style="margin-top: 1rem;">Tổng tiền:</div>
                        <div class="detail-value"><?= number_format($dg['tong_tien']) ?> VNĐ</div>
                    </div>
                </div>

                <!-- Thông tin xe -->
                <div class="detail-card" style="margin-bottom: 2rem;">
                    <h4 style="margin-top: 0; color: #007bff;">🚗 Thông tin xe được thuê</h4>
                    <div class="detail-grid">
                        <div>
                            <div class="detail-label">Tên xe:</div>
                            <div class="detail-value"><?= htmlspecialchars($dg['ten_xe']) ?></div>
                            
                            <div class="detail-label" style="margin-top: 1rem;">Biển số:</div>
                            <div class="detail-value"><?= htmlspecialchars($dg['bien_so']) ?></div>
                        </div>
                        <div>
                            <div class="detail-label">Loại xe:</div>
                            <div class="detail-value"><?= htmlspecialchars($dg['ten_loai']) ?></div>
                            
                            <div class="detail-label" style="margin-top: 1rem;">Hãng xe:</div>
                            <div class="detail-value"><?= htmlspecialchars($dg['ten_hang']) ?></div>
                        </div>
                    </div>
                </div>

                <!-- Bình luận -->
                <?php if ($dg['noi_dung']): ?>
                    <div class="comment-section">
                        <h4 style="margin-top: 0; color: #28a745;">💬 Bình luận từ khách hàng</h4>
                        <div class="comment-text">
                            "<?= nl2br(htmlspecialchars($dg['noi_dung'])) ?>"
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Thông tin meta -->
                <div style="background: #e9ecef; padding: 1rem; border-radius: 8px; margin-bottom: 2rem;">
                    <strong>Ngày đánh giá:</strong> <?= date('d/m/Y H:i:s', strtotime($dg['ngay_danh_gia'])) ?>
                </div>

                <!-- Nút thao tác -->
                <div class="form-group">
                    <a href="index.php" class="btn btn-info">📋 Quay lại danh sách</a>
                    <a href="delete.php?id=<?= $dg['id'] ?>" 
                       class="btn btn-danger"
                       onclick="return confirm('Bạn có chắc muốn xóa đánh giá này?')">
                        🗑️ Xóa đánh giá
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>