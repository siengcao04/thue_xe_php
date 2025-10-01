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
    js_redirect_to('admin/loai_xe/index.php');
}

// Kiểm tra xem loại xe có tồn tại không
$sql = "SELECT * FROM loai_xe WHERE id = ?";
$loai_xe = db_select($sql, [$id]);

if (empty($loai_xe)) {
    js_alert('Không tìm thấy loại xe!');
    js_redirect_to('admin/loai_xe/index.php');
}

$loai_xe = $loai_xe[0];

// Kiểm tra xem có xe nào đang sử dụng loại xe này không
$sql = "SELECT COUNT(*) as total FROM xe WHERE loai_xe_id = ?";
$result = db_select($sql, [$id]);
$count_xe = $result[0]['total'];

if ($count_xe > 0) {
    js_alert("Không thể xóa loại xe này vì đang có {$count_xe} xe thuộc loại này!");
    js_redirect_to('admin/loai_xe/index.php');
}

// Xóa loại xe
$sql = "DELETE FROM loai_xe WHERE id = ?";
$result = db_execute($sql, [$id]);

if ($result) {
    js_alert('Xóa loại xe thành công!');
} else {
    js_alert('Có lỗi xảy ra khi xóa loại xe!');
}

js_redirect_to('admin/loai_xe/index.php');
?>