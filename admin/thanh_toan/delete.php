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
    js_redirect_to('admin/thanh_toan/index.php');
}

// Kiểm tra xem giao dịch có tồn tại không
$sql = "SELECT 
            tt.*,
            dt.ma_don_thue,
            kh.ho_ten as ten_khach_hang
        FROM thanh_toan tt
        JOIN don_thue dt ON tt.don_thue_id = dt.id
        JOIN khach_hang kh ON dt.khach_hang_id = kh.id
        WHERE tt.id = ?";
$thanh_toan = db_select($sql, [$id]);

if (empty($thanh_toan)) {
    js_alert('Không tìm thấy giao dịch thanh toán!');
    js_redirect_to('admin/thanh_toan/index.php');
}

$tt = $thanh_toan[0];

// Xóa giao dịch thanh toán
$sql = "DELETE FROM thanh_toan WHERE id = ?";
$result = db_execute($sql, [$id]);

if ($result) {
    js_alert("Xóa giao dịch thanh toán của khách hàng '{$tt['ten_khach_hang']}' (Đơn {$tt['ma_don_thue']}) thành công!");
} else {
    js_alert('Có lỗi xảy ra khi xóa giao dịch!');
}

js_redirect_to('admin/thanh_toan/index.php');
?>