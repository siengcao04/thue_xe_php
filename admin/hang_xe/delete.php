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
    js_redirect_to('admin/hang_xe/index.php');
}

// Kiểm tra xem hãng xe có tồn tại không
$sql = "SELECT * FROM hang_xe WHERE id = ?";
$hang_xe = db_select($sql, [$id]);

if (empty($hang_xe)) {
    js_alert('Không tìm thấy hãng xe!');
    js_redirect_to('admin/hang_xe/index.php');
}

$hang_xe = $hang_xe[0];

// Kiểm tra xem có xe nào đang sử dụng hãng xe này không
$sql = "SELECT COUNT(*) as total FROM xe WHERE hang_xe_id = ?";
$result = db_select($sql, [$id]);
$count_xe = $result[0]['total'];

if ($count_xe > 0) {
    js_alert("Không thể xóa hãng xe này vì đang có {$count_xe} xe thuộc hãng này!");
    js_redirect_to('admin/hang_xe/index.php');
}

// Xóa hãng xe
$sql = "DELETE FROM hang_xe WHERE id = ?";
$result = db_execute($sql, [$id]);

if ($result) {
    js_alert('Xóa hãng xe thành công!');
} else {
    js_alert('Có lỗi xảy ra khi xóa hãng xe!');
}

js_redirect_to('admin/hang_xe/index.php');
?>