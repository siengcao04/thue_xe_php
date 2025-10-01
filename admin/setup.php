<?php
// Script tạo tài khoản admin mặc định
include("../include/common.php");

// Kiểm tra xem đã có admin nào chưa
$sql = "SELECT COUNT(*) as total FROM admin";
$result = db_select($sql);
$total_admin = $result[0]['total'];

if ($total_admin == 0) {
    // Tạo tài khoản admin mặc định
    $username = "admin";
    $password = password_hash("123456", PASSWORD_DEFAULT);
    $ho_ten = "Administrator";
    $email = "admin@xedeep.com";
    $vai_tro = "admin";
    $trang_thai = 1;
    
    $sql = "INSERT INTO admin (username, password, ho_ten, email, vai_tro, trang_thai) VALUES (?, ?, ?, ?, ?, ?)";
    $result = db_execute($sql, [$username, $password, $ho_ten, $email, $vai_tro, $trang_thai]);
    
    if ($result) {
        echo "<div style='background: #d4edda; color: #155724; padding: 1rem; border-radius: 5px; margin: 1rem;'>";
        echo "<h3>✅ Tạo tài khoản admin thành công!</h3>";
        echo "<p><strong>Tên đăng nhập:</strong> admin</p>";
        echo "<p><strong>Mật khẩu:</strong> 123456</p>";
        echo "<p><a href='login.php' style='color: #155724;'>🔗 Đăng nhập ngay</a></p>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 5px; margin: 1rem;'>";
        echo "<h3>❌ Lỗi tạo tài khoản admin!</h3>";
        echo "</div>";
    }
} else {
    echo "<div style='background: #fff3cd; color: #856404; padding: 1rem; border-radius: 5px; margin: 1rem;'>";
    echo "<h3>ℹ️ Đã có {$total_admin} tài khoản admin trong hệ thống</h3>";
    echo "<p><a href='login.php' style='color: #856404;'>🔗 Đến trang đăng nhập</a></p>";
    echo "</div>";
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Admin - XeDeep</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
            padding: 2rem;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 2rem;
        }
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 1rem;
        }
        .btn:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚗 XeDeep - Setup Admin</h1>
        
        <div style="text-align: center; margin-top: 2rem;">
            <a href="../index.php" class="btn">🏠 Về trang chính</a>
            <a href="login.php" class="btn">🔐 Đăng nhập Admin</a>
        </div>
    </div>
</body>
</html>