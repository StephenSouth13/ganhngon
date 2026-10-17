# Parity với theme SaaS Gánh Ngon

Theme WordPress `ganhngon-saas` (1.3.0) bám giao diện SaaS trong gói `docs/theme/ganh-ngon` (commit c674493a). WordPress vẫn là backend. Không gọi `/api/public/fnb/menu`, `/api/public/forms/*`, `/api/public/tickets/lookup`, `/api/public/leads/contact-form`. Không in giá món.

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

## 1.3.0 — Tùy biến, lead, một form
- Giao diện → Tùy biến → **Gánh Ngon**. Chưa bấm Xuất bản thì trang giữ đúng chữ và ảnh đang có. Cách sửa: `HUONG-DAN-CHINH-SUA.md`.
- `/dat-ban/`, `/lien-he/`, `/khach-doan-tiec-rieng/` dùng một form (`template-parts/gn-form.php`), gửi `gns_submit_request`. Handler ghi `wp_sao_bookings` (email, Telegram, Jetpack CRM của theme cha) và bài riêng `gn_lead`. `sao_create_booking` và `gns_send_note` vẫn nhận đơn cũ.
- Lead bot: option `gns_lead_email`, `gns_lead_webhook`, `gns_lead_secret`. Để trống thì tắt kênh đó.
- `/tin-tuc/` là danh sách bài WordPress (bài nổi bật, lưới, chuyên mục, `/tin-tuc/page/2/`). Trang bài dùng ngày `d/m/Y`, bài liên quan và nút tới form đặt bàn. Không còn 4 thẻ mẫu của giao diện SaaS.
- Ảnh phòng, sảnh, VIP, sự kiện và ảnh minh họa món (file gốc tháng 9/2026 và `su-kien-*.webp` Unsplash) đổi thành họa tiết than–vàng cho đến khi chọn ảnh trong Tùy biến → Ảnh chờ thay. `photos/` (món thật, mâm xiên) giữ nguyên. File `lau-ga-sau-rieng.webp` ở thư mục gốc được trỏ sang `photos/lau-ga-sau-rieng.webp`.

## 1.3.2 — Audit công khai
- Form cũ `sao_create_booking` / `.gn-kd-lead-form` bị gỡ khỏi nội dung trang. `/doi-tac/`, trang tiệc và 5 trang con dùng một form `gns_submit_request`.
- Ảnh `uploads/2026/09/ganhngon-*.webp` và `photos/com-nieu.webp` (Wikimedia) thành họa tiết. Danh sách mẫu lọc qua `gns_hidden_media_patterns`. Trang `/hinh-anh-review/` bỏ ảnh đó thay vì lát họa tiết. og:image và JSON-LD dùng `photos/lau-ga-sau-rieng.webp`.
- Chữ "workspace", "Hotline 24/7", "Đặt cọc 30%", "220+", chú thích nội bộ và quote chưa kiểm chứng không còn trong template. Địa chỉ chân trang và `addressRegion` dùng "113 Trần Hưng Đạo, Dương Đông, Phú Quốc".
- 301 các đường 404 trong audit (Sao → saophuquoc.com, cẩm nang/thực đơn cũ, `/ve-combo/`, `/privacy-policy/`). `/tin-tuc/page/N` quá trang cuối trả 404.
- UI (menu, form, nút, tiêu đề chân trang) theo ngôn ngữ hiện tại. Nội dung bài trong cơ sở dữ liệu không dịch.
