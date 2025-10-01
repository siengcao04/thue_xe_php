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

// Kiểm tra xem đánh giá có tồn tại không
$sql = "SELECT 
            dg.*,
            kh.ho_ten as ten_khach_hang,
            dt.ma_don_thue
        FROM danh_gia dg
        JOIN khach_hang kh ON dg.khach_hang_id = kh.id
        JOIN don_thue dt ON dg.don_thue_id = dt.id
        WHERE dg.id = ?";
$danh_gia = db_select($sql, [$id]);

if (empty($danh_gia)) {
    js_alert('Không tìm thấy đánh giá!');
    js_redirect_to('admin/danh_gia/index.php');
}

$dg = $danh_gia[0];

// Xóa đánh giá
$sql = "DELETE FROM danh_gia WHERE id = ?";
$result = db_execute($sql, [$id]);

if ($result) {
    js_alert("Xóa đánh giá từ khách hàng '{$dg['ten_khach_hang']}' (Đơn {$dg['ma_don_thue']}) thành công!");
} else {
    js_alert('Có lỗi xảy ra khi xóa đánh giá!');
}

js_redirect_to('admin/danh_gia/index.php');
?>