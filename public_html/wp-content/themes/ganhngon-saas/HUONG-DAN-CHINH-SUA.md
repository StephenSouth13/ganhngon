# Sửa chữ, ảnh và lead — không cần FTP

Vào **Giao diện → Tùy biến → Gánh Ngon**, sửa, rồi bấm **Xuất bản**. Chưa xuất bản thì trang giữ nguyên như đang chạy.

## Thông tin chung

Hotline, Zalo, WhatsApp, email, địa chỉ, giờ mở cửa, link Google Maps. Facebook, Instagram, TikTok, YouTube chỉ hiện ở chân trang khi có link.

## Trang chủ

Tiêu đề hero, đoạn mô tả, ảnh, chữ và link hai nút. Tiêu đề và đoạn mở đầu từng mục. Ô “Hiện …” tắt thì mục đó biến mất. Món mũi nhọn có đoạn chữ và ảnh riêng.

Link nút để trống thì giữ link đang dùng (Đặt bàn, Thực đơn).

## Thực đơn

Đoạn mở đầu và khối món mũi nhọn (tiêu đề, đoạn, ảnh). Danh sách món vẫn lấy từ thực đơn WordPress. Ảnh món: xem `HUONG-DAN-FTP.md`, mục 6.

## Liên hệ

Đoạn mở đầu và dòng ghi chú dưới form. Để trống ghi chú thì không in thêm dòng nào.

## Footer

Đoạn giới thiệu cột đầu và dòng cuối chân trang.

## Không gian

Tiêu đề, mô tả và ảnh Khu A / Khu B, chọn từ Thư viện. Chưa có ảnh (trong Tùy biến hoặc file `assets/img/khu-b-tiec-*.jpg`, `khu-a-*`) thì mục đó không hiện.

## Ảnh chờ thay

Ảnh phòng, sảnh, VIP, sân khấu và ảnh minh họa món (không phải ảnh chụp quán) đang hiện họa tiết than và vàng, giống ô trống trên thực đơn. Mỗi tên file một ô ảnh trong mục này. Chọn ảnh từ Thư viện rồi Xuất bản thì ảnh đó thay họa tiết ở mọi chỗ từng dùng file đó. Để trống thì vẫn là họa tiết.

Ảnh món thật trong `assets/img/photos/` (lẩu gà sầu riêng, mâm xiên, cocktail…) giữ nguyên. `photos/com-nieu.webp` là ảnh Wikimedia nên hiện họa tiết. Ảnh upload `2026/09/ganhngon-*.webp` cũng thành họa tiết; danh sách mẫu nằm ở filter `gns_hidden_media_patterns`. Trang Hình ảnh bỏ những ảnh đó thay vì lát cả trang bằng họa tiết. Ảnh hero và món mũi nhọn trên trang chủ / thực đơn vẫn ở mục Trang chủ và Thực đơn.

## Tin tức

`/tin-tuc/` liệt kê bài viết WordPress: bài mới nhất nổi bật, rồi lưới bài, lọc theo chuyên mục, phân trang `/tin-tuc/page/2/`. Ảnh đại diện lấy từ bài. Bài chưa có ảnh đại diện thì hiện họa tiết, không dùng ảnh minh họa. Trang một bài hiện ngày `d/m/Y`, bài liên quan và nút Đặt bàn.

## Menu

**Giao diện → Menu.** Gán vào “Menu chính” và “Menu chân trang”. Chưa gán thì header và chân trang giữ các link hiện tại.

## Nội dung trang

Sửa trong **Trang**. Nếu ô nội dung có chữ, chữ đó hiện dưới phần giao diện của theme. Trang Đặt bàn, Voucher, Cổng đối tác và Hỏi món không dùng ô này vì theme cha đã gắn form riêng.

## Lead cho bot

Cùng bảng Tùy biến, mục **Lead cho bot**. Ba option trong `wp_options`:

| Option | Để trống |
| --- | --- |
| `gns_lead_email` | Không gửi email bot |
| `gns_lead_webhook` | Không gọi webhook |
| `gns_lead_secret` | Không gửi header `X-GN-Secret` |

Email bot có tiêu đề `[GN-LEAD] nhóm khách | số khách | form`. Webhook là POST JSON, timeout 5 giây, không chặn khách. Đơn vẫn vào danh sách đặt bàn, email nhà hàng, Telegram và CRM như cũ.

Danh sách riêng: **Lead khách** trong wp-admin. Lọc theo nhóm khách và trạng thái (Mới, Đã liên hệ, Chốt, Huỷ). Đọc qua REST chỉ khi đã đăng nhập (Application Password): `GET /wp-json/gns/v1/leads`.

## Một form

`/dat-ban/`, `/lien-he/` và `/khach-doan-tiec-rieng/` dùng cùng form. `/dat-ban/` mở sẵn nhóm Khách lẻ. Trang tiệc mở sẵn Khách đặt tiệc. `/lien-he/` không chọn sẵn. Thêm `?nhom=khach-le` (hoặc `gia-dinh`, `doan-truc-tiep`, `doanh-nghiep`, `dat-tiec`, `doi-tac-dua-khach`) để chọn nhóm lúc mở trang.

Khách đặt tiệc thì hiện Loại tiệc. Khách doanh nghiệp thì hiện Tên công ty và ô Cần hóa đơn VAT. Đối tác đưa khách thì hiện Công ty / đơn vị. Mã đối tác trên link `/p/{mã}` vẫn gắn vào đơn như trước, form này không tạo đối tác mới.
