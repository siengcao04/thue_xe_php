<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ thống thuê xe XeDeep</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
            width: 90%;
        }
        
        .logo {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        h1 {
            color: #333;
            margin-bottom: 1rem;
            font-size: 2.5rem;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 3rem;
            font-size: 1.2rem;
        }
        
        .btn-group {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 10px;
            font-size: 1.2rem;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            font-weight: bold;
        }
        
        .btn-admin {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }
        
        .btn-customer {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        .features {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #eee;
        }
        
        .feature-list {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .feature {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
        }
        
        @media (max-width: 600px) {
            .feature-list {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">🚗</div>
        <h1>XeDeep</h1>
        <p class="subtitle">Hệ thống quản lý thuê xe chuyên nghiệp</p>
        
        <div class="btn-group">
            <a href="admin/login.php" class="btn btn-admin">
                🔐 Đăng nhập quản trị
            </a>
            
            <a href="customer/" class="btn btn-customer">
                👤 Giao diện khách hàng
            </a>
        </div>
        
        <div class="features">
            <h3 style="color: #333; margin-bottom: 1rem;">✨ Tính năng nổi bật</h3>
            <div class="feature-list">
                <div class="feature">
                    <strong>📊 Quản lý xe</strong><br>
                    Theo dõi toàn bộ đội xe
                </div>
                <div class="feature">
                    <strong>🎯 Đặt xe online</strong><br>
                    Đặt xe dễ dàng, nhanh chóng
                </div>
                <div class="feature">
                    <strong>💳 Thanh toán</strong><br>
                    Nhiều phương thức thanh toán
                </div>
                <div class="feature">
                    <strong>📈 Báo cáo</strong><br>
                    Thống kê doanh thu chi tiết
                </div>
            </div>
        </div>
        
        <div style="margin-top: 2rem; font-size: 0.9rem; color: #999;">
            © 2025 XeDeep - Hệ thống quản lý thuê xe
        </div>
    </div>
</body>
</html>