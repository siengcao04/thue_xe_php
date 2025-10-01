# Mô tả dự án

1. Hệ thống quản lý thuê xe cho công ty XeDeep

Chức năng khách hàng:
- Đăng ký/Đăng nhập: Quản lý thông tin cá nhân.
- Tìm kiếm xe: Lọc theo loại xe (xe máy, ô tô,...), giá, thời gian thuê.
- Đặt xe: Chọn xe, thời gian thuê, địa điểm nhận/trả xe.
- Theo dõi đơn thuê: Trạng thái đơn thuê, lịch sử thuê xe.
- Đánh giá dịch vụ: Gửi phản hồi sau khi thuê.

Chức năng quản trị viên:
- Quản lý xe: Thêm/sửa/xóa xe, cập nhật tình trạng (sẵn sàng, đang thuê, bảo trì).
- Quản lý đơn thuê: Xác nhận, hủy, theo dõi tiến độ thuê xe.
- Quản lý khách hàng: Thông tin người thuê, lịch sử giao dịch.
- Quản lý tài khoản quản trị: Gồm 2 quyền admin và nhân viên.
- Thống kê & báo cáo: Doanh thu, xe được thuê nhiều nhất, hiệu suất sử dụng xe.

# công nghệ sử dụng
PHP,Mysql,HTML,CSS

# Quy tắc
- Luôn phản hồi bằng tiếng Việt.
- Comment code bằng tiếng Việt.
- Giao diện đơn giản, dễ sử dụng
- Phần admin của mỗi bảng được đặt ở thư mục /admin/tên_bảng,mỗi thư mục gồm các file: index.php(danh sách), create.php(thêm), edit.php(sửa), update.php(cập nhật), delete.php(xóa).
- Sử dụng các hàm từ `include/common.php` để thao tác database và các hàm dùng chung ,không được chỉnh sửa file này.
- Thông tin kết nối database được đặt trong file `include/config.php`,không được chỉnh sửa file này.
- Script tạo bảng được lưu trong thư mục `database`.
- css phải đặt trong thư mục `assets/css`.
