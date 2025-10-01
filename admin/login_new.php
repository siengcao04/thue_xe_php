<?php
session_start();
include("../include/common.php");

// Nếu đã đăng nhập rồi thì chuyển về dashboard
if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';

// Xử lý form đăng nhập
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username)) {
        $error = 'Vui lòng nhập tên đăng nhập!';
    } elseif (empty($password)) {
        $error = 'Vui lòng nhập mật khẩu!';
    } else {
        // Kiểm tra thông tin đăng nhập
        $sql = "SELECT * FROM admin WHERE username = ? AND trang_thai = 1";
        $admin = db_select($sql, [$username]);
        
        if (empty($admin)) {
            $error = 'Tên đăng nhập không tồn tại hoặc tài khoản bị khóa!';
        } else {
            $admin = $admin[0];
            
            // kiểm tra mật khẩu bằng bcrypt
            if (password_verify($password, $admin['password'])) {
                // Đăng nhập thành công - lưu thông tin session
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_ho_ten'] = $admin['ho_ten'];
                $_SESSION['admin_vai_tro'] = $admin['vai_tro'];
                
                header('Location: index.php');
                exit();
            } else {
                $error = 'Mật khẩu không đúng!';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập quản trị - XeDeep</title>
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
            padding: 1rem;
        }
        
        .login-container {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        
        .logo {
            text-align: center;
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 0.5rem;
            font-size: 2rem;
        }
        
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 2rem;
            font-size: 1rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }
        
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .back-link {
            text-align: center;
            margin-top: 2rem;
        }
        
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
        
        .demo-info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
        
        .demo-info strong {
            display: block;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">🚗</div>
        <h1>XeDeep Admin</h1>
        <p class="subtitle">Đăng nhập để quản trị hệ thống</p>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <div class="demo-info">
            <strong>Tài khoản demo:</strong>
            Username: admin<br>
            Password: 123456
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Tên đăng nhập</label>
                <input type="text" 
                       id="username" 
                       name="username" 
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                       placeholder="Nhập tên đăng nhập"
                       required>
            </div>
            
            <div class="form-group">
                <label for="password">Mật khẩu</label>
                <input type="password" 
                       id="password" 
                       name="password" 
                       placeholder="Nhập mật khẩu"
                       required>
            </div>
            
            <button type="submit" class="btn">🔐 Đăng nhập</button>
        </form>
        
        <div class="back-link">
            <a href="../index.php">← Về trang chính</a>
        </div>
    </div>
</body>
</html>