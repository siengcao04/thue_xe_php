-- Script tạo database cho hệ thống quản lý thuê xe XeDeep
-- Ngày tạo: 01/10/2025

-- Tạo database
CREATE DATABASE IF NOT EXISTS thue_xe_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE thue_xe_db;

-- Bảng loại xe (xe máy, ô tô, xe đạp...)
CREATE TABLE loai_xe (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ten_loai VARCHAR(100) NOT NULL UNIQUE,
    mo_ta TEXT,
    trang_thai TINYINT DEFAULT 1 COMMENT '1: Hoạt động, 0: Không hoạt động',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Bảng hãng xe
CREATE TABLE hang_xe (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ten_hang VARCHAR(100) NOT NULL UNIQUE,
    mo_ta TEXT,
    trang_thai TINYINT DEFAULT 1 COMMENT '1: Hoạt động, 0: Không hoạt động',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Bảng xe
CREATE TABLE xe (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ma_xe VARCHAR(20) NOT NULL UNIQUE,
    ten_xe VARCHAR(150) NOT NULL,
    loai_xe_id INT NOT NULL,
    hang_xe_id INT NOT NULL,
    bien_so VARCHAR(20) UNIQUE,
    so_cho_ngoi INT DEFAULT 2,
    gia_thue_ngay DECIMAL(10,2) NOT NULL,
    gia_thue_gio DECIMAL(10,2),
    mo_ta TEXT,
    hinh_anh VARCHAR(255),
    trang_thai ENUM('san_sang', 'dang_thue', 'bao_tri', 'khong_hoat_dong') DEFAULT 'san_sang',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (loai_xe_id) REFERENCES loai_xe(id) ON DELETE RESTRICT,
    FOREIGN KEY (hang_xe_id) REFERENCES hang_xe(id) ON DELETE RESTRICT
);

-- Bảng quản trị viên
CREATE TABLE admin (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    ho_ten VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    sdt VARCHAR(15),
    vai_tro ENUM('admin', 'nhan_vien') DEFAULT 'nhan_vien',
    avatar VARCHAR(255),
    trang_thai TINYINT DEFAULT 1 COMMENT '1: Hoạt động, 0: Không hoạt động',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Bảng khách hàng
CREATE TABLE khach_hang (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    ho_ten VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    sdt VARCHAR(15) NOT NULL,
    dia_chi TEXT,
    ngay_sinh DATE,
    gioi_tinh ENUM('nam', 'nu', 'khac'),
    so_cmnd VARCHAR(20) UNIQUE,
    bang_lai VARCHAR(20),
    avatar VARCHAR(255),
    trang_thai TINYINT DEFAULT 1 COMMENT '1: Hoạt động, 0: Bị khóa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Bảng đơn thuê xe
CREATE TABLE don_thue (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ma_don VARCHAR(20) NOT NULL UNIQUE,
    khach_hang_id INT NOT NULL,
    xe_id INT NOT NULL,
    ngay_thue DATE NOT NULL,
    gio_thue TIME,
    ngay_tra DATE NOT NULL,
    gio_tra TIME,
    dia_diem_nhan TEXT NOT NULL,
    dia_diem_tra TEXT,
    gia_thue DECIMAL(10,2) NOT NULL,
    tien_dat_coc DECIMAL(10,2) DEFAULT 0,
    tong_tien DECIMAL(10,2) NOT NULL,
    ghi_chu TEXT,
    trang_thai ENUM('cho_xac_nhan', 'da_xac_nhan', 'dang_thue', 'da_tra', 'huy') DEFAULT 'cho_xac_nhan',
    ly_do_huy TEXT,
    admin_xac_nhan INT,
    ngay_xac_nhan TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (khach_hang_id) REFERENCES khach_hang(id) ON DELETE RESTRICT,
    FOREIGN KEY (xe_id) REFERENCES xe(id) ON DELETE RESTRICT,
    FOREIGN KEY (admin_xac_nhan) REFERENCES admin(id) ON DELETE SET NULL
);

-- Bảng đánh giá dịch vụ
CREATE TABLE danh_gia (
    id INT PRIMARY KEY AUTO_INCREMENT,
    don_thue_id INT NOT NULL,
    khach_hang_id INT NOT NULL,
    xe_id INT NOT NULL,
    diem_danh_gia TINYINT NOT NULL CHECK (diem_danh_gia >= 1 AND diem_danh_gia <= 5),
    noi_dung TEXT,
    hinh_anh VARCHAR(255),
    trang_thai TINYINT DEFAULT 1 COMMENT '1: Hiển thị, 0: Ẩn',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (don_thue_id) REFERENCES don_thue(id) ON DELETE CASCADE,
    FOREIGN KEY (khach_hang_id) REFERENCES khach_hang(id) ON DELETE CASCADE,
    FOREIGN KEY (xe_id) REFERENCES xe(id) ON DELETE CASCADE
);

-- Bảng thanh toán
CREATE TABLE thanh_toan (
    id INT PRIMARY KEY AUTO_INCREMENT,
    don_thue_id INT NOT NULL,
    so_tien DECIMAL(10,2) NOT NULL,
    hinh_thuc ENUM('tien_mat', 'chuyen_khoan', 'the_tin_dung') DEFAULT 'tien_mat',
    ma_giao_dich VARCHAR(100),
    trang_thai ENUM('cho_thanh_toan', 'da_thanh_toan', 'that_bai') DEFAULT 'cho_thanh_toan',
    ghi_chu TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (don_thue_id) REFERENCES don_thue(id) ON DELETE CASCADE
);

-- Bảng hình ảnh xe (nhiều ảnh cho 1 xe)
CREATE TABLE hinh_anh_xe (
    id INT PRIMARY KEY AUTO_INCREMENT,
    xe_id INT NOT NULL,
    duong_dan VARCHAR(255) NOT NULL,
    mo_ta VARCHAR(200),
    la_anh_chinh TINYINT DEFAULT 0 COMMENT '1: Ảnh chính, 0: Ảnh phụ',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (xe_id) REFERENCES xe(id) ON DELETE CASCADE
);

-- Bảng thông báo
CREATE TABLE thong_bao (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tieu_de VARCHAR(200) NOT NULL,
    noi_dung TEXT NOT NULL,
    loai_thong_bao ENUM('he_thong', 'khuyen_mai', 'thong_tin') DEFAULT 'thong_tin',
    khach_hang_id INT NULL COMMENT 'NULL: Gửi cho tất cả',
    da_doc TINYINT DEFAULT 0 COMMENT '1: Đã đọc, 0: Chưa đọc',
    trang_thai TINYINT DEFAULT 1 COMMENT '1: Hiển thị, 0: Ẩn',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (khach_hang_id) REFERENCES khach_hang(id) ON DELETE CASCADE
);

-- Chèn dữ liệu mẫu

-- Dữ liệu loại xe
INSERT INTO loai_xe (ten_loai, mo_ta) VALUES 
('Xe máy', 'Các loại xe máy số, xe máy tay ga'),
('Ô tô', 'Ô tô 4 chỗ, 7 chỗ'),
('Xe đạp', 'Xe đạp thường, xe đạp thể thao'),
('Xe máy điện', 'Xe máy điện thân thiện môi trường');

-- Dữ liệu hãng xe
INSERT INTO hang_xe (ten_hang, mo_ta) VALUES 
('Honda', 'Hãng xe Honda'),
('Yamaha', 'Hãng xe Yamaha'),
('Toyota', 'Hãng ô tô Toyota'),
('Vinfast', 'Hãng xe Vinfast'),
('Giant', 'Hãng xe đạp Giant'),
('Piaggio', 'Hãng xe Piaggio');

-- Dữ liệu admin mặc định (password: admin123)
INSERT INTO admin (username, password, ho_ten, email, vai_tro) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Quản trị viên', 'admin@xedeep.com', 'admin'),
('nhanvien1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Nhân viên 1', 'nhanvien1@xedeep.com', 'nhan_vien');

-- Dữ liệu xe mẫu
INSERT INTO xe (ma_xe, ten_xe, loai_xe_id, hang_xe_id, bien_so, so_cho_ngoi, gia_thue_ngay, gia_thue_gio, mo_ta, trang_thai) VALUES 
('XM001', 'Honda Air Blade 125', 1, 1, '29B1-12345', 2, 150000, 20000, 'Xe máy Honda Air Blade 125cc, màu đỏ, đời 2023', 'san_sang'),
('XM002', 'Yamaha Grande', 1, 2, '29B1-12346', 2, 140000, 18000, 'Xe máy Yamaha Grande 125cc, màu xanh, đời 2022', 'san_sang'),
('OT001', 'Toyota Vios', 2, 3, '29A-12345', 5, 800000, 100000, 'Ô tô Toyota Vios 4 chỗ, màu trắng, đời 2021', 'san_sang'),
('XD001', 'Giant ATX', 3, 5, NULL, 1, 50000, 8000, 'Xe đạp thể thao Giant ATX', 'san_sang');

-- Tạo chỉ mục để tối ưu hiệu suất
CREATE INDEX idx_xe_loai ON xe(loai_xe_id);
CREATE INDEX idx_xe_hang ON xe(hang_xe_id);
CREATE INDEX idx_xe_trang_thai ON xe(trang_thai);
CREATE INDEX idx_don_thue_khach_hang ON don_thue(khach_hang_id);
CREATE INDEX idx_don_thue_xe ON don_thue(xe_id);
CREATE INDEX idx_don_thue_trang_thai ON don_thue(trang_thai);
CREATE INDEX idx_don_thue_ngay ON don_thue(ngay_thue, ngay_tra);
CREATE INDEX idx_danh_gia_xe ON danh_gia(xe_id);
CREATE INDEX idx_thanh_toan_don ON thanh_toan(don_thue_id);
CREATE INDEX idx_hinh_anh_xe ON hinh_anh_xe(xe_id);
CREATE INDEX idx_thong_bao_khach_hang ON thong_bao(khach_hang_id);