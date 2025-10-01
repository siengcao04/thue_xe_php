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

$id = (int)($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';

if ($id <= 0) {
    js_alert('ID không hợp lệ!');
    js_redirect_to('admin/admin/index.php');
}

// Không được thao tác với chính mình
if ($id == $_SESSION['admin_id']) {
    js_alert('Không thể thao tác với tài khoản của chính mình!');
    js_redirect_to('admin/admin/index.php');
}

// Kiểm tra xem admin có tồn tại không
$sql = "SELECT * FROM admin WHERE id = ?";
$admin = db_select($sql, [$id]);

if (empty($admin)) {
    js_alert('Không tìm thấy admin!');
    js_redirect_to('admin/admin/index.php');
}

$admin = $admin[0];

if ($action === 'lock') {
    // Khóa tài khoản
    $sql = "UPDATE admin SET trang_thai = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
    $result = db_execute($sql, [$id]);
    
    if ($result) {
        js_alert('Khóa tài khoản admin thành công!');
    } else {
        js_alert('Có lỗi xảy ra khi khóa tài khoản!');
    }
} elseif ($action === 'unlock') {
    // Mở khóa tài khoản
    $sql = "UPDATE admin SET trang_thai = 1, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
    $result = db_execute($sql, [$id]);
    
    if ($result) {
        js_alert('Mở khóa tài khoản admin thành công!');
    } else {
        js_alert('Có lỗi xảy ra khi mở khóa tài khoản!');
    }
} elseif ($action === 'delete') {
    // Xóa hoàn toàn (chỉ admin cấp cao mới được)
    // Kiểm tra xem admin này có xác nhận đơn thuê nào không
    $sql = "SELECT COUNT(*) as total FROM don_thue WHERE admin_xac_nhan = ?";
    $result = db_select($sql, [$id]);
    $count_don_thue = $result[0]['total'];
    
    if ($count_don_thue > 0) {
        js_alert("Không thể xóa admin này vì đang có {$count_don_thue} đơn thuê liên quan!");
    } else {
        // Xóa avatar nếu có
        if ($admin['avatar']) {
            remove_file($admin['avatar']);
        }
        
        // Xóa admin
        $sql = "DELETE FROM admin WHERE id = ?";
        $result = db_execute($sql, [$id]);
        
        if ($result) {
            js_alert('Xóa admin thành công!');
        } else {
            js_alert('Có lỗi xảy ra khi xóa admin!');
        }
    }
} else {
    js_alert('Thao tác không hợp lệ!');
}

js_redirect_to('admin/admin/index.php');
?>