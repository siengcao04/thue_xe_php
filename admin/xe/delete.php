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
    js_redirect_to('admin/xe/index.php');
}

// Kiểm tra xem xe có tồn tại không
$sql = "SELECT * FROM xe WHERE id = ?";
$xe = db_select($sql, [$id]);

if (empty($xe)) {
    js_alert('Không tìm thấy xe!');
    js_redirect_to('admin/xe/index.php');
}

$xe = $xe[0];

// Kiểm tra xem có đơn thuê nào đang sử dụng xe này không
$sql = "SELECT COUNT(*) as total FROM don_thue WHERE xe_id = ? AND trang_thai IN ('cho_xac_nhan', 'da_xac_nhan', 'dang_thue')";
$result = db_select($sql, [$id]);
$count_don_thue = $result[0]['total'];

if ($count_don_thue > 0) {
    js_alert("Không thể xóa xe này vì đang có {$count_don_thue} đơn thuê liên quan!");
    js_redirect_to('admin/xe/index.php');
}

// Xóa ảnh nếu có
if ($xe['hinh_anh']) {
    remove_file($xe['hinh_anh']);
}

// Xóa các ảnh phụ trong bảng hinh_anh_xe
$sql = "SELECT duong_dan FROM hinh_anh_xe WHERE xe_id = ?";
$hinh_anh_phu = db_select($sql, [$id]);
foreach ($hinh_anh_phu as $anh) {
    remove_file($anh['duong_dan']);
}
db_execute("DELETE FROM hinh_anh_xe WHERE xe_id = ?", [$id]);

// Xóa xe
$sql = "DELETE FROM xe WHERE id = ?";
$result = db_execute($sql, [$id]);

if ($result) {
    js_alert('Xóa xe thành công!');
} else {
    js_alert('Có lỗi xảy ra khi xóa xe!');
}

js_redirect_to('admin/xe/index.php');
?>