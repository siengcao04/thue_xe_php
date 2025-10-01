<?php
session_start();
include("../../include/common.php");

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['admin_id'])) {
    redirect_to("admin/login.php");
}

$id = (int)($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';

if ($id <= 0) {
    js_alert('ID không hợp lệ!');
    js_redirect_to('admin/khach_hang/index.php');
}

// Kiểm tra xem khách hàng có tồn tại không
$sql = "SELECT * FROM khach_hang WHERE id = ?";
$khach_hang = db_select($sql, [$id]);

if (empty($khach_hang)) {
    js_alert('Không tìm thấy khách hàng!');
    js_redirect_to('admin/khach_hang/index.php');
}

$khach_hang = $khach_hang[0];

if ($action === 'lock') {
    // Khóa tài khoản
    $sql = "UPDATE khach_hang SET trang_thai = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
    $result = db_execute($sql, [$id]);
    
    if ($result) {
        js_alert('Khóa tài khoản khách hàng thành công!');
    } else {
        js_alert('Có lỗi xảy ra khi khóa tài khoản!');
    }
} elseif ($action === 'unlock') {
    // Mở khóa tài khoản
    $sql = "UPDATE khach_hang SET trang_thai = 1, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
    $result = db_execute($sql, [$id]);
    
    if ($result) {
        js_alert('Mở khóa tài khoản khách hàng thành công!');
    } else {
        js_alert('Có lỗi xảy ra khi mở khóa tài khoản!');
    }
} else {
    js_alert('Thao tác không hợp lệ!');
}

js_redirect_to('admin/khach_hang/index.php');
?>