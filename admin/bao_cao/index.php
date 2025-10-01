<?php
session_start();
include("../../include/common.php");

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['admin_id'])) {
    redirect_to("admin/login.php");
}

// Lấy thống kê doanh thu theo tháng
$thang_hien_tai = date('Y-m');
$nam_hien_tai = date('Y');

// Doanh thu theo 12 tháng gần nhất
$sql = "SELECT 
            DATE_FORMAT(ngay_thue, '%Y-%m') as thang_nam,
            SUM(tong_tien) as doanh_thu,
            COUNT(*) as so_don
        FROM don_thue 
        WHERE trang_thai = 'hoan_thanh' 
        AND ngay_thue >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(ngay_thue, '%Y-%m')
        ORDER BY thang_nam ASC";
$doanh_thu_thang = db_select($sql);

// Xe được thuê nhiều nhất
$sql = "SELECT 
            x.ten_xe,
            lx.ten_loai,
            hx.ten_hang,
            COUNT(dt.id) as so_lan_thue,
            SUM(dt.tong_tien) as tong_doanh_thu
        FROM don_thue dt
        JOIN xe x ON dt.xe_id = x.id
        JOIN loai_xe lx ON x.loai_xe_id = lx.id
        JOIN hang_xe hx ON x.hang_xe_id = hx.id
        WHERE dt.trang_thai = 'hoan_thanh'
        GROUP BY dt.xe_id
        ORDER BY so_lan_thue DESC
        LIMIT 10";
$xe_thue_nhieu = db_select($sql);

// Thống kê tổng quan
$sql = "SELECT 
            COUNT(*) as tong_don_thue,
            SUM(CASE WHEN trang_thai = 'hoan_thanh' THEN tong_tien ELSE 0 END) as tong_doanh_thu,
            SUM(CASE WHEN trang_thai = 'hoan_thanh' THEN 1 ELSE 0 END) as don_hoan_thanh,
            SUM(CASE WHEN trang_thai = 'huy' THEN 1 ELSE 0 END) as don_huy
        FROM don_thue
        WHERE YEAR(ngay_thue) = ?";
$thong_ke_tong = db_select($sql, [$nam_hien_tai]);
$thong_ke = $thong_ke_tong[0] ?? [];

// Doanh thu hôm nay
$sql = "SELECT 
            SUM(tong_tien) as doanh_thu_ngay,
            COUNT(*) as don_ngay
        FROM don_thue 
        WHERE DATE(ngay_thue) = CURDATE() 
        AND trang_thai = 'hoan_thanh'";
$doanh_thu_ngay = db_select($sql);
$dt_ngay = $doanh_thu_ngay[0] ?? ['doanh_thu_ngay' => 0, 'don_ngay' => 0];

// Tỷ lệ trạng thái đơn thuê
$sql = "SELECT 
            trang_thai,
            COUNT(*) as so_luong,
            ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM don_thue WHERE YEAR(ngay_thue) = ?)), 2) as ty_le
        FROM don_thue 
        WHERE YEAR(ngay_thue) = ?
        GROUP BY trang_thai";
$ty_le_trang_thai = db_select($sql, [$nam_hien_tai, $nam_hien_tai]);

// Chuẩn bị dữ liệu cho chart
$chart_labels = [];
$chart_data = [];
$chart_colors = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40'];

foreach ($ty_le_trang_thai as $index => $item) {
    $chart_labels[] = ucfirst(str_replace('_', ' ', $item['trang_thai']));
    $chart_data[] = $item['so_luong'];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo cáo thống kê - XeDeep</title>
    <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .stat-card.success {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        .stat-card.warning {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }
        
        .stat-card.danger {
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 1rem;
            opacity: 0.9;
        }
        
        .chart-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .chart-title {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 1rem;
            color: #333;
        }
        
        .table-responsive {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .revenue-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .revenue-table th,
        .revenue-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .revenue-table th {
            background: #f8f9fa;
            font-weight: bold;
            color: #333;
        }
        
        .revenue-table tr:hover {
            background: #f8f9fa;
        }
        
        .number-format {
            text-align: right;
            font-weight: bold;
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
            <li><a href="index.php" class="active">📊 Báo cáo</a></li>
        </ul>
    </nav>

    <!-- Container -->
    <div class="admin-container">
        <div class="admin-card">
            <div class="admin-card-header">
                <h2>📊 Báo cáo thống kê năm <?= $nam_hien_tai ?></h2>
            </div>
            <div class="admin-card-body">
                <!-- Thống kê tổng quan -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?= number_format($thong_ke['tong_doanh_thu'] ?? 0) ?></div>
                        <div class="stat-label">Tổng doanh thu (VNĐ)</div>
                    </div>
                    
                    <div class="stat-card success">
                        <div class="stat-number"><?= number_format($thong_ke['don_hoan_thanh'] ?? 0) ?></div>
                        <div class="stat-label">Đơn hoàn thành</div>
                    </div>
                    
                    <div class="stat-card warning">
                        <div class="stat-number"><?= number_format($dt_ngay['doanh_thu_ngay'] ?? 0) ?></div>
                        <div class="stat-label">Doanh thu hôm nay (VNĐ)</div>
                    </div>
                    
                    <div class="stat-card danger">
                        <div class="stat-number"><?= number_format($thong_ke['don_huy'] ?? 0) ?></div>
                        <div class="stat-label">Đơn bị hủy</div>
                    </div>
                </div>

                <!-- Biểu đồ tròn trạng thái đơn thuê -->
                <div class="chart-container">
                    <div class="chart-title">Tỷ lệ trạng thái đơn thuê năm <?= $nam_hien_tai ?></div>
                    <div style="width: 400px; margin: 0 auto;">
                        <canvas id="statusChart" width="400" height="400"></canvas>
                    </div>
                </div>

                <!-- Biểu đồ doanh thu theo tháng -->
                <div class="chart-container">
                    <div class="chart-title">Doanh thu 12 tháng gần nhất</div>
                    <canvas id="revenueChart" width="800" height="400"></canvas>
                </div>

                <!-- Bảng xe được thuê nhiều nhất -->
                <div class="table-responsive">
                    <h3 style="padding: 1.5rem 1.5rem 0; margin: 0; color: #333;">🏆 Top 10 xe được thuê nhiều nhất</h3>
                    <table class="revenue-table">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Tên xe</th>
                                <th>Loại xe</th>
                                <th>Hãng xe</th>
                                <th class="number-format">Số lần thuê</th>
                                <th class="number-format">Tổng doanh thu (VNĐ)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($xe_thue_nhieu)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 2rem; color: #666;">
                                        Chưa có dữ liệu thuê xe
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($xe_thue_nhieu as $index => $xe): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars($xe['ten_xe']) ?></td>
                                        <td><?= htmlspecialchars($xe['ten_loai']) ?></td>
                                        <td><?= htmlspecialchars($xe['ten_hang']) ?></td>
                                        <td class="number-format"><?= number_format($xe['so_lan_thue']) ?></td>
                                        <td class="number-format"><?= number_format($xe['tong_doanh_thu']) ?></td>
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
        // Biểu đồ tròn trạng thái đơn thuê
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($chart_labels) ?>,
                datasets: [{
                    data: <?= json_encode($chart_data) ?>,
                    backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            font: {
                                size: 14
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed * 100) / total).toFixed(1);
                                return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });

        // Biểu đồ cột doanh thu theo tháng
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        const revenueLabels = <?= json_encode(array_column($doanh_thu_thang, 'thang_nam')) ?>;
        const revenueData = <?= json_encode(array_column($doanh_thu_thang, 'doanh_thu')) ?>;

        const revenueChart = new Chart(revenueCtx, {
            type: 'bar',
            data: {
                labels: revenueLabels,
                datasets: [{
                    label: 'Doanh thu (VNĐ)',
                    data: revenueData,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 2,
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return new Intl.NumberFormat('vi-VN').format(value);
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Doanh thu: ' + new Intl.NumberFormat('vi-VN').format(context.parsed.y) + ' VNĐ';
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>