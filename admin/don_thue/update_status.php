<?php
session_start();
include("../../include/common.php");

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['admin_id'])) {
    redirect_to("admin/login.php");
}

$id = (int)($_GET['id'] ?? 0);
$new_status = $_GET['status'] ?? '';

if ($id <= 0) {
    js_alert('ID không hợp lệ!');
    js_redirect_to('admin/don_thue/index.php');
}

$allowed_statuses = ['da_xac_nhan', 'dang_thue', 'da_tra', 'huy'];
if (!in_array($new_status, $allowed_statuses)) {
    js_alert('Trạng thái không hợp lệ!');
    js_redirect_to('admin/don_thue/index.php');
}

// Lấy thông tin đơn thuê
$sql = "SELECT dt.*, x.trang_thai as xe_trang_thai 
        FROM don_thue dt 
        LEFT JOIN xe x ON dt.xe_id = x.id 
        WHERE dt.id = ?";
$don_thue = db_select($sql, [$id]);

if (empty($don_thue)) {
    js_alert('Không tìm thấy đơn thuê!');
    js_redirect_to('admin/don_thue/index.php');
}

$don_thue = $don_thue[0];

// Cập nhật trạng thái đơn thuê
$sql = "UPDATE don_thue SET trang_thai = ?, updated_at = CURRENT_TIMESTAMP";
$params = [$new_status];

// Nếu xác nhận đơn, lưu admin xác nhận
if ($new_status == 'da_xac_nhan') {
    $sql .= ", admin_xac_nhan = ?, ngay_xac_nhan = CURRENT_TIMESTAMP";
    $params[] = $_SESSION['admin_id'];
}

$sql .= " WHERE id = ?";
$params[] = $id;

$result = db_execute($sql, $params);

if ($result) {
    // Cập nhật trạng thái xe tương ứng
    $xe_status = '';
    switch ($new_status) {
        case 'da_xac_nhan':
        case 'dang_thue':
            $xe_status = 'dang_thue';
            break;
        case 'da_tra':
        case 'huy':
            $xe_status = 'san_sang';
            break;
    }
    
    if ($xe_status) {
        $sql_xe = "UPDATE xe SET trang_thai = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        db_execute($sql_xe, [$xe_status, $don_thue['xe_id']]);
    }
    
    $status_names = [
        'da_xac_nhan' => 'đã xác nhận',
        'dang_thue' => 'đang thuê',
        'da_tra' => 'đã trả',
        'huy' => 'đã hủy'
    ];
    
    js_alert("Cập nhật trạng thái đơn thuê thành \"{$status_names[$new_status]}\" thành công!");
} else {
    js_alert('Có lỗi xảy ra khi cập nhật trạng thái!');
}

js_redirect_to('admin/don_thue/index.php');
?>