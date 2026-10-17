# Parity với theme SaaS Gánh Ngon

Theme WordPress `ganhngon-saas` (1.2.3) bám giao diện SaaS trong gói `docs/theme/ganh-ngon` (commit c674493a). WordPress vẫn là backend. Không gọi `/api/public/fnb/menu`, `/api/public/forms/*`, `/api/public/tickets/lookup`, `/api/public/leads/contact-form`. Không in giá món.

## Trang

Mỗi file HTML của theme SaaS được đưa vào `parts/mains/`. `header.php` / `footer.php` của child theme giữ header một dòng, một chân trang tối (pháp nhân, MST, cảnh báo, Sao Phú Quốc) và một nút liên hệ.

- Đặt bàn: form theme cha, `sao_create_booking`.
- Đối tác: `gn_partner_register` với đúng field `full_name`, `phone`, `partner_type`.
- Cổng đối tác: sổ cái `gn_portal_html()` khi có `?t=`; không có link thì form gửi `partner@ganhngon.com`.
- Voucher: mệnh giá từ `gn_voucher_tiers()`, mua qua `gn_voucher_buy` (không tin giá gửi từ trình duyệt).
- Tra cứu: đọc sổ voucher WordPress, không gọi ticket SaaS.
- Xác minh: `gn_partner_by_code`, chỉ hiện tên và khu vực khi hồ sơ đang active.
- Liên hệ, báo giá tiệc, gọi lại tư vấn: `wp_mail` tới contact / sales / partner.
- Tư vấn món: trả lời cục bộ trong `script.js`, không gọi model ngoài.

`/p/{mã}` giữ hành vi theme cha (ghi lượt quét, chuyển tới đặt bàn). Các đường cũ trong `redirects.json` (category, home-en, tra-cuu-en) chuyển 301.

## Ảnh thực đơn

`/thuc-don/` không còn dòng món trống. Ảnh món lấy theo thứ tự:

1. Media WordPress có slug, tên file hoặc tiêu đề trùng slug món (`tom-nuong.jpg` cho «Tôm nướng»).
2. Ảnh nhà hàng tháng 10/2026 trong `assets/img/photos/` khi tên món khớp đúng món đó.
3. Ảnh nhóm chỉ khi ảnh thật sự là nhóm đó: mâm xiên cho xiên, cơm niêu cho nhóm cơm niêu. Nhãn nhỏ «Ảnh nhóm» trên món xiên chưa có ảnh riêng.
4. Nhóm và món còn lại dùng ô hoạ tiết than / vàng (icon vẽ), không phải ảnh.

Không dùng ảnh một món (sầu riêng chiên, bò lúc lắc, cocktail, whisky, cơm niêu) làm ảnh của món khác hoặc của cả nhóm. Không dùng ảnh minh hoạ tháng 9 và không dùng ảnh Unsplash làm ảnh món. Cách thêm ảnh: `HUONG-DAN-FTP.md`, mục 6.

## 1.2.3 — Form liên hệ và đặt bàn
- `/lien-he/` thêm 3 ô: **Nhóm khách hàng** (bắt buộc, 6 nhóm, hiện mô tả nhu cầu dưới ô chọn), **Số lượng khách** (số, từ 1), **Khu vực** (Khu A, Khu B, Phòng VIP, Không yêu cầu). Gửi qua `gns_send_note` như cũ; email tới contact@ganhngon.com có thêm 3 dòng ở đầu nội dung và tiêu đề có nhóm khách + số khách. Giá trị ngoài danh sách bị server từ chối/bỏ.
- `/dat-ban/` (form theme cha, `sao_create_booking`) thêm Nhóm khách hàng và Khu vực trong "Thêm yêu cầu" (không bắt buộc). Số khách dùng ô có sẵn. Trước khi handler cha chạy, theme con ghép nhóm khách vào `need` (lưu "Nhu cầu: …" trong `wp_sao_bookings.notes`, email sale/cskh, Telegram, Jetpack CRM) và khu vực vào `note` ("Khu vực: Khu B."). Không đổi bảng hay cột.
- Ảnh Khu B: đặt `assets/img/khu-b-tiec-thoi-noi.jpg` và `assets/img/khu-b-tiec-sinh-nhat.jpg`; mục "Khu B – không gian tiệc" tự hiện trên `/lien-he/` và `/khach-doan-tiec-rieng/`. Chưa có file thì mục này ẩn.
