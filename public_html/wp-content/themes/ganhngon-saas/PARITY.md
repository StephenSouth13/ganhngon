# Parity với theme SaaS Gánh Ngon

Theme WordPress `ganhngon-saas` (1.2.1) bám giao diện SaaS trong gói `docs/theme/ganh-ngon` (commit c674493a). WordPress vẫn là backend. Không gọi `/api/public/fnb/menu`, `/api/public/forms/*`, `/api/public/tickets/lookup`, `/api/public/leads/contact-form`. Không in giá món.

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
2. Ảnh nhà hàng tháng 10/2026 trong `assets/img/photos/` khi tên món khớp.
3. Ảnh nhóm thật của nhóm đó, nhãn nhỏ «Ảnh nhóm».

Không dùng ảnh minh hoạ tháng 9 và không dùng ảnh Unsplash làm ảnh món. Cách thêm ảnh: `HUONG-DAN-FTP.md`, mục 6.
